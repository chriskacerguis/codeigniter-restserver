<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tests\Support\DummyController;

final class ControllerTest extends TestCase
{
    private function makeController(): DummyController
    {
        $c = new DummyController();
        // Inject response stub into protected property on parent ResourceController
        $ref = new ReflectionClass($c);
        $prop = $ref->getParentClass()->getProperty('response');
        $prop->setAccessible(true);
        $prop->setValue($c, service('response'));

        return $c;
    }

    public function testGet(): void
    {
        $controller = $this->makeController();
        $resp = $controller->index();
        $this->assertSame(200, $resp->getStatusCode());
    }

    public function testPost(): void
    {
        $controller = $this->makeController();
        $resp = $controller->create();
        $this->assertSame(201, $resp->getStatusCode());
    }

    public function testPut(): void
    {
        $controller = $this->makeController();
        $resp = $controller->update(123);
        $this->assertSame(202, $resp->getStatusCode());
    }

    public function testPatch(): void
    {
        $controller = $this->makeController();
        $resp = $controller->patch(123);
        $this->assertSame(200, $resp->getStatusCode());
    }

    public function testDelete(): void
    {
        $controller = $this->makeController();
        $resp = $controller->delete(123);
        $this->assertSame(204, $resp->getStatusCode());
    }
}
