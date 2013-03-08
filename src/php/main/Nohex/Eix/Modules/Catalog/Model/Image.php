<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Data\Sources\ImageStore as DataSource;
use Nohex\Eix\Services\Log\Logger;

/**
 * Representation of an image.
 *
 * The lack of fields other than the ID in this class is to avoid the image
 * data to be kept in memory at any time. Instead, the image should be retrieved
 * only when needed.
 */
abstract class Image extends \Nohex\Eix\Services\Data\Entity
{
    const COLLECTION = 'images';
    const IMAGE_SIZE = 140;

    protected $location;
    // Stores whether the path in $location points to a temporary file. TRUE if
    // the file is temporary, FALSE if the file is the stored resource.
    private $isLocationTemporary = TRUE;

    /**
     * @param array $data there needs to be an 'id' parameter and either a
     * 'location' or a 'source' parameters; 'location' points to an existing
     * file or resource (such as an URL), which will only be referred to,
     * whereas 'source' points to a location that needs to be imported, such as
     * a temporary file, a URL, or a string with the image binary content.
     * @throws \RuntimeException
     */
    public function __construct(array $data, $isNew = TRUE)
    {
        if (empty($data['id'])) {
            throw new \RuntimeException(
                'An ID is needed to create a new image.'
            );
        }

        $source = @$data['source'];
        $location = @$data['location'];
        if ($location) {
            $this->isLocationTemporary = FALSE;
        } elseif ($source) {
            // If a source is specified, the image is new or is meant to replace
            // an existing one.

            // a temporary location to be moved later to the persistence layer,
            // if need be.

            // Create a new temporary location.
            $this->location = self::getNewLocation();
            if (is_resource($source)) {
                $result = !!file_put_contents($this->location, $source);
                // Close the resource.
                fclose($source);
            } elseif (is_readable($source)) {
                // The source is a readable path, so the image is copied to a
                // temporary location.
                copy($source, $this->location);
            } else {
                // If the source is not a file, it is assumed that the image is
                // a string or a stream. It is then committed to a temporary
                // file.
                file_put_contents($this->location, $source);
            }
        } else {
            // If neither source nor location have been set, the image cannot
            // be created.
            throw new \RuntimeException(
                'Either a source or a location are needed to create an image.'
            );
        }

        // Remove the source from the data, in case it is a string holding the
        // image, which we don't want to keep in memory. At any rate, the image
        // is now referred to by $this->location.
        unset($data['source']);

        // Set the image data.
        parent::__construct($data, $isNew);
    }

    public function __destruct()
    {
        // If the image is a temporary file, delete it.
        if ($this->isLocationTemporary) {
            @unlink($this->location);
            Logger::get()->debug(
                "The image in {$this->location} has been deleted."
            );
        }
    }

    protected function assignDataSource()
    {
        $this->dataSource = DataSource::getInstance(static::COLLECTION);
    }

    protected function getFields()
    {
        return array(
            'id',
            'location',
        );
    }

    protected function getFieldValidators()
    {
        return array(
            'id' => array('NonEmpty'),
        );
    }

    private static function getNewLocation()
    {
        return sys_get_temp_dir() . '/eix-image-' . uniqid();
    }

    public function store()
    {
        parent::store();

        // Set the new location.
        $this->location = $this->getDataSource()->getAssetLocation($this->id);
        // Once stored, the image is no longer temporary.
        $this->isLocationTemporary = FALSE;
    }
}
