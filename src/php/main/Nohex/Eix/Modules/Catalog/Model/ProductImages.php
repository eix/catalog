<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Data\Sources\ImageStore as DataSource;

/**
 * Provides access to product images.
 */
class ProductImages extends \Nohex\Eix\Services\Data\Factory
{
    const COLLECTION = 'products';
    const ENTITIES_CLASS_NAME = '\\Nohex\\Eix\\Modules\\Catalog\\Model\\ProductImage';

    protected function getDefaultDataSource()
    {
        return DataSource::getInstance(static::COLLECTION);
    }
}
