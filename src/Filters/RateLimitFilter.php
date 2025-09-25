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
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config(RestConfig::class);

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

        $uriPath = '/' . ltrim((string) $request->getUri()->getPath(), '/');
        $identifier['uri'] = $uriPath;
        $identifier['method'] = strtoupper($request->getMethod());
        $identifier['ip_address'] = $request->getIPAddress();
        $identifier['api_key'] = $request->getHeaderLine('X-API-KEY') ?: ($request->getGet('api_key') ?? '');

        if (function_exists('service')) {
            $router = service('router');
            if ($router) {
                $identifier['class'] = $router->controllerName();
                $identifier['method'] = $router->methodName();
            }
        }

        $modelClass = $config->limitModelClass ?? LimitModel::class;
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

        $row = $limitModel->where($where)->get()->getRowArray();

        $now = time();
        $nowDt = gmdate('Y-m-d H:i:s', $now);

        $defaultLimit = (int) $config->limitDefaultPerHour;
        $periodSeconds = (int) $config->limitWindowSeconds;

        if (!$row) {
            $row = array_merge($where, [
                'count'     => 0,
                'limit'     => $defaultLimit,
                'reset_at'  => gmdate('Y-m-d H:i:s', $now + $periodSeconds),
                'created_at' => $nowDt,
                'updated_at' => null,
            ]);
            $limitModel->insert($row);
            $row['id'] = $limitModel->getInsertID();
        }

        if (!empty($row['reset_at']) && strtotime((string) $row['reset_at']) <= $now) {
            $row['count'] = 0;
            $row['reset_at'] = gmdate('Y-m-d H:i:s', $now + $periodSeconds);
        }

        $row['count'] = (int) $row['count'] + 1;
        $row['updated_at'] = $nowDt;

        $limitModel->update($row['id'], [
            'count' => $row['count'],
            'reset_at' => $row['reset_at'],
            'updated_at' => $row['updated_at'],
        ]);

        $max = (int) ($row['limit'] ?? $defaultLimit);
        if ($max > 0 && $row['count'] > $max) {
            $response = service('response') ?: new Response(config('App'));
            $retryAfter = max(1, strtotime((string) $row['reset_at']) - $now);
            $response->setHeader('Retry-After', (string) $retryAfter);
            return $response->setStatusCode(429)->setJSON([
                'status' => false,
                'error' => 'Rate limit exceeded',
            ]);
        }

        $remaining = max(0, $max - $row['count']);
        $response = service('response') ?: new Response(config('App'));
        $response->setHeader('X-RateLimit-Limit', (string) $max);
        $response->setHeader('X-RateLimit-Remaining', (string) $remaining);
        $response->setHeader('X-RateLimit-Reset', (string) strtotime((string) $row['reset_at']));

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}
