<?php

declare(strict_types=1);

use chriskacerguis\RestServer\Config\Rest;
use chriskacerguis\RestServer\Filters\ApiKeyFilter;
use PHPUnit\Framework\TestCase;

final class ApiKeyFilterTest extends TestCase
{
    private function requestWith(array $opts = [])
    {
        return new class($opts) implements \CodeIgniter\HTTP\RequestInterface {
            private array $opts;

            public function __construct(array $opts)
            {
                $this->opts = $opts;
            }

            public function getHeaderLine($name)
            {
                return $this->opts['headers'][$name] ?? '';
            }

            public function getGet($key = null)
            {
                $get = $this->opts['get'] ?? [];

                return $key === null ? $get : ($get[$key] ?? null);
            }

            public function getMethod($upper = false)
            {
                return $this->opts['method'] ?? 'GET';
            }

            public function getUri()
            {
                return new class($this->opts['path'] ?? '/') {
                    private string $p;

                    public function __construct($p)
                    {
                        $this->p = $p;
                    }

                    public function getPath()
                    {
                        return $this->p;
                    }
                };
            }

            public function getIPAddress()
            {
                return $this->opts['ip'] ?? '127.0.0.1';
            }
        };
    }

    private function installStubKeyModel(array $rows): void
    {
        // Provide a stub KeyModel via config->keyModelClass with no-arg constructor
        $class = new class() {
            public static array $rows = [];

            public function findValidKey(string $key): ?array
            {
                return self::$rows[$key] ?? null;
            }
        };
        $ref = new \ReflectionClass($class);
        $className = $ref->getName();
        $className::$rows = $rows;
        $config = config(Rest::class);
        $config->keyModelClass = $className;
    }

    public function testBypassWhenKeysDisabled(): void
    {
        $config = config(Rest::class);
        $config->enableKeys = false;

        $filter = new ApiKeyFilter();
        $this->assertNull($filter->before($this->requestWith(['headers'=>[]])));
    }

    public function testValidKeyInHeader(): void
    {
        $config = config(Rest::class);
        $config->enableKeys = true;
        $config->keyHeaderName = 'X-API-KEY';

        $this->installStubKeyModel([
            'abc123' => ['key' => 'abc123', 'level' => 1],
        ]);

        $req = $this->requestWith(['headers' => ['X-API-KEY' => 'abc123']]);
        $resp = (new ApiKeyFilter())->before($req);
        $this->assertNull($resp);
    }

    public function testValidKeyInQueryParam(): void
    {
        $config = config(Rest::class);
        $config->enableKeys = true;
        $config->keyHeaderName = 'X-API-KEY';

        $this->installStubKeyModel([
            'def456' => ['key' => 'def456', 'level' => 2],
        ]);

        $req = $this->requestWith(['get' => ['api_key' => 'def456']]);
        $resp = (new ApiKeyFilter())->before($req);
        $this->assertNull($resp);
    }

    public function testCustomHeaderNameRespected(): void
    {
        $config = config(Rest::class);
        $config->enableKeys = true;
        $config->keyHeaderName = 'X-CUSTOM-KEY';

        $this->installStubKeyModel([
            'zzz999' => ['key' => 'zzz999', 'level' => 3],
        ]);

        $req = $this->requestWith(['headers' => ['X-CUSTOM-KEY' => 'zzz999']]);
        $resp = (new ApiKeyFilter())->before($req);
        $this->assertNull($resp);
    }

    public function testInvalidKeyReturns401(): void
    {
        $config = config(Rest::class);
        $config->enableKeys = true;
        $config->keyHeaderName = 'X-API-KEY';

        $this->installStubKeyModel([]);

        $req = $this->requestWith(['headers' => ['X-API-KEY' => 'nope']]);
        $resp = (new ApiKeyFilter())->before($req);
        $this->assertNotNull($resp);
        $this->assertSame(401, $resp->getStatusCode());
        $this->assertStringContainsString('Invalid or expired API key', $resp->getBody());
    }

    public function testMissingKeyReturns401(): void
    {
        $config = config(Rest::class);
        $config->enableKeys = true;
        $config->keyHeaderName = 'X-API-KEY';

        $this->installStubKeyModel([]);

        $resp = (new ApiKeyFilter())->before($this->requestWith(['headers'=>[]]));
        $this->assertNotNull($resp);
        $this->assertSame(401, $resp->getStatusCode());
        $this->assertStringContainsString('API key missing', $resp->getBody());
    }
}
