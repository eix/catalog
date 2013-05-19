<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\Request;
use Nohex\Eix\Modules\Catalog\Model\Product;
use Nohex\Eix\Modules\Catalog\Model\ProductGroups;
use Nohex\Eix\Modules\Catalog\Model\ProductImage;
use Nohex\Eix\Modules\Catalog\Model\Products;
use Nohex\Eix\Modules\Catalog\Model\Report;
use Nohex\Eix\Modules\Catalog\Responses\Order as HtmlResponse;
use Nohex\Eix\Services\Data\Responders\CollectionManager;
use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Net\Http\NotFoundException;
use Nohex\Eix\Services\Net\Http\BadRequestException;

/**
 * Manages a product list.
 */
class ProductManager extends CollectionManager
{
    const COLLECTION_NAME = 'products';
    const ITEM_NAME = 'product';
    const ITEM_CLASS = '\\Nohex\\Eix\\Modules\\Catalog\\Model\\Product';

    public function getCollectionName()
    {
        return static::COLLECTION_NAME;
    }

    public function getItemName()
    {
        return static::ITEM_NAME;
    }

    protected function getHtmlResponse(Request $request)
    {
        return new HtmlResponse($request);
    }

    protected function getEntityClass()
    {
        return new ReflectionClass(static::ITEM_CLASS);
    }

    /**
     * Get a product.
     */
    protected function getEntity($id)
    {
        // Fetch the product from the ID.
        $product = Products::getInstance()->findEntity($id);

        return $product;
    }

    /**
     * Get a list of the selected products.
     */
    protected function getEntities($view = NULL)
    {
        return Products::getInstance()->getAll();
    }

    protected function destroyEntity($id)
    {
        Products::getInstance()->findEntity($id)->destroy();
    }

    /**
     * Get a posted product's data from the request.
     */
    protected function getEntityDataFromRequest()
    {
        $request = $this->getRequest();

        $groupIds = $request->getParameter('groups') ?: array();
        $groups = array();
        foreach ($groupIds as $groupId) {
            try {
                $groups[] = ProductGroups::getInstance()->findEntity($groupId);
            } catch (NotFoundException $exception) {
                Logger::get()->warning(sprintf(
                    'Product group ID "%s" not known.',
                    $groupId
                ));
            }
        }

        // Fetch the product from the request.
        return array(
            'id' => $request->getParameter('id'),
            'name' => $request->getParameter('name'),
            'description' => $request->getParameter('description'),
            'enabled' => $request->getParameter('enabled') == 'on',
            'featured' => $request->getParameter('featured') == 'on',
            'price' => $request->getParameter('price'),
            'weight' => $request->getParameter('weight'),
            'presentation' => $request->getParameter('presentation'),
            'groups' => $groups,
        );
    }

    /**
     * Enables a product.
     * @param string $id the ID of the product to enable.
     */
    protected function enableProduct($id)
    {
        $product = Products::getInstance()->findEntity($id);
        $product->enable();
        $product->store();
    }

    /**
     * Disables a product.
     * @param string $id the ID of the product to disable.
     */
    protected function disableProduct($id)
    {
        $product = Products::getInstance()->findEntity($id);
        $product->disable();
        $product->store();
    }

    /**
     * Promotes a product, or marks it as featured.
     * @param string $id the ID of the product to promote.
     */
    protected function promoteProduct($id)
    {
        $product = Products::getInstance()->findEntity($id);
        $product->promote();
        $product->store();
    }

    /**
     * Removes a product from the featured set.
     * @param string $id the ID of the product to demote.
     */
    protected function demoteProduct($id)
    {
        $product = Products::getInstance()->findEntity($id);
        $product->demote();
        $product->store();
    }

    public function httpGetForHtml()
    {
        $id = $this->getRequest()->getParameter('id');

        $response = NULL;
        switch ($id) {
            case 'import':
                $response = $this->httpGetImportForHtml();
                break;
            default:
                $response = parent::httpGetForHtml();
                $response->setData('groups', $this->getGroups());
        }

        return $response;
    }

    public function httpPostForHtml()
    {
        $operation = $this->getRequest()->getParameter('operation');

        $response = NULL;
        switch ($operation) {
            case 'enable_selected':
                $response = $this->httpPostEnableForHtml();
                break;
            case 'disable_selected':
                $response = $this->httpPostDisableForHtml();
                break;
            case 'promote_selected':
                $response = $this->httpPostPromoteForHtml();
                break;
            case 'demote_selected':
                $response = $this->httpPostDemoteForHtml();
                break;
            default:
                $response = parent::httpPostForHtml();
                $response->setData('groups', $this->getGroups());
        }

        return $response;
    }

