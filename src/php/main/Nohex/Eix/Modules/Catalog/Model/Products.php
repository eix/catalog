<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Data\Factory;
use Nohex\Eix\Services\Data\Sources\MongoDB as DataSource;

/**
 * Provides access to products data.
 */
class Products extends Factory
{
    const COLLECTION = 'products';
    const ENTITIES_CLASS_NAME = '\\Nohex\\Eix\\Modules\\Catalog\\Model\\Product';

    protected function assignDataSource()
    {
        $this->dataSource = DataSource::getInstance(static::COLLECTION);
    }
}
