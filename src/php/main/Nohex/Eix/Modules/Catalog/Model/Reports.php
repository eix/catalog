<?php

namespace Nohex\Eix\Modules\Catalog\Model;

/**
 * Provides access to reports data.
 */
class Reports extends \Nohex\Eix\Services\Data\Factory
{
    const COLLECTION = 'reports';
    const ENTITIES_CLASS_NAME = '\\Nohex\\Eix\\Modules\\Catalog\\Model\\Report';

    protected function getDefaultDataSource()
    {
        return \Nohex\Eix\Services\Data\Sources\MongoDB::getInstance(static::COLLECTION);
    }
}
