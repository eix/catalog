<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Modules\Catalog\Model\Order;

class OrderTest extends \PHPUnit_Framework_TestCase
{
    private $order;

    public function setUp()
    {
        // Create an order.
        $this->order = new Order(array(
            'customer' => MockCustomer::getNew(),
        ));
    }

    public function tearDown()
    {
        // $this->basket->empty();
        $this->order = NULL;
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructorNoArguments()
    {
        new Order; // Tainted love.
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEmptyConstructor()
    {
        new Order(array());
    }

    public function testEmptyOrder()
    {
        $this->assertTrue($this->order instanceof Order);
        $this->assertEquals($this->order->getPrice(), 0);
        $this->assertEquals($this->order->getWeight(), 0);
    }
}