    public function httpPostImportForHtml()
    {
        return $this->getImportResultsResponse();
    }

    /**
     * POST /{collection}/[/:id]
     *  +- operation: disable
     * POST /{collection}/disable[/]
     *
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpPostDisableForHtml()
    {
        $isConfirmed = $this->getRequest()->getParameter('confirm') == 1;

        $response = NULL;
        if ($isConfirmed) {
            // If the operation has been confirmed, proceed.
            $response = $this->getDisabilitationResponse(
                $this->getRequest()->getParameter('ids')
            );
        } else {
            // If the operation is not confirmed, request confirmation.
            $response = $this->getDisabilitationConfirmationResponse(
                $this->getSelectedIds()
            );
        }

        return $response;
    }


    /**
     * POST /{collection}/[/:id]
     *  +- operation: enable
     * POST /{collection}/enable[/]
     *
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpPostEnableForHtml()
    {
        $isConfirmed = $this->getRequest()->getParameter('confirm') == 1;

        $response = NULL;
        if ($isConfirmed) {
            // If the operation has been confirmed, proceed.
            $response = $this->getAbilitationResponse(
                $this->getRequest()->getParameter('ids')
            );
        } else {
            // If the operation is not confirmed, request confirmation.
            $response = $this->getAbilitationConfirmationResponse(
                $this->getSelectedIds()
            );
        }

        return $response;
    }

    private function getImportResultsResponse()
    {
        $response = NULL;

        try {
            $importId = $this->importProducts();
            // The import was successful. Redirect to the status page.
            $response = new \Nohex\Eix\Core\Responses\Http\Redirection($this->getRequest());
            $response->setNextUrl("/reports/{$importId}");
        } catch (\Exception $exception) {
            // The import failed.
            $response = new \Nohex\Eix\Core\Responses\Http\Html($this->getRequest());
            $response->setTemplateId('products/import');
            // Set the error details.
            $response->addErrorMessage($exception->getMessage());
        }

        return $response;
    }

    /**
     * Request confirmation to enable.
     */
    protected function getAbilitationResponse(array $selectedIds)
    {
        return $this->getBatchActionResponse(
            array($this, 'enableProduct'),
            $selectedIds
        );
    }

    /**
     * Request confirmation to disable.
     */
    protected function getDisabilitationResponse(array $selectedIds)
    {
        return $this->getBatchActionResponse(
            array($this, 'disableProduct'),
            $selectedIds
        );
    }

    /**
     * Respond to a disabilitation confirmation.
     */
    protected function getDisabilitationConfirmationResponse(array $selectedIds)
    {
        return $this->getBatchActionConfirmationResponse(
            'disable',
            $selectedIds
        );
    }

    /**
     * Respond to an abilitation confirmation.
     */
    protected function getAbilitationConfirmationResponse(array $selectedIds)
    {
        return $this->getBatchActionConfirmationResponse(
            'enable',
            $selectedIds
        );
    }

    /**
     * POST /{collection}/[/:id]
     *  +- operation: promote
     * POST /{collection}/promote[/]
     *
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpPostPromoteForHtml()
    {
        // If the operation is not confirmed, request confirmation.
        $response = $this->getPromotionResponse(
            $this->getSelectedIds()
        );

        return $response;
    }

    /**
     * Promote the selected products.
     */
    protected function getPromotionResponse(array $selectedIds)
    {
        return $this->getBatchActionResponse(
            array($this, 'promoteProduct'),
            $selectedIds
        );
    }

    /**
     * POST /{collection}/[/:id]
     *  +- operation: demote
     * POST /{collection}/demote[/]
     *
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpPostDemoteForHtml()
    {
        // If the operation is not confirmed, request confirmation.
        $response = $this->getDemotionResponse(
            $this->getSelectedIds()
        );

        return $response;
    }

    /**
     * Demote the selected products.
     */
    protected function getDemotionResponse(array $selectedIds)
    {
        return $this->getBatchActionResponse(
            array($this, 'demoteProduct'),
            $selectedIds
        );
    }

////////////////////////////////////////////////

    public function httpGetImportForHtml()
    {
        $response = new \Nohex\Eix\Core\Responses\Http\Html($this->getRequest());
        $response->setTemplateId('products/import');

        return $response;
    }


