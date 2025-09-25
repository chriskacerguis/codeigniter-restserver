<?php
declare(strict_types=1);

// Minimal bootstrap for testing the package outside a full CI4 app.
// Provides basic stubs for config() and service() helpers used in filters.

// ---- CI4 interface stubs (only methods needed by our filters) ----
namespace CodeIgniter\Filters { interface FilterInterface { public function before(\CodeIgniter\HTTP\RequestInterface $request, $arguments=null); public function after(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, $arguments=null); } }
namespace CodeIgniter\HTTP {
    interface RequestInterface { public function getHeaderLine($name); public function getGet($key=null); public function getMethod($upper=false); public function getUri(); public function getIPAddress(); }
    interface ResponseInterface { public function setHeader($k,$v); public function setStatusCode(int $code); public function setJSON($data); }
}
namespace CodeIgniter\Config { class BaseConfig {} }

// Minimal stubs to satisfy RestController inheritance and response handling in tests
namespace CodeIgniter\RESTful { class ResourceController { protected $format = 'json'; protected $response; } }
namespace CodeIgniter\API {
    trait ResponseTrait {
        protected $response;
        protected function respond($data = null, int $status = 200) {
            if ($this->response === null) {
                $this->response = \service('response');
            }
            $this->response->setStatusCode($status);
            $this->response->setHeader('Content-Type', 'application/json');
            return $this->response->setJSON($data);
        }
    }
}

namespace {
use chriskacerguis\RestServer\Config\Rest;

// Simple PSR-4 autoloader for the library under test (no Composer dependency in CI)
spl_autoload_register(function ($class) {
    $prefix = 'chriskacerguis\\RestServer\\';
    $baseDir = dirname(__DIR__) . '/src/';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $path = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
    // Load test support classes
    $tPrefix = 'Tests\\Support\\';
    $tBase = dirname(__DIR__) . '/tests/';
    if (str_starts_with($class, $tPrefix)) {
        $relative = substr($class, strlen($tPrefix));
        $path = $tBase . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

if (!function_exists('config')) {
    function config($name)
    {
        static $rest;
        if ($name === Rest::class || $name === 'chriskacerguis\\RestServer\\Config\\Rest') {
            if ($rest === null) {
                $rest = new Rest();
            }
            return $rest;
        }
        return null;
    }
}

if (!function_exists('service')) {
    function service(string $name)
    {
        // Very light stubs for tests
        if ($name === 'response') {
            // Simple dummy that mimics CI4 Response minimal API used in filters
            return new class implements \CodeIgniter\HTTP\ResponseInterface {
                private array $headers = [];
                private int $status = 200;
                private $body;
                public function setHeader($k, $v) { $this->headers[$k] = $v; return $this; }
                public function setStatusCode(int $code) { $this->status = $code; return $this; }
                public function setJSON($data) { $this->body = json_encode($data); return $this; }
                // Accessors for tests
                public function getStatusCode(): int { return $this->status; }
                public function getHeaderLine(string $k): string { return $this->headers[$k] ?? ''; }
                public function getBody(): string { return (string) $this->body; }
            };
        }
        if ($name === 'session') {
            return new class {
                private array $data = [];
                public function set(string $k, $v) { $this->data[$k] = $v; }
                public function get(string $k) { return $this->data[$k] ?? null; }
            };
        }
        if ($name === 'router') {
            return new class {
                public function controllerName() { return 'App\\Controllers\\Api\\Users'; }
                public function methodName() { return 'index'; }
            };
        }
        return null;
    }
}

}
