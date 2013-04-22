<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\Responders\Http as HttpResponder;
use Nohex\Eix\Modules\Catalog\Model\Products;
use Nohex\Eix\Modules\Catalog\Model\ProductGroups;
use Nohex\Eix\Modules\Catalog\Responses\Html as HtmlResponse;
use Nohex\Eix\Services\Data\Factory;
use Nohex\Eix\Services\Net\Http\NotFoundException;

/**
 * Displays a product list.
 */
class ProductViewer extends HttpResponder
{
    private static $factory;

    public function httpGetForAll()
    {
        return $this->httpGetForHtml();
    }

    /**
     * GET /products[/:id]
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    protected function httpGetForHtml()
    {
        $response = new HtmlResponse($this->getRequest());
        $id = $this->getRequest()->getParameter('id');
        $groupId = $this->getRequest()->getParameter('groupId');

        switch ($id) {
            case NULL:
                $productList = array();
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
                $product = $this->getProduct($id);
                $response->setTemplateId('products/view');
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
            $options['enabled'] = TRUE;
        }
        if ($groupId) {
            // The matching should be done over 'groups._id', but because of how
            // products are being saved, this is the working form.
            $options["groups.{$groupId}._id"] = $groupId;
        }
        $products = self::getFactory()->getAll($options);

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

    /**
     * Get one product's data.
     */
    protected function getProduct($id)
    {
        return self::getFactory()->findEntity($id)->getForDisplay();
    }

    /**
     * Get the default entity factory for that responder.
     *
     * @return Nohex\Eix\Services\Data\Factory
     */
    private static function getDefaultFactory()
    {
        return Products::getInstance();
    }

    /**
     * Get the factory that provides this responder with entities.
     */
    public static function getFactory()
    {
        if (empty(self::$factory)) {
            self::$factory = self::getDefaultFactory();
        }

        return self::$factory;
    }

    /**
     * Set the factory that will provide this responder with entities.
     *
     * @param Nohex\Eix\Services\Data\Factory the entity factory to use.
     */
    public static function setFactory(Factory $factory)
    {
        self::$factory = $factory;
    }
}
