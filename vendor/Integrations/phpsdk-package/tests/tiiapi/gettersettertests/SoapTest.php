<?php

require_once(__DIR__ . '/../testconsts.php');
require_once __DIR__ . '/../../../vendor/autoload.php';

use Integrations\PhpSdk\SubmissionSoap;

class SoapTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var SubmissionSoap
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new SubmissionSoap(dirname(__FILE__).'/../../../src/wsdl/lis-result.wsdl', []);
    }

    public function testSetCurlHandler()
    {
        $expected = 'test';
        $this->object->setCurlHandler($expected);
        $result = $this->object->getCurlHandler();

        $this->assertEquals($expected,$result);
    }

    public function testGetCurlHandler()
    {
        $expected = 'test';
        $this->object->setCurlHandler($expected);
        $result = $this->object->getCurlHandler();

        $this->assertEquals($expected,$result);
    }
}
