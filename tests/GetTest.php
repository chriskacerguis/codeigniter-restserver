<?php

class GetTest extends PHPUnit_Framework_TestCase
{
    private $http;

    public function setUp()
    {
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://localhost:8080']);
    }

    public function tearDown() {
        $this->http = null;
    }

    public function testGet()
    {
        $response = $this->http->get('/api/test');
        $this->assertEquals(200, $response->getStatusCode());
    }
}