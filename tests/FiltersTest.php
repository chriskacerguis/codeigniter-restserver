<?php

declare(strict_types=1);

use chriskacerguis\RestServer\Config\Rest;
use chriskacerguis\RestServer\Filters\ApiKeyFilter;
use chriskacerguis\RestServer\Filters\AuthFilter;
use chriskacerguis\RestServer\Filters\RateLimitFilter;
use PHPUnit\Framework\TestCase;

final class FiltersTest extends TestCase
{
    private function requestWith(array $opts = [])
    {
        // Minimal request stub implementing methods used by our filters
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

            // Unused RequestInterface methods for our tests
            public function setMethod($method)
            {
                return $this;
            }

            public function setLocale($locale)
            {
                return $this;
            }

            public function getLocale()
            {
                return 'en';
            }

            public function isValidIP($ip = null, $which = null)
            {
                return true;
            }

            public function getServer($index = null)
            {
                return null;
            }

            public function getEnv($index = null)
            {
                return null;
            }

            public function getBody()
            {
                return '';
            }

            public function setBody($body)
            {
                return $this;
            }

            public function getHeader($name)
            {
                return null;
            }

            public function headers()
            {
                return [];
            }

            public function hasHeader($name)
            {
                return false;
            }

            public function getProtocolVersion()
            {
                return '1.1';
            }

            public function setProtocolVersion($version)
            {
                return $this;
            }

            public function getPost($index = null)
            {
                return null;
            }

            public function getPostGet($index = null)
            {
                return null;
            }

            public function getJSON($assoc = false)
            {
                return null;
            }

            public function getRawInput()
            {
                return [];
            }

            public function getUserAgent()
            {
                return '';
            }
        };
    }

    public function testApiKeyFilterMissingKey(): void
    {
        $config = config(Rest::class);
        $config->enableKeys = true;

        $filter = new ApiKeyFilter();
        $resp = $filter->before($this->requestWith(['headers' => []]));
        $this->assertNotNull($resp);
        $this->assertSame(401, $resp->getStatusCode());
        $this->assertStringContainsString('API key missing', $resp->getBody());
    }

    public function testAuthFilterBasicSuccess(): void
    {
        $config = config(Rest::class);
        $config->auth = 'basic';
        $config->realm = 'TestRealm';
        $config->validLogins = ['user' => 'pass'];

        $hdr = 'Basic '.base64_encode('user:pass');
        $req = $this->requestWith(['headers' => ['Authorization' => $hdr], 'method' => 'GET']);
        $resp = (new AuthFilter())->before($req);
        $this->assertNull($resp); // allowed
    }

    public function testAuthFilterBasicFail(): void
    {
        $config = config(Rest::class);
        $config->auth = 'basic';
        $config->realm = 'TestRealm';
        $config->validLogins = ['user' => 'pass'];

        $hdr = 'Basic '.base64_encode('user:wrong');
        $req = $this->requestWith(['headers' => ['Authorization' => $hdr], 'method' => 'GET']);
        $resp = (new AuthFilter())->before($req);
        $this->assertNotNull($resp);
        $this->assertSame(401, $resp->getStatusCode());
        $this->assertNotSame('', $resp->getHeaderLine('WWW-Authenticate'));
    }

    public function testRateLimitHeadersAndBlocking(): void
    {
        $config = config(Rest::class);
        $config->enableLimits = true;
        $config->limitDefaultPerHour = 2;
        $config->limitWindowSeconds = 60;

        // Provide an in-memory LimitModel stub via config override
        $config->limitModelClass = new class() {
            private static array $rows = [];
            private static int $auto = 1;
            private static $lastInsertId;
            private array $w = [];

            public function where(array $w)
            {
                $this->w = $w;

                return $this;
            }

            public function get()
            {
                $row = null;
                foreach (self::$rows as $r) {
                    $ok = true;
                    foreach (($this->w ?? []) as $k=>$v) {
                        if (($r[$k] ?? null) !== $v) {
                            $ok = false;
                            break;
                        }
                    } if ($ok) {
                        $row = $r;
                        break;
                    }
                }

                return new class($row) {
                    private $r;

                    public function __construct($r)
                    {
                        $this->r = $r;
                    }

                    public function getRowArray()
                    {
                        return $this->r;
                    }
                };
            }

            public function insert(array $row)
            {
                $row['id'] = self::$auto++;
                self::$rows[$row['id']] = $row;
                self::$lastInsertId = $row['id'];
            }

            public function getInsertID()
            {
                return self::$lastInsertId;
            }

            public function update($id, array $data)
            {
                self::$rows[$id] = array_merge(self::$rows[$id], $data);
            }
        }::class; // pass class-string

        $req = $this->requestWith(['headers'=>[], 'path'=>'/v1/users', 'method'=>'GET', 'ip'=>'1.2.3.4']);
        $filter = new RateLimitFilter();

        // 1st request allowed
        $this->assertNull($filter->before($req));
        // 2nd request allowed, but remaining = 0
        $this->assertNull($filter->before($req));
        // 3rd request blocked
        $resp = $filter->before($req);
        $this->assertNotNull($resp);
        $this->assertSame(429, $resp->getStatusCode());
    }
}
