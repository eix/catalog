<?php

namespace Nohex\Eix\Modules\Catalog\Model\Reports;

/**
 * Keeps a record of a product import.
 */
class Import extends \Nohex\Eix\Modules\Catalog\Model\Report
{
    protected $type = 'import';
    protected $products = array();

    protected function getFields()
    {
        return array(
            'id',
            'type',
            'products',
        );
    }

}
