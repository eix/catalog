<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Data\Entity;
use Nohex\Eix\Services\Data\Sources\MongoDB as DataSource;

/**
 * Representation of a product.
 */
class Product extends Entity
{
    const COLLECTION = 'products';

    protected $name;
    protected $description;
    protected $price;
    protected $enabled = true;
    protected $featured = FALSE;
    protected $groups = array();

    public function update(array $data, $isAtomic = true)
    {
        parent::update($data, $isAtomic);

        // Set the groups.
        if (!empty($data['groups'])) {
            $this->groups = array();
            foreach ($data['groups'] as $key => $group) {
                if (!($group instanceof ProductGroup)) {
                    $productGroup = ProductGroups::getInstance()->findEntity($group['id']);

                    $group = $productGroup;
                }
                // Keep the new entity.
                $this->groups[$group->id] = $group;
            }
        }

        // Invalidate calculated fields.
        $this->pricePerKg = null;
    }

    protected function getDefaultDataSource()
    {
        return DataSource::getInstance(static::COLLECTION);
    }

    protected function getFactory()
    {
        return Products::getInstance();
    }

    protected function getFields()
    {
        return array(
            'id',
            'name',
            'description',
            'enabled',
            'featured',
            'price',
            'groups',
        );
    }

    public function getForDisplay()
    {
        $groupsForDisplay = array();
        foreach ($this->groups as $group) {
            $groupsForDisplay[$group->getId()] = $group->getForDisplay();
        }

        return array(
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'enabled' => $this->enabled,
            'groups' => $groupsForDisplay,
        );
    }

    protected function getFieldValidators()
    {
        return array(
            'id' => array('NonEmpty'),
            'name' => array('NonEmpty'),
            'description' => array('NonEmpty'),
            'price' => array('NonEmpty', 'Number'),
        );
    }

    /**
     * Make this product part of the specified group.
     * @param ProductGroup $group
     */
    public function addToGroup(ProductGroup $group)
    {
        if (!in_array($group, $this->groups)) {
            // Reference the group in the product.
            $this->groups[] = $group;
            // Reference the product in the group.
            $group->addProduct($this);
        }
    }

    /**
     * Remove this product from the specified group.
     * @param  ProductGroup      $group
     * @throws \RuntimeException if the product is only linked to one group.
     */
    public function removeFromGroup(ProductGroup $group)
    {
        // Remove the reference from this product.
        foreach ($this->groups as $index => $existingGroup) {
            if ($existingGroup == $group) {
                unset($this->groups[$index]);
                break;
            }
        }
        // Remove the product reference from the group.
        $group->removeProduct($this);
    }

    /**
     * Set this product's groups.
     * @param  array                     $groups
     * @throws \InvalidArgumentException if a group in the array is not valid.
     */
    public function setGroups(array $groups)
    {
        if (count($groups)) {
            foreach ($groups as $group) {
                if (!($group instanceof ProductGroup)) {
                    // One wrong group invalidates the groups array.
                    throw new \InvalidArgumentException('The product group is not valid.');
                }
            }
            $this->groups = $groups;
        }
    }

    /**
     * Allows the product to be displayed and used.
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Prevents the product from being displayed or used.
     */
    public function disable()
    {
        $this->enabled = FALSE;
    }

    /**
     * Sets a product as featured.
     */
    public function promote()
    {
        $this->featured = true;
    }

    /**
     * Marks the product as not featured.
     */
    public function demote()
    {
        $this->featured = FALSE;
    }

    /**
     * Returns a list of sizes a product image can be.
     */
    public static function getImageSizes()
    {
        return array(
            32,
            96,
            140,
        );
    }
}
