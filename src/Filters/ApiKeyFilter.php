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
    /**
     * @param array<string,mixed>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $config = config(RestConfig::class);
        if (!$config instanceof RestConfig) {
            $config = new RestConfig();
        }
        if (!$config->enableKeys) {
            return null;
        }

        $headerName = (string) $config->keyHeaderName;
        $apiKey = $request->getHeaderLine($headerName);
        if ($apiKey === '') {
            $queryKey = $request->getGet('api_key');
            if (!is_string($queryKey) || $queryKey === '') {
                $queryKey = $request->getGet($headerName);
            }
            $apiKey = is_string($queryKey) ? $queryKey : '';
        }

        if ($apiKey === '') {
            $response = service('response');
            if (!$response instanceof ResponseInterface) {
                $response = new Response(config('App'));
            }
            return $response->setStatusCode(401)->setJSON([
                $config->statusFieldName => false,
                $config->messageFieldName => 'API key missing',
            ]);
        }

        $modelClass = $config->keyModelClass ?? KeyModel::class;
        /** @var KeyModel $model */
        $model = new $modelClass();
        $row = $model->findValidKey((string) $apiKey);
        if (!$row) {
            $response = service('response');
            if (!$response instanceof ResponseInterface) {
                $response = new Response(config('App'));
            }
            return $response->setStatusCode(401)->setJSON([
                $config->statusFieldName => false,
                $config->messageFieldName => 'Invalid or expired API key',
            ]);
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
