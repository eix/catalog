<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Core\Requests\Http as HttpRequest;

class ProductViewerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testHttpGetForAll()
    {
        $application = new MockApplication;

        $responder = new ProductViewer(new HttpRequest);
        $this->assertTrue($responder instanceof ProductViewer);

        $response = $responder->httpGetForAll();
    }
}
