<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Filters;

use chriskacerguis\RestServer\Config\Rest as RestConfig;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config(RestConfig::class);

        if ($config->auth === false || $config->auth === '') {
            // Auth disabled
            return null;
        }

        if ($config->auth === 'session') {
            $session = service('session');
            if ($session && ($session->get('isLoggedIn') === true || $session->get('logged_in') === true)) {
                return null;
            }

            return $this->unauthorizedResponse('basic', $config->realm, 'Session authentication required');
        }

        $authHeader = (string) ($request->getHeaderLine('Authorization') ?? '');
        $method = strtoupper($request->getMethod());

        if ($config->auth === 'basic') {
            $creds = $this->parseBasic($authHeader);
            if (!$creds) {
                return $this->unauthorizedResponse('basic', $config->realm);
            }

            $isValid = $this->validateCredentials($creds['username'], $creds['password'], $config);
            if ($isValid) {
                return null;
            }

            return $this->unauthorizedResponse('basic', $config->realm);
        }

        if ($config->auth === 'digest') {
            $digest = $this->parseDigest($authHeader);
            if (!$digest) {
                return $this->unauthorizedResponse('digest', $config->realm);
            }

            $username = $digest['username'] ?? '';
            $password = $this->findPasswordForUser($username, $config);
            if ($password === null) {
                return $this->unauthorizedResponse('digest', $config->realm);
            }

            $ha1 = md5($username.':'.$config->realm.':'.$password);
            $ha2 = md5($method.':'.($digest['uri'] ?? ''));

            $data = $ha1.':'.($digest['nonce'] ?? '').':'.($digest['nc'] ?? '').':'
                .($digest['cnonce'] ?? '').':'.($digest['qop'] ?? '').':'.$ha2;
            $validResponse = md5($data);

            if (hash_equals($validResponse, (string) ($digest['response'] ?? ''))) {
                return null;
            }

            return $this->unauthorizedResponse('digest', $config->realm);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }

    private function parseBasic(string $authHeader): ?array
    {
        if (!str_starts_with(strtolower($authHeader), 'basic ')) {
            return null;
        }

        $encoded = trim(substr($authHeader, 6));
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return null;
        }

        $parts = explode(':', $decoded, 2);
        if (count($parts) !== 2) {
            return null;
        }

        return ['username' => $parts[0], 'password' => $parts[1]];
    }

    private function parseDigest(string $authHeader): ?array
    {
        if (!str_starts_with(strtolower($authHeader), 'digest ')) {
            return null;
        }
        $data = substr($authHeader, 7);

        $neededParts = ['nonce', 'nc', 'cnonce', 'qop', 'username', 'uri', 'response'];
        $digest = [];

        preg_match_all('@(\w+)=([\"]?)([^,]+?)\2(?=,|$)@', $data, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $digest[$m[1]] = trim($m[3], '"');
        }

        foreach ($neededParts as $part) {
            if (!isset($digest[$part])) {
                return null;
            }
        }

        return $digest;
    }

    private function validateCredentials(string $username, string $password, RestConfig $config): bool
    {
        if ($config->authSource === 'library' && $config->authLibraryClass && $config->authLibraryFunction) {
            $class = $config->authLibraryClass;
            $func = $config->authLibraryFunction;

            if (method_exists($class, $func)) {
                if ((new \ReflectionMethod($class, $func))->isStatic()) {
                    return (bool) $class::$func($username, $password);
                }
                $instance = new $class();

                return (bool) $instance->$func($username, $password);
            }
        }

        return isset($config->validLogins[$username])
            && hash_equals((string) $config->validLogins[$username], $password);
    }

    private function findPasswordForUser(string $username, RestConfig $config): ?string
    {
        if ($config->authSource === 'library' && $config->authLibraryClass && $config->authLibraryFunction) {
            $class = $config->authLibraryClass;
            $func = $config->authLibraryFunction;

            if (method_exists($class, $func)) {
                if ((new \ReflectionMethod($class, $func))->isStatic()) {
                    $ok = (bool) $class::$func($username, null);

                    return $ok ? '' : null;
                }
                $instance = new $class();
                $ok = (bool) $instance->$func($username, null);

                return $ok ? '' : null;
            }
        }

        return $config->validLogins[$username] ?? null;
    }

    private function unauthorizedResponse(string $scheme, string $realm, string $message = 'Unauthorized'): ResponseInterface
    {
        $response = service('response');
        if (!$response instanceof ResponseInterface) {
            $response = new Response(config('App'));
        }

        if ($scheme === 'basic') {
            $response->setHeader('WWW-Authenticate', 'Basic realm="'.addslashes($realm).'", charset="UTF-8"');
        } elseif ($scheme === 'digest') {
            $nonce = bin2hex(random_bytes(16));
            $opaque = bin2hex(random_bytes(16));
            $header = sprintf(
                'Digest realm="%s", qop="auth", nonce="%s", opaque="%s"',
                addslashes($realm),
                $nonce,
                $opaque
            );
            $response->setHeader('WWW-Authenticate', $header);
        }

        return $response->setStatusCode(401)->setJSON([
            'status' => false,
            'error'  => $message,
        ]);
    }
}
