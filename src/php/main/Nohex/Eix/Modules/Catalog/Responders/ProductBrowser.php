<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Modules\Catalog\Model\ProductGroups;
use Nohex\Eix\Modules\Catalog\Model\Products;
use Nohex\Eix\Modules\Catalog\Responses\Html as HtmlResponse;
use Nohex\Eix\Services\Data\Responders\CollectionBrowser;
use Nohex\Eix\Services\Net\Http\NotFoundException;

/**
 * Displays a product list.
 */
class ProductBrowser extends CollectionBrowser
{
    const ITEM_NAME = 'product';
    const COLLECTION_NAME = 'products';

    public function httpGetForAll()
    {
        return $this->httpGetForHtml();
    }

    /**
     * GET /products[/:id]
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpGetForHtml()
    {
        $response = new HtmlResponse($this->getRequest());
        $id = $this->getRequest()->getParameter('id');
        $groupId = $this->getRequest()->getParameter('groupId');

        switch ($id) {
            case NULL:
                if ($groupId) {
                    $group = ProductGroups::getInstance()->findEntity($groupId);
                    $response->setData('group', $group->name);
                }

                $productList = $this->getProductList($groupId);
                if (empty($productList)) {
                    $response->setTemplateId('products/empty');
                } else {
                    $response->setTemplateId('products/index');
                    $response->setData('products', $productList);
                }

                $response->appendToTitle(_('Productes'));
                break;
            default:
                $response->setTemplateId('products/view');
                $product = $this->getForDisplay($id);
                if ($product['enabled']) {
                    $response->setData('product', $product);
                    $response->appendToTitle(_($product['name']));
                } else {
                    throw new NotFoundException('Product not found');
                }
                break;
        }

        return $response;
    }

    /**
     * Get a list of all enabled products.
     */
    public function getProductList($groupId = NULL, $includeDisabled = FALSE)
    {
        $options = array();
        // If disabled products are not meant to be included, include just the
        // enabled ones.
        if (!$includeDisabled) {
            $options['enabled'] = true;
        }
        if ($groupId) {
            // The matching should be done over 'groups._id', but because of how
            // products are being saved, this is the working form.
            $options["groups.{$groupId}._id"] = $groupId;
        }
        $products = $this->getFactory()->getAll($options);

        $productList = array();
        if (!empty($products)) {
            $productList = array();
            foreach ($products as $product) {
                if (!$groupId || @$product->groups[$groupId]) {
                    $displayProduct = $product->getForDisplay();
                    // Truncate the description if it is too long.
                    $description = $displayProduct['description'];
                    if (strlen($description) > 150) {
                        $displayProduct['description'] = substr($description, 0, 147) . '...';
                    }
                    // Add all the products's displayable fields to the list.
                    $productList[] = $displayProduct;
                }
            }

        }

        return $productList;
    }

    public function getDefaultFactory()
    {
        return Products::getInstance();
    }
}
