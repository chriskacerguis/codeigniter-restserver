<?php

use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
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

    public function testOptions()
    {
        $response = $this->http->options('/api/test');
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Origin'));
        $this->assertEquals(200, $response->getStatusCode());
    }

}