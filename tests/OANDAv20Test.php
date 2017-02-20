<?php

namespace TheCodeMill\OANDA\Tests;

use TheCodeMill\OANDA\OANDAv20;

class OANDAv20Test extends \PHPUnit_Framework_TestCase
{
    protected $apiKey = '123456-7890';
    protected $apiEnvironment = OANDAv20::ENV_PRACTICE;

    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf(
            OANDAv20::class,
            new OANDAv20()
        );
    }

    public function testCanBeInstantiatedWithArguments()
    {
        $this->assertInstanceOf(
            OANDAv20::class,
            new OANDAv20($this->apiEnvironment, $this->apiKey)
        );
    }

    public function testSetAndGetApiEnvironment()
    {
        $oanda = new OANDAv20;

        $this->assertInstanceOf(
            OANDAv20::class,
            $oanda->setApiEnvironment($this->apiEnvironment)
        );

        $this->assertEquals(
            $oanda->getApiEnvironment(),
            $this->apiEnvironment
        );
    }

    public function testSetAndGetApiKey()
    {
        $oanda = new OANDAv20;

        $this->assertInstanceOf(
            OANDAv20::class,
            $oanda->setApiKey($this->apiKey)
        );

        $this->assertEquals(
            $oanda->getApiKey(),
            $this->apiKey
        );
    }
}
