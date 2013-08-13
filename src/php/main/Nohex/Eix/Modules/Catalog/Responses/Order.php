<?php

namespace Nohex\Eix\Modules\Catalog\Responses;

use Nohex\Eix\Modules\Catalog\Model\Order as OrderModel;
use Nohex\Eix\Modules\Catalog\Responses\Html as HtmlResponse;

/**
 * Provides a response tailored to the order view.
 */
class Order extends HtmlResponse
{
    public function issue()
    {
        // Load the statuses map into the response.
        $this->addData('orders', array(
            'statuses' => OrderModel::getStatusLabels()
        ));

        parent::issue();
    }

}
