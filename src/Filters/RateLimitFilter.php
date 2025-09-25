<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Filters;

use chriskacerguis\RestServer\Config\Rest as RestConfig;
use chriskacerguis\RestServer\Models\LimitModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Response;

class RateLimitFilter implements FilterInterface
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

        if (!$config->enableLimits) {
            return null;
        }

        $identifier = [
            'uri'       => null,
            'class'     => null,
            'method'    => null,
            'api_key'   => null,
            'ip_address' => null,
        ];

        $uri = $request->getUri();
        $uriPath = '/' . ltrim($uri->getPath(), '/');
        $identifier['uri']       = $uriPath;
        $identifier['method']    = strtoupper($request->getMethod());
        $identifier['ip_address'] = $request->getIPAddress();
        $apiKey = $request->getHeaderLine('X-API-KEY');
        if ($apiKey === '') {
            $q = $request->getGet('api_key');
            $apiKey = is_string($q) ? $q : '';
        }
        $identifier['api_key'] = $apiKey;

        if (function_exists('service')) {
            $router = service('router');
            if (
                is_object($router)
                && method_exists($router, 'controllerName')
                && method_exists($router, 'methodName')
            ) {
                $cn = $router->controllerName();
                if (is_string($cn)) {
                    $identifier['class'] = $cn;
                }
                $mn = $router->methodName();
                if (is_string($mn)) {
                    $identifier['method'] = $mn;
                }
            }
        }

        $modelClass = $config->limitModelClass ?? LimitModel::class;
        /** @var object $limitModel */
        $limitModel = new $modelClass();

        // Compose filter for lookup based on limitsMethod
        $where = [];
        switch ($config->limitsMethod) {
            case 'IP_ADDRESS':
                $where = ['ip_address' => $identifier['ip_address']];
                break;
            case 'API_KEY':
                $where = ['api_key' => $identifier['api_key']];
                break;
            case 'METHOD_NAME':
                $where = ['class' => $identifier['class'], 'method' => $identifier['method']];
                break;
            case 'ROUTED_URL':
            default:
                $where = ['uri' => $identifier['uri'], 'method' => $identifier['method']];
                break;
        }

        /** @var array<string,mixed>|null $row */
        $row = null;
        if (method_exists($limitModel, 'builder')) {
            $builder = $limitModel->builder();
            if (is_object($builder) && method_exists($builder, 'where')) {
                $builderW = $builder->where($where);
                if (is_object($builderW) && method_exists($builderW, 'get')) {
                    $query = $builderW->get();
                    if (is_object($query) && method_exists($query, 'getRowArray')) {
                        $row = $query->getRowArray();
                    }
                }
            }
        } elseif (method_exists($limitModel, 'where')) {
            $builder2 = $limitModel->where($where);
            if (is_object($builder2) && method_exists($builder2, 'get')) {
                $query = $builder2->get();
                if (is_object($query) && method_exists($query, 'getRowArray')) {
                    $row = $query->getRowArray();
                }
            }
        }

        $now = time();
        $nowDt = gmdate('Y-m-d H:i:s', $now);

        $defaultLimit = (int) $config->limitDefaultPerHour;
        $periodSeconds = (int) $config->limitWindowSeconds;

        if (!$row) {
            $row = array_merge(
                $where,
                [
                    'count'      => 0,
                    'limit'      => $defaultLimit,
                    'reset_at'   => gmdate('Y-m-d H:i:s', $now + $periodSeconds),
                    'created_at' => $nowDt,
                    'updated_at' => null,
                ]
            );
            if (method_exists($limitModel, 'insert')) {
                $limitModel->insert($row);
            }
            if (method_exists($limitModel, 'getInsertID')) {
                $row['id'] = $limitModel->getInsertID();
            }
        }

        /** @var array<string,mixed> $row */

        // Normalize expected types for row keys
        if (!isset($row['count']) || !is_int($row['count'])) {
            $row['count'] = is_numeric($row['count'] ?? null) ? (int) $row['count'] : 0;
        }
        if (!isset($row['limit']) || !is_int($row['limit'])) {
            $row['limit'] = is_numeric($row['limit'] ?? null) ? (int) $row['limit'] : $defaultLimit;
        }
        if (!isset($row['reset_at']) || !is_string($row['reset_at'])) {
            $row['reset_at'] = $nowDt;
        }
        /** @var array{count:int,limit:int,reset_at:string,id?:int,updated_at:mixed} $row */

        if ($row['reset_at'] !== '' && strtotime($row['reset_at']) <= $now) {
            $row['count'] = 0;
            $row['reset_at'] = gmdate('Y-m-d H:i:s', $now + $periodSeconds);
        }

        $row['count'] = $row['count'] + 1;
        $row['updated_at'] = $nowDt;

        $id = $row['id'] ?? null;
        if ($id !== null && method_exists($limitModel, 'update')) {
            $limitModel->update(
                $id,
                [
                    'count'      => $row['count'],
                    'reset_at'   => $row['reset_at'],
                    'updated_at' => $row['updated_at'],
                ]
            );
        }

        $max = $row['limit'];
        if ($max > 0 && $row['count'] > $max) {
            $response = service('response');
            if (!$response instanceof ResponseInterface) {
                $response = new Response(config('App'));
            }
            $retryAfter = max(1, strtotime($row['reset_at']) - $now);
            $response->setHeader('Retry-After', (string) $retryAfter);
            return $response->setStatusCode(429)->setJSON([
                'status' => false,
                'error' => 'Rate limit exceeded',
            ]);
        }

        $remaining = max(0, $max - $row['count']);
        $response = service('response');
        if (!$response instanceof ResponseInterface) {
            $response = new Response(config('App'));
        }
        $response->setHeader('X-RateLimit-Limit', (string) $max);
        $response->setHeader('X-RateLimit-Remaining', (string) $remaining);
        $response->setHeader('X-RateLimit-Reset', (string) strtotime($row['reset_at']));

        return null;
    }

    /**
     * @param array<string,mixed>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // No-op
    }
}
