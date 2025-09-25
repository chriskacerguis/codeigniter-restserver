<?php
declare(strict_types=1);

namespace chriskacerguis\RestServer\Filters;

use chriskacerguis\RestServer\Config\Rest as RestConfig;
use chriskacerguis\RestServer\Models\KeyModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Response;

class ApiKeyFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config(RestConfig::class);
        if (!$config->enableKeys) {
            return null;
        }

        $headerName = $config->keyHeaderName;
        $apiKey = $request->getHeaderLine($headerName)
            ?: ($request->getGet('api_key') ?? ($request->getGet($headerName) ?? ''));

        if ($apiKey === '') {
            $response = service('response') ?: new Response(config('App'));
            return $response->setStatusCode(401)->setJSON([
                $config->statusFieldName => false,
                $config->messageFieldName => 'API key missing',
            ]);
        }

        $modelClass = $config->keyModelClass ?? KeyModel::class;
        $model = new $modelClass();
        $row = $model->findValidKey($apiKey);
        if (!$row) {
            $response = service('response') ?: new Response(config('App'));
            return $response->setStatusCode(401)->setJSON([
                $config->statusFieldName => false,
                $config->messageFieldName => 'Invalid or expired API key',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
