<?php

namespace Nohex\Eix\Modules\Catalog\Model;

class ProductTest extends \PHPUnit_Framework_TestCase
{
    private $productData = array(
        'id' => 1,
        'name' => 'Name',
        'price' => 2.5,
        'weight' => 0.5,
    );

    public function testModelConstructor()
    {
        $product = new Product($this->productData);
        $this->assertTrue($product instanceof Product);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testModelEmptyConstructor()
    {
        $product = new Product;
    }

    public function testProperties()
    {
        $product = new Product($this->productData);

        $this->assertEquals($product->id, $this->productData['id']);
        $this->assertEquals($product->name, $this->productData['name']);

        foreach ($this->productData as $property => $value) {
            if ($product->$property != $value) {
                $this->fail("Property '$property' is not correctly set.");
            } else {
                return;
            }
        }
    }

    public function testGroups()
    {
        $groups = array(
            ProductGroups::getInstance()->findEntity('f'),
            ProductGroups::getInstance()->findEntity('v'),
        );

        $product = new Product($this->productData);
        $product->setGroups($groups);
        $productGroups = $product->groups;
        $this->assertTrue(count($productGroups) == count($groups));
        $this->assertTrue($productGroups[0]->getId() == $groups[0]->getId());
        $this->assertTrue($productGroups[1]->getId() == $groups[1]->getId());
    }

    public function testPricePerKg()
    {
        $product = new Product($this->productData);

        $this->assertEquals($product->getPricePerKg(), 5);
    }
}
