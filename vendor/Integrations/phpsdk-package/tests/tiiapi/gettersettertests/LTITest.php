<?php

require_once(__DIR__ . '/../testconsts.php');
require_once __DIR__ . '/../../../vendor/autoload.php';

use Integrations\PhpSdk\LTI;

class LTITest extends PHPUnit_Framework_TestCase
{
    /**
     * @var LTI
     */
    protected $object;

    public static function setUpBeforeClass()
    {
        // fwrite(STDOUT,"\n" . __METHOD__ . "\n");
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new LTI( TII_APIBASEURL );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testSetAccountId()
    {
        $expected = 12345;
        $this->object->setAccountId($expected);
    }

    public function testGetAccountId()
    {
        $expected = 12345;
        $this->object->setAccountId($expected);
        $result = $this->object->getAccountId();

        $this->assertEquals($expected,$result);
    }

    public function testSetSharedKey()
    {
        $expected = 'secret';
        $this->object->setSharedKey($expected);
    }

    public function testGetSharedKey()
    {
        $expected = 'secret';
        $this->object->setSharedKey($expected);
        $result = $this->object->getSharedKey();

        $this->assertEquals($expected,$result);
    }

    public function testSetProxyType()
    {
        $expected = 'test';
        $this->object->setProxyType($expected);
    }

    public function testGetProxyType()
    {
        $expected = 'test';
        $this->object->setProxyType($expected);
        $result = $this->object->getProxyType();

        $this->assertEquals($expected,$result);
    }

    public function testSetProxyBypass()
    {
        $expected = 'test';
        $this->object->setProxyBypass($expected);
    }

    public function testGetProxyBypass()
    {
        $expected = 'test';
        $this->object->setProxyBypass($expected);
        $result = $this->object->getProxyBypass();

        $this->assertEquals($expected,$result);
    }

}
