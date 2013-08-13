<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Modules\Catalog\Model\Products;
use Nohex\Eix\Modules\Catalog\Responders\ProductBrowser;
use Nohex\Eix\Core\Responses\Http\Html as HtmlResponse;

/**
 * Displays a product list.
 */
class FeaturedProductsBrowser extends ProductBrowser
{
    /**
     * GET /products/featured
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpGetForHtml()
    {
        $response = new HtmlResponse($this->getRequest());

        $productList = $this->getFeaturedProductsList();
        if (empty($productList)) {
            $response->setTemplateId('products/featured/empty');
        } else {
            $response->setTemplateId('products/featured/index');
            $response->setData('products', $productList);
        }

        $response->appendToTitle(_('Productes'));

        return $response;
    }

    /**
     * Get a list of all featured products.
     */
    protected function getFeaturedProductsList()
    {
        $options = array();
        // If disabled products are not meant to be included, include just the
        // enabled ones.
        $products = Products::getInstance()->getAll(array(
            'featured' => true,
            'enabled' => true,
        ));

        $productList = array();
        if (!empty($products)) {
            $productList = array();
            foreach ($products as $product) {
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
                    'price_kg' => $product->getPricePerKg(),
                    'enabled' => $product->enabled,
                );
            }
        }

        return $productList;
    }
}
