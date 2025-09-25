<?php
declare(strict_types=1);

namespace Tests\Support;

use chriskacerguis\RestServer\RestController;
use CodeIgniter\HTTP\ResponseInterface;

class DummyController extends RestController
{
    public function index(): ResponseInterface
    {
        return $this->respondData(['method' => 'GET'], 200);
    }

    public function create(): ResponseInterface
    {
        return $this->respondData(['method' => 'POST'], 201);
    }

    public function update($id = null): ResponseInterface
    {
        return $this->respondData(['method' => 'PUT', 'id' => $id], 202);
    }

    public function patch($id = null): ResponseInterface
    {
        return $this->respondData(['method' => 'PATCH', 'id' => $id], 200);
    }

    public function delete($id = null): ResponseInterface
    {
        return $this->respondData(null, 204);
    }
}
