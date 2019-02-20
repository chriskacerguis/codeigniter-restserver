<?php

use PHPUnit\Framework\TestCase;

class GetTest extends TestCase
{
    private $http;

    public function setUp() : void
    {
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://0.0.0.0:8080']);
    }

    public function tearDown() : void
    {
        $this->http = null;
    }

    public function testGet()
    {
        $response = $this->http->get('/api/test');
        $data = json_decode($response->getBody(), true);

        $this->assertEquals($data[0]['name'], 'Luke');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetParam()
    {
        $response = $this->http->get('/api/test/chris');
        $data = json_decode($response->getBody(), true);

        $this->assertEquals($data[0], 'chris');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetWithKey()
    { // fix when we have the key
        $response = $this->http->get('/api/test/chris');
        $data = json_decode($response->getBody(), true);

        $this->assertEquals($data[0], 'chris');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetParamWithKey()
    {
        // fix when we have the key
        $response = $this->http->get('/api/test/chris');
        $data = json_decode($response->getBody(), true);

        $this->assertEquals($data[0], 'chris');
        $this->assertEquals(200, $response->getStatusCode());
    }

}