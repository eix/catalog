<?php

namespace Nohex\Eix\Modules\Catalog\Responses;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Core\Requests\Http\Mock as Request;
use Nohex\Eix\Modules\Catalog\Responses\Order as OrderResponse;

class OrderTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        // Test the response.
        $this->response = new OrderResponse(new Request);
    }

    public function tearDown()
    {
        $this->response = null;
    }

    public function testEmptyConstructor()
    {
        $response = new OrderResponse;

        $this->assertTrue($response instanceof OrderResponse);
    }

    /**
     * @expectedException Nohex\Eix\Core\Responses\Http\Media\Exception
     */
    public function testIssueNoTemplate()
    {
        // Set up a new application as XSLPage uses application settings.
        new MockApplication;

        $this->response->issue();
    }

    public function testIssueWithTemplate()
    {
        // Get an application running.
        new MockApplication;
        MockApplication::getCurrent();

        $this->expectOutputString("newconfirmed by customercancelled by customerconfirmed by vendorcancelled by vendorreadydeliveredclosedtesttext/html/request/uri\n");

        $this->response->setTemplateId('test');
        $this->response->issue();
    }

}
