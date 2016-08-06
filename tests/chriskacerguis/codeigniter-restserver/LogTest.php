<?php

namespace Restserver\Log\Test;

class LogTest extends \PHPUnit_Framework_TestCase
{
    public $config;

    public $log;

    public function setUp()
    {
        $this->log = new \Restserver\Log\Log();
    }

    public function tearDown()
    {
        // No tear down needed
    }

    public function testCreateTable()
    {
        $this->markTestSkipped('needs implementation');
    }

    public function testEnabled()
    {
        $this->markTestSkipped('needs implementation');
    }

    public function testDbLog()
    {
        $this->markTestSkipped('needs implementation');
    }

    public function testFileLog()
    {
        $this->markTestSkipped('needs implementation');
    }

    public function testEntry()
    {
        $this->markTestSkipped('needs implementation');
    }

    public function testStartRequest()
    {
        $this->log->startRequest();
        $this->assertTrue(is_float($this->log->requestStart));
    }

    public function testEndRequest()
    {
        $this->log->endRequest();
        $this->assertTrue(is_float($this->log->requestEnd));
    }

    public function testTotalRequest()
    {
        $this->log->startRequest();
        $this->log->endRequest();
        $this->log->totalRequest();
        $this->assertTrue(is_float($this->log->requestTotal));       
    }


}