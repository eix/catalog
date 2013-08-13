<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\Request;
use Nohex\Eix\Modules\Catalog\Model\Order;
use Nohex\Eix\Modules\Catalog\Model\Orders;
use Nohex\Eix\Services\Data\Entity;
use Nohex\Eix\Services\Data\Responders\CollectionManager;

class OrderManager extends CollectionManager
{
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->setCollectionBrowser(new OrderBrowser($request));
    }

    public function httpPostForHtml()
    {
        $operation = $this->getRequest()->getParameter('operation');

        $response = NULL;
        switch ($operation) {
            case 'archive_selected':
                $response = $this->httpPostArchiveForHtml();
                break;
            default:
                $response = parent::httpPostForHtml();
        }

        return $response;
    }

    public function httpGetArchiveForHtml()
    {
        return $this->getArchivalConfirmationResponse(
                        // There is only one ID, wrap it in an array.
                        (array) $this->getRequest()->getParameter('id')
        );
    }

    /**
     * POST /{collection}/[/:id]
     * POST /{collection}/archive[/]
     *
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpPostArchiveForHtml()
    {
        $isConfirmed = $this->getRequest()->getParameter('confirm') == 1;

        $response = NULL;
        if ($isConfirmed) {
            // If the operation has been confirmed, proceed.
            $response = $this->getArchivalResponse(
                    $this->getRequest()->getParameter('ids')
            );
        } else {
            // If the operation is not confirmed, request confirmation.
            $response = $this->getArchivalConfirmationResponse(
                    $this->getSelectedIds()
            );
        }

        return $response;
    }
    
    protected function getEditionResponse(Entity $entity = null)
    {
        $response = parent::getEditionResponse($entity);
        
        // Add the status labels.
        $response->addData('orders', array('statuses' => Order::getStatusLabels()));
        
        return $response;
    }

    /**
     * Issue a response to an archival process.
     */
    protected function getArchivalResponse(array $selectedIds)
    {
        return $this->getBatchActionResponse(
            array($this, 'archiveOrder'), $selectedIds
        );
    }

    /**
     * Issue a response to an archival process.
     */
    protected function getArchivalConfirmationResponse(array $selectedIds)
    {
        return $this->getBatchActionConfirmationResponse(
            'archive', $selectedIds
        );
    }

    /**
     * Get a posted order's data from the request.
     */
    protected function getEntityDataFromRequest()
    {
        $request = $this->getRequest();

        // Fetch the order from the request.
        return array(
            'id' => $request->getParameter('id'),
            'comments' => $request->getParameter('comments'),
            'vendorComments' => $request->getParameter('vendorComments'),
            'price' => $request->getParameter('price'),
            'weight' => $request->getParameter('weight'),
            'securityCode' => $request->getParameter('securityCode'),
            'status' => $request->getParameter('status'),
        );
    }

    protected function archiveOrder($id)
    {
        $order = Orders::getInstance()->findEntity($id);
        $order->setStatus(Order::STATUS_CLOSED);
        $order->store();
    }
}