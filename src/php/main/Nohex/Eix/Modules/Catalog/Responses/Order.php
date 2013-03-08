<?php

namespace Nohex\Eix\Modules\Catalog\Responses;

use Nohex\Eix\Modules\Catalog\Model\Order;

/**
 * Provides a response tailored to the order view.
 */
class Order extends \Nohex\Eix\Modules\Catalog\Responses\Html
{
    public function issue()
    {
        // Load the statuses map into the response.
        $this->addData('orders', array(
            'statuses' => Order::getStatuses()
        ));

        parent::issue();
    }

}
