<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Modules\Catalog\Model\ProductImages;
use Nohex\Eix\Services\Net\Http\NotFoundException;

/**
 * Manages a product list.
 */
class Media extends \Nohex\Eix\Core\Responders\Http
{
    /**
     * GET /media/images/products/{id}
     * @return \Nohex\Eix\Core\Responses\Http\Image
     */
    protected function httpGetForImage()
    {
        $response = new \Nohex\Eix\Core\Responses\Http\Image($this->getRequest());

        $id = $this->getRequest()->getParameter('id');

        try {
            $image = ProductImages::getInstance()->findEntity($id);
            $response->setFileName($image->location);
        } catch (NotFoundException $exception) {
            $response->setFileName('../static/images/unknown-product.png');
        }

        return $response;
    }

    protected function httpGetForAll()
    {
        return $this->httpGetForImage();
    }
}
