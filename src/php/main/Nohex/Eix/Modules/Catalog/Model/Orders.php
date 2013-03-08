<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Data\Sources\MongoDB as DataSource;

/**
 * Provides access to orders data.
 */
class Orders extends \Nohex\Eix\Services\Data\Factory
{
    const COLLECTION = 'orders';
    const ENTITIES_CLASS_NAME = '\\Nohex\\Eix\\Modules\\Catalog\\Model\\Order';

    protected function assignDataSource()
    {
        $this->dataSource = DataSource::getInstance(static::COLLECTION);
    }
}
