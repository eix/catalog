<?php

namespace Nohex\Eix\Modules\Catalog\DataSources;

use Nohex\Eix\Services\Data\Sources\Immutable as ImmutableDataSource;

/**
 * Immutable data source for product groups.
 */
class ProductGroups extends ImmutableDataSource
{
    protected function loadEntities()
    {
        return array(
            array(
                'id' => 'f',
                'name' => _('Fruit'),
            ),
            array(
                'id' => 'v',
                'name' => _('Grocery'),
            ),
            array(
                'id' => 'e',
                'name' => _('Exotic'),
            ),
            array(
                'id' => 'w',
                'name' => _('Packaged'),
            ),
            array(
                'id' => 'p',
                'name' => _('Proximity'),
            ),
        );
    }

}
