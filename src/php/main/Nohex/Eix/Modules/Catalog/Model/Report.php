<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Data\Sources\MongoDB as DataSource;

/**
 * Keeps a record of an event.
 */
class Report extends \Nohex\Eix\Services\Data\Entity
{
    const COLLECTION = 'reports';
    const TYPE_IMPORT = 'import';

    protected $type;
    protected $details;

    protected function getDefaultDataSource()
    {
        return DataSource::getInstance(static::COLLECTION);
    }

    protected function getFactory()
    {
        return Reports::getInstance();
    }

    protected function getFields()
    {
        return array(
            'id',
            'type',
            'details',
        );
    }

    protected function getFieldValidators()
    {
        return array(
            'id' => array('NonEmpty'),
            'type' => array('NonEmpty'),
            'details' => array('NonEmpty'),
        );
    }

}
