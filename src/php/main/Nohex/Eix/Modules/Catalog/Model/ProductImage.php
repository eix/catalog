<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Data\Sources\ImageStore as DataSource;
use Nohex\Eix\Modules\Catalog\Model\Image;
/**
 * Representation of an image associated with a product.
 */
class ProductImage extends Image
{
    const COLLECTION = 'products';

    protected function getDefaultDataSource()
    {
        return DataSource::getInstance(static::COLLECTION);
        // Set alternative image sizes.
        $this->dataSource->setAlternativeImageSizes(array(
            32,
            96,
            140
        ));
    }

    protected function getFactory()
    {
        return ProductImages::getInstance();
    }
}
