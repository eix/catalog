<?php

namespace Nohex\Eix\Modules\Catalog\Model;

class BasketTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->basket = new Basket(array());
    }

    public function tearDown()
    {
        // $this->basket->empty();
        $this->basket = NULL;
    }

    public function testBasket()
    {
        $this->assertTrue($this->basket instanceof Basket);
    }
}
