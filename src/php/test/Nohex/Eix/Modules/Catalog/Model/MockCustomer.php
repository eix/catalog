<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Modules\Catalog\Model\Customer;

/**
 * Fake customer for test purposes.
 */
class MockCustomer extends Customer
{

    public static function getNew()
    {
        return new Customer(array());
    }
}
