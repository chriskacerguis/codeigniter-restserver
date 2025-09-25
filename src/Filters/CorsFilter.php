<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Filters;

use chriskacerguis\RestServer\Config\Rest as RestConfig;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config(RestConfig::class);

        $allowedHeaders = implode(', ', $config->allowedCorsHeaders);
        $allowedMethods = implode(', ', $config->allowedCorsMethods);

        $origin = $request->getHeaderLine('Origin');

        if ($config->allowAnyCorsDomain) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: '.$allowedHeaders);
            header('Access-Control-Allow-Methods: '.$allowedMethods);
            header('Vary: Origin');
        } elseif ($origin && in_array($origin, $config->allowedCorsOrigins, true)) {
            header('Access-Control-Allow-Origin: '.$origin);
            header('Access-Control-Allow-Headers: '.$allowedHeaders);
            header('Access-Control-Allow-Methods: '.$allowedMethods);
            header('Vary: Origin');
        }

        foreach ($config->forcedCorsHeaders as $h => $v) {
            header($h.': '.$v);
        }

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return service('response')->setStatusCode(204);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
