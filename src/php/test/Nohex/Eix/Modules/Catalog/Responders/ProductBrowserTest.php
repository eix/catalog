<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Core\Requests\Http as HttpRequest;
use Nohex\Eix\Modules\Catalog\Data\Sources\MockProducts as MockProductsDataSource;
use Nohex\Eix\Modules\Catalog\Model\Products;

class ProductBrowserTest extends \PHPUnit_Framework_TestCase
{
    public function testHttpGetForAll()
    {
        // Set up the environment.
        new MockApplication;

        // Inject the mock datasource.
        Products::getInstance()->setDataSource(new MockProductsDataSource);

        $responder = new ProductBrowser(new HttpRequest);
        $this->assertTrue($responder instanceof ProductBrowser);

        $response = $responder->httpGetForAll();
    }
}
