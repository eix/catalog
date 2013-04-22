<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Modules\Catalog\Model\Product;
use Nohex\Eix\Modules\Catalog\Model\Products;
use Nohex\Eix\Services\Data\Subentity;
use Nohex\Eix\Services\Log\Logger;

/**
 * Representation of a user's product basket. Since a basket only exists as
 * customers assets, they are implemented as subentities.
 */
class Basket extends Subentity
{
    protected $products = array();
    protected $forDisplay = array();

    private $contents = array();
    private $price = 0;
    // Weight is not zeroed to tell apart empty baskets from ones with products
    // which have no weight member.
    private $weight = null;

    protected function getFields()
    {
        return array(
            'id',
            'products',
        );
    }

    protected function getFieldValidators()
    {
        return array();
    }

    /**
     * A basket is serialised only as the relation of products and their counts.
     */
    public function getFieldsData()
    {
        return $this->products;
    }

    public function getForDisplay()
    {
        if (empty($this->forDisplay)) {
            // Get the products' displayable data.
            $this->forDisplay = array('products' => array());
            foreach ($this->products as $product) {
                $this->forDisplay['products'][] = $product->getForDisplay();
            }
        }

        return $this->forDisplay;
    }

    /**
     * Adds a product to the basket.
     *
     * @param Product $product
     * @paramt $count how many units should be added.
     */
    public function add(Product $product, $count = 1)
    {
        $productId = $product->id;
        Logger::get()->debug(
            "Adding product {$productId} to basket."
        );
        // Add the product reference if not present.
        if (empty($this->products[$productId])) {
            $this->products[$productId] = 0;
        }
        // Update product count.
        $this->products[$productId] += $count;
        // Update total price.
        $this->price += $product->price * $count;
        // Update total weight.
        if (property_exists($product, 'weight')) {
            $this->weight += $product->weight * $count;
        }

        $this->invalidateCalculatedFields();

        Logger::get()->debug('Added');
    }

    /**
     * Removes a product from the basket.
     *
     * @param Product $product
     */
    public function remove(Product $product)
    {
        $productId = $product->id;
        if (isset($this->products[$productId])) {
            Logger::get()->debug(
                "Removing product {$productId} from basket."
            );
            // Update product count.
            $this->products[$productId]--;
            // If there are no products, remove the reference.
            if ($this->products[$productId] === 0) {
                unset($this->products[$productId]);
            }
            // Update total price.
            $this->price -= $product->price;
            // Update total weight.
            if (property_exists($product, 'weight')) {
                $this->weight -= $product->weight;
            }

            $this->invalidateCalculatedFields();

            Logger::get()->debug('Removed');
        }
    }

    /**
     * Removes all products from the basket.
     */
    public function clear()
    {
        $this->products = array();
        $this->price = 0;
        // Weight is not zeroed, see member declaration.
        $this->weight = null;

        $this->invalidateCalculatedFields();
    }

    /**
     * Sets the products of the basket.
     *
     * @param  Product[]                 $products
     * @throws \IllegalArgumentException
     */
    public function fill(array $products)
    {
        // First make sure that the product list is valid.
        foreach ($products as $product) {
            if (!($product instanceof Product)) {
                throw new \IllegalArgumentException(
                    'The Basket can only carry Products.'
                );
            }
        }
        // Clear the basket prior to filling it up.
        $this->clear();
        // Fill the basket up with the new products.
        foreach ($products as $product) {
            $this->add($product);
        }

        $this->invalidateCalculatedFields();
    }

    /**
     * Provides a list of Product objects that reflect the contents of the
     * basket.
     *
     * @return Product[] the returned Product objects have been injected with
     * a 'count' property which details how many of those products the basket
     * holds.
     */
    public function getContents()
    {
        if (empty($this->contents)) {
            foreach ($this->products as $id => $count) {
                // Get the product entity.
                $product = Products::getInstance()->findEntity($id);
                $this->contents[$id] = $product->getFieldsData();
                // Inject the product metadata in the entity.
                $this->contents[$id]['count'] = $count;
                $this->contents[$id]['totalPrice'] = $product->price * $count;
                if (property_exists($product, 'weight')) {
                    $this->contents[$id]['totalWeight'] = $product->weight * $count;
                }
            }
        }

        return $this->contents;
    }

    /**
     * Clear fields that depend on variable data such as the product list.
     */
    public function invalidateCalculatedFields()
    {
        $this->contents = null;
        $this->displayData = null;
    }

    /**
     * Gets the current total price of the basket.
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Gets the current total weight of the basket.
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Details how many units of a particular product can currently be found in
     * the basket.
     * @param string $productId
     */
    public function getCount($productId)
    {
        return @$this->products[$productId] ?: 0;
    }

    public function isEmpty()
    {
        return empty($this->products);
    }
}
