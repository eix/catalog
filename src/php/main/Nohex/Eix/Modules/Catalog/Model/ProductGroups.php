<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Modules\Catalog\DataSources\ProductGroups as DataSource;

/**
 * Provides access to product groups.
 */
class ProductGroups extends \Nohex\Eix\Services\Data\Factory
{
    const ENTITIES_CLASS_NAME = '\\Nohex\\Eix\\Modules\\Catalog\\Model\\ProductGroup';

    protected function getDefaultDataSource()
    {
        return DataSource::getInstance();
    }

}
