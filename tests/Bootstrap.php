<?php
declare(strict_types=1);

// Minimal bootstrap for testing the package outside a full CI4 app.
// Provides basic stubs for config() and service() helpers used in filters.

// ---- CI4 interface stubs (only methods needed by our filters) ----
namespace CodeIgniter\Filters { interface FilterInterface { public function before(\CodeIgniter\HTTP\RequestInterface $request, $arguments=null); public function after(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, $arguments=null); } }
namespace CodeIgniter\HTTP {
    interface RequestInterface { public function getHeaderLine($name): string; public function getGet($key=null); public function getMethod($upper=false): string; public function getUri(): URI; public function getIPAddress(): string; }
    interface ResponseInterface { public function setHeader($k,$v): ResponseInterface; public function setStatusCode(int $code): ResponseInterface; public function setJSON($data): ResponseInterface; }
    class URI { public function getPath(): string { return '/'; } }
    class Response implements ResponseInterface { private array $headers=[]; private int $status=200; private $body; public function __construct($config=null) {} public function setHeader($k,$v): ResponseInterface{$this->headers[$k]=$v; return $this;} public function setStatusCode(int $code): ResponseInterface{$this->status=$code; return $this;} public function setJSON($data): ResponseInterface{$this->body=json_encode($data); return $this;} }
}
namespace CodeIgniter\Config { class BaseConfig {} }
namespace CodeIgniter { class Model { protected $DBGroup='default'; protected $table; protected $primaryKey='id'; protected $returnType='array'; protected $useSoftDeletes=false; protected $allowedFields=[]; protected $useTimestamps=false; public function __construct($db=null) {} public function builder(): \CodeIgniter\Database\BaseBuilder{ return new \CodeIgniter\Database\BaseBuilder(); } public function where($k,$v=null){ return $this->builder()->where($k,$v);} public function get(){ return $this->builder()->get(); } public function insert(array $data){ return true; } public function getInsertID(){ return 1; } public function update($id, array $data){ return true; } }
}
namespace CodeIgniter\Database { class QueryResult { public function getRowArray(): ?array { return null; } }
    class BaseBuilder { private array $where=[]; public function where($k,$v=null): self{ return $this; } public function orWhere($k,$v=null): self{ return $this; } public function groupStart(): self{ return $this; } public function groupEnd(): self{ return $this; } public function get(): QueryResult { return new QueryResult(); } }
    class Forge { public function addField($f): self { return $this;} public function addKey($k,$primary=false): self { return $this;} public function addUniqueKey($k): self { return $this;} public function createTable($t,$ifNotExists=false): bool { return true;} public function dropTable($t,$ifExists=false): bool { return true;} }
    class Migration { /** @var Forge */ public $forge; public function __construct(){ $this->forge=new Forge(); } }
}

// Minimal stubs to satisfy RestController inheritance and response handling in tests
namespace CodeIgniter\RESTful { class ResourceController { protected $format = 'json'; protected $response; } }
namespace CodeIgniter\API {
    trait ResponseTrait {
        protected $response;
        protected function respond($data = null, int $status = 200): \CodeIgniter\HTTP\ResponseInterface {
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
                public function setHeader($k, $v): \CodeIgniter\HTTP\ResponseInterface { $this->headers[$k] = $v; return $this; }
                public function setStatusCode(int $code): \CodeIgniter\HTTP\ResponseInterface { $this->status = $code; return $this; }
                public function setJSON($data): \CodeIgniter\HTTP\ResponseInterface { $this->body = json_encode($data); return $this; }
                // Accessors for tests
                public function getStatusCode(): int { return $this->status; }
                public function getHeaderLine(string $k): string { return $this->headers[$k] ?? ''; }
                public function getBody(): string { return (string) $this->body; }
            };
        }
        if ($name === 'session') {
            return new class {
                private array $data = [];
                public function set(string $k, $v): void { $this->data[$k] = $v; }
                public function get(string $k) { return $this->data[$k] ?? null; }
            };
        }
        if ($name === 'router') {
            return new class {
                public function controllerName(): string { return 'App\\Controllers\\Api\\Users'; }
                public function methodName(): string { return 'index'; }
            };
        }
        return null;
    }
}

}
