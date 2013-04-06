<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Core\Requests\Http as HttpRequest;
use Nohex\Eix\Modules\Catalog\Model\Products;
use Nohex\Eix\Modules\Catalog\Data\Sources\MockProducts as MockProductsDataSource;

class ProductViewerTest extends \PHPUnit_Framework_TestCase
{
    public function testHttpGetForAll()
    {
        // \Nohex\Eix\Services\Data\Sources\MongoDB::setConnector(new \Nohex\Eix\Services\Data\Sources\Connectors\MockMongo);

        $application = new MockApplication;

        // Inject the mock datasource.
        Products::getInstance()->setDataSource(new MockProductsDataSource);

        $responder = new ProductViewer(new HttpRequest);
        $this->assertTrue($responder instanceof ProductViewer);

        $response = $responder->httpGetForAll();
    }
}
