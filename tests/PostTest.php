<?php

class PostTest extends PHPUnit_Framework_TestCase
{
    private $http;

    public function setUp()
    {
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://localhost:8080']);
    }

    public function tearDown() {
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
        $this->assertEquals(200, $response->getStatusCode());
    }
}