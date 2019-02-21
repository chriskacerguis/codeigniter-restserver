<?php

use PHPUnit\Framework\TestCase;

class HeadTest extends TestCase
{
    private $http;

    public function setUp() : void
    {
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://localhost:8080']);
    }

    public function tearDown() : void
    {
        $this->http = null;
    }

    public function testHead()
    {
        $response   = $this->http->head('/api/test');
        $body       = (string) $response->getBody();
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($body);
    }

}