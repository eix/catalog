<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\Responders\Http as HttpResponder;
use Nohex\Eix\Modules\Catalog\Model\Products;
use Nohex\Eix\Modules\Catalog\Model\ProductGroups;
use Nohex\Eix\Modules\Catalog\Responses\Html as HtmlResponse;
use Nohex\Eix\Services\Net\Http\NotFoundException;

/**
 * Displays a product list.
 */
class ProductViewer extends HttpResponder
{
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
        $products = Products::getInstance()->getAll($options);

        $productList = array();
        if (!empty($products)) {
            $productList = array();
            foreach ($products as $product) {
                if (!$groupId || @$product->groups[$groupId]) {
                    // Truncate the description if it is too long.
                    $description = $product->description;
                    if (strlen($description) > 150) {
                        $description = substr($description, 0, 147) . '...';
                    }
                    $productList[] = array(
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $description,
                        'price' => $product->price,
                        'weight' => $product->weight,
                        'enabled' => $product->enabled,
                    );
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
        $product = Products::getInstance()->findEntity($id);
        $productData = $product->getFieldsData();

        // Add the price per kg.
        $productData['price_per_kg'] = sprintf('%1.2f',
            $product->price / $product->weight
        );

        return $productData;
    }
}
