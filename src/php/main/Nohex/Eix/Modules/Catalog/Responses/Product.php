<?php

namespace Nohex\Eix\Modules\Catalog\Responses;

/**
 * Provides a response tailored to the product view.
 */
class Product extends \Nohex\Eix\Modules\Catalog\Responses\Html
{
    public function setProductType($productType)
    {
    }

    public function issue()
    {
        $type = $this->getRequest()->getParameter('grup');

        // Get the relevant data.
        $products = new \Nohex\Eix\Modules\Catalog\Data\Products;
        $productList = $products->get($type);

        // Set the response.
        $this->setTemplateId('products/index');
        $this->addData('product', array('type' => $productType));

        parent::issue();
    }

}
