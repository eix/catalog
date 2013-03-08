<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Data\Sources\MongoDB as DataSource;

/**
 * Representation of a product.
 */
class ProductGroup extends \Nohex\Eix\Services\Data\Entity
{
    const COLLECTION = 'products';

    protected $name;

    protected $products;

    protected function assignDataSource()
    {
        $this->dataSource = DataSource::getInstance(static::COLLECTION);
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
