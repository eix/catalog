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
                'name' => _('Fruita'),
            ),
            array(
                'id' => 'v',
                'name' => _('Verdura'),
            ),
            array(
                'id' => 'e',
                'name' => _('Exòtics'),
            ),
            array(
                'id' => 'w',
                'name' => _('Envasats'),
            ),
            array(
                'id' => 'p',
                'name' => _('Proximitat'),
            ),
        );
    }

}
