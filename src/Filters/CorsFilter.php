<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Filters;

use chriskacerguis\RestServer\Config\Rest as RestConfig;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    /**
     * @param array<string,mixed>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $config = config(RestConfig::class);
        if (!$config instanceof RestConfig) {
            $config = new RestConfig();
        }

        $allowedHeaders = implode(', ', (array) $config->allowedCorsHeaders);
        $allowedMethods = implode(', ', (array) $config->allowedCorsMethods);

        $origin = $request->getHeaderLine('Origin');

        if ((bool) $config->allowAnyCorsDomain) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: ' . $allowedHeaders);
            header('Access-Control-Allow-Methods: ' . $allowedMethods);
            header('Vary: Origin');
        } elseif ($origin && in_array($origin, (array) $config->allowedCorsOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Headers: ' . $allowedHeaders);
            header('Access-Control-Allow-Methods: ' . $allowedMethods);
            header('Vary: Origin');
        }

        foreach ((array) $config->forcedCorsHeaders as $h => $v) {
            header($h . ': ' . $v);
        }

        if (strtoupper((string) $request->getMethod()) === 'OPTIONS') {
            $response = service('response');
            if ($response instanceof ResponseInterface) {
                return $response->setStatusCode(204);
            }
            return null;
        }

        return null;
    }

    /**
     * @param array<string,mixed>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // no-op
    }
}