    /**
     * Runs the product import process.
     *
     * @return string the ID of the import record.
     */
    private function importProducts()
    {
        $file = $this->getRequest()->getParameter('products_file');
        $fileName = $file['tmp_name'];
        if (is_readable($fileName)) {
            $file = fopen($fileName, 'r');

            $importedIds = array();
            $count = 0;
            // Ignore the first line, it's the header.
            $record = fgetcsv($file);

            while (!feof($file)) {
                $record = fgetcsv($file);
                if (is_array($record)) {
                    Logger::get()->info('Importing record: ' . implode(',', $record));
                    // Assign the record's fields to their variables.
                    list(
                        $id,
                        $name,
                        $description,
                        $price,
                        $weight,
                        $precision, // Left for backwards compatibility.
                        $presentation,
                        $isGroupF,
                        $isGroupV,
                        $isGroupP,
                        $isGroupW,
                        $isGroupE,
                        $imageUrl
                    ) = $record;

                    try {
                        // Proceed only if the required fields are present.
                        if ($id && $name && $price && $weight) {
                            $data = array(
                                'id' => $id,
                                'name' => $name,
                                'description' => $description ?: _('(Sense descripció)'),
                                'price' => $price,
                                'weight' => $weight,
                                'presentation' => $presentation ?: _('Unitat'),
                            );
                            $product = NULL;
                            try {
                                // Get the product from the factory.
                                $product = Products::getInstance()->findEntity($id);
                                // It's there, update it.
                                Logger::get()->info("Updating product {$id}: {$name}...");
                                $product->update($data);
                            } catch (NotFoundException $exception) {
                                Logger::get()->info("Creating product {$id}: {$name}...");
                                // It's not there, create a new one.
                                $product = new Product($data);
                            }

                            // Decode groups.
                            $groupIdArray = array();
                            if ($isGroupF) {
                                $groupIdArray[] = 'f';
                            }
                            if ($isGroupV) {
                                $groupIdArray[] = 'v';
                            }
                            if ($isGroupP) {
                                $groupIdArray[] = 'p';
                            }
                            if ($isGroupW) {
                                $groupIdArray[] = 'w';
                            }
                            if ($isGroupE) {
                                $groupIdArray[] = 'e';
                            }
                            $productGroups = ProductGroups::getInstance();
                            foreach ($groupIdArray as $groupId) {
                                $productGroup = $productGroups->findEntity($groupId);
                                $product->addToGroup($productGroup);
                            }
                            // Store the product.
                            $product->store();

                            // Open a stream to read the image, in case it's a
                            // URL.
                            if ($imageUrl) {
                                $imageHandle = @fopen($imageUrl, 'r');
                                if ($imageHandle) {
                                    // Store image.
                                    $image = new ProductImage(array(
                                        'id' => $id,
                                        'source' => $imageHandle,
                                    ));
                                    $image->store();
                                }
                            }

                            // Record the ID of the successfully imported product.
                            $importedIds[] = $product->getId();
                        } else {
                            throw new \InvalidArgumentException('Missing data.');
                        }

                    } catch (\Exception $exception) {
                        Logger::get()->warning("Could not import record: {$exception->getMessage()}\n\t" . @join(', ', $record));
                    }
                }
            }

            // Generate a record of the import.
            $details = count($importedIds) > 0
                ? _('The following products have been imported: ') . join(', ', $importedIds)
                : _('No products have been imported.')
            ;
            $importRecord = new Report(array(
                'type' => Report::TYPE_IMPORT,
                'details' => $details,
            ));
            $importRecord->store();

            // Return the import log ID.
            return $importRecord->getId();
        } else {
            throw new BadRequestException('Uploaded file cannot be used: ' . $fileName);
        }
    }

    /**
     * Returns a list of all available product groups.
     */
    private function getGroups()
    {
        $productGroups = ProductGroups::getInstance()->getAll();

        return array_map(function ($group) {
            return $group->getFieldsData();
        }, $productGroups);
    }

    /**
     * Stores the product-related data in the request.
     */
    protected function storeEntity()
    {
        $request = $this->getRequest();
        $id = $request->getParameter('id');
        // Find the product.
        $product = NULL;
        try {
            $product = Products::getInstance()->findEntity($id);
        } catch (NotFoundException $exception) {
            // Not found? Create it.
            $product = new Product(array(
                'id' => $id,
            ));
        }

        // Update the product from the request data.
        $product->update($this->getEntityDataFromRequest());
        // Store the image, if there's one.
        $pictureData = $this->getRequest()->getParameter('picture');
        $this->storeImage(
            $product->getId(),
            $pictureData['tmp_name']
        );
        // Store the updated product.
        $product->store();

        return $product->getFieldsData();
    }

    /**
     * Stores an uploaded image and puts a reference to it in the product.
     *
     * @param string $id       the ID of the picture.
     * @param string $location the location of the file which holds the picture.
     */
    private function storeImage($id, $location)
    {
        if ($location && is_readable($location)) {
            $image = new ProductImage(array(
                'id' => $id,
                'location' => $location,
            ));
            $image->store();

            return TRUE;
        } else {
            // No picture found, so no updates performed.
            return FALSE;
        }
    }
}
