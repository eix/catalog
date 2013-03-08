<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\Request;
use Nohex\Eix\Services\Data\Responders\CollectionManager;
use Nohex\Eix\Modules\Catalog\Model\Orders;
use Nohex\Eix\Modules\Catalog\Model\Order;
use Nohex\Eix\Modules\Catalog\Responses\Order as HtmlResponse;

class OrderManager extends CollectionManager
{
    const COLLECTION_NAME = 'orders';
    const ITEM_NAME = 'order';
    const ITEM_CLASS = '\\Nohex\\Eix\\Modules\\Catalog\\Model\\Order';

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
     * Get an order, first checking if the current user can view it.
     */
    protected function getEntity($id)
    {
        // Fetch the order from the ID.
        $order = Orders::getInstance()->findEntity($id);
        // Find the customer whom the order belongs to.
        $orderCustomerId = $order->getCustomer()->getId();

        // Everything looks fine, return the order.
        return $order;
    }

    /**
     * Get a list of the selected orders.
     */
    protected function getEntities($view = NULL)
    {
        $filter = array();

        switch ($view) {
            case 'all':
                $filter = NULL;
                break;
            default:
                $filter = array('status' =>'customer_confirmed');
                break;
        }
        $orders = Orders::getInstance()->getAll($filter);

        $entities = array();
        if (!empty($orders)) {
            $entities = array();
            foreach ($orders as $order) {
                $entities[] = array(
                    'id' => $order->id,
                    'lastUpdatedOn' => $order->lastUpdatedOn,
                    'status' => $order->status,
                    'customer' => array(
                        'name' => $order->customer->name,
                        'phone' => $order->customer->phone,
                        'email' => $order->customer->email,
                    ),
                );
            }

        }

        return $entities;
    }

    protected function destroyEntity($id)
    {
        Orders::getInstance()->findEntity($id)->destroy();
    }

    protected function storeEntity()
    {
        $request = $this->getRequest();
        // Find the order.
        $order = Orders::getInstance()->findEntity(
            $request->getParameter('id')
        );

        // Update the order from the request data.
        $order->update(array(
            'vendorComments' => $request->getParameter('vendorComments'),
        ));

        // Set the new status.
        $status = $request->getParameter('status');
        if ($order->getStatus() != $status) {
            $order->setStatus($status, TRUE);
        }

        // Store the order.
        $order->store();

        // Return the order's updated data.
        return $order->getFieldsData();
    }

    protected function archiveOrder($id)
    {
        $order = Orders::getInstance()->findEntity($id);
        $order->setStatus(Order::STATUS_CLOSED);
        $order->store();
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

    /**
     * Issue a response to an archival process.
     */
    protected function getArchivalResponse(array $selectedIds)
    {
        return $this->getBatchActionResponse(
                array($this, 'archiveOrder'),
                $selectedIds
        );
    }

    /**
     * Issue a response to an archival process.
     */
    protected function getArchivalConfirmationResponse(array $selectedIds)
    {
        return $this->getBatchActionConfirmationResponse(
                'archive',
                $selectedIds
        );
    }

    /**
     * Get a posted order's data from the request.
     */
    protected function getEntityDataFromRequest()
    {
        throw new \Exception('Looks like this function is used after all. It needs implementing.');

        $request = $this->getRequest();

        $customer = Customers::findEntity($request->getParameter('customer'));

        // Fetch the order from the request.
        return array(
            'customer' => $customer,
            'comments' => $request->getParameter('comments'),
            'vendorComments' => $request->getParameter('vendorComments'),
            'price' => $request->getParameter('price'),
            'weight' => $request->getParameter('weight'),
            'securityCode' => $request->getParameter('securityCode'),
        );
    }
}
