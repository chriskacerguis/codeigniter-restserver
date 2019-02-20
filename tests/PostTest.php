<?php

use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
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

    public function testPost()
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        $response = $this->http->post('/api/test', ['form_params' => $data]);

        $rdata = json_decode($response->getBody(), true);
        $this->assertEquals($data['key1'], $rdata['key1']);
        $this->assertEquals($data['key2'], $rdata['key2']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostWithKey()
    { // fix when we have the key
        $data = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        $response = $this->http->post('/api/test', ['form_params' => $data]);

        $rdata = json_decode($response->getBody(), true);
        $this->assertEquals($data['key1'], $rdata['key1']);
        $this->assertEquals($data['key2'], $rdata['key2']);
        $this->assertEquals(200, $response->getStatusCode());
    }
}