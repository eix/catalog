<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Data\Entity;
use Nohex\Eix\Services\Data\Sources\MongoDB as DataSource;

/**
 * Representation of a product.
 */
class ProductGroup extends Entity
{
    const COLLECTION = 'products';

    protected $name;

    protected $products;

    protected function getDefaultDataSource()
    {
        return DataSource::getInstance(static::COLLECTION);
    }

    protected function getFactory()
    {
        return ProductGroups::getInstance();
    }

    protected function getFields()
    {
        return array(
            'id',
            'name',
        );
    }

    protected function getFieldValidators()
    {
        return array(
            'id' => array('NonEmpty'),
            'name' => array('NonEmpty'),
        );
    }

    public function getForDisplay()
    {
        return array(
            'id' => $this->id,
            'name' => _($this->name),
        );
    }

    /**
     * Add a product to this group.
     */
    public function addProduct(Product $product)
    {
        $this->products[$product->id] = $product;
    }

    /**
     * Remove a product from this group.
     */
    public function removeProduct(Product $product)
    {
        if (@$this->products[$product->id]) {
            unset($this->products[@$product->id]);
        }
    }
}
