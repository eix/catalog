<?php

namespace Nohex\Eix\Modules\Catalog\Responses;

use Nohex\Eix\Modules\Catalog\Model\Customers;

/**
 * HTML response which carries the current customer's basket information.
 */
class Html extends \Nohex\Eix\Core\Responses\Http\Html
{
    protected function setCustomData()
    {
        // If there is a customer, insert its basket into the response data.
        $currentCustomer = Customers::getCurrent();
        if ($currentCustomer instanceof Customer) {
            $this->setData('basket', $this->getBasketContents($currentCustomer));
        }
    }

    /**
     * Groups up several basket properties to be passed to the template.
     */
    private function getBasketContents(Customer $currentCustomer)
    {
        $basket = $currentCustomer->getBasket();

        return array(
            'price' => $basket->getPrice(),
            'weight' => $basket->getWeight(),
            'contents' => $basket->getContents(),
        );
    }
}
