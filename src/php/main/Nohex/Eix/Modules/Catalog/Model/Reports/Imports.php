<?php

namespace Nohex\Eix\Modules\Catalog\Model\Reports;

/**
 * Provides access to imports records.
 */
class Imports extends \Nohex\Eix\Services\Data\Factory
{
    const COLLECTION = 'reports';
    const ENTITIES_CLASS_NAME = '\\Nohex\\Eix\\Modules\\Catalog\\Model\\Records\\Import';

    protected function assignDataSource()
    {
        $this->dataSource = \Nohex\Eix\Services\Data\Sources\MongoDB::getInstance(static::COLLECTION);
    }
}
