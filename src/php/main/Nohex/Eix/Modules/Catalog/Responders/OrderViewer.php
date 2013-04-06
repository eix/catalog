<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\Responses\Http\Redirection;
use Nohex\Eix\Services\Data\Responders\CollectionBrowser;
use Nohex\Eix\Modules\Catalog\Model\Orders;
use Nohex\Eix\Modules\Catalog\Model\Order;

class OrderViewer extends CollectionBrowser
{
    const COLLECTION_NAME = 'orders';
    const ITEM_NAME = 'order';

    public function getCollectionName()
    {
        return static::COLLECTION_NAME;
    }

    public function getItemName()
    {
        return static::ITEM_NAME;
    }

    public function httpGetForHtml()
    {
        $response = parent::httpGetForHtml();

        // The list viewer returns an edition page by default, the orders only
        // need to show a view page.
        $response->setTemplateId('orders/view');

        return $response;
    }

    /**
     * POST /orders[/:id]
     * POST /comandes[/:id]
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpPostForHtml()
    {
        $id = $this->getRequest()->getParameter('id');
        $operation = $this->getRequest()->getParameter('operation');

        $response = NULL;
        switch ($operation) {
            case 'confirm':
                $response = $this->getConfirmationResponse($this->getEntity($id));
                break;
            case 'resend':
                $order = $this->getOrder($id);
                // Resend the confirmation.
                $order->sendConfirmationRequest();
                $response = new Redirection($this->getRequest());
                $response->setNextUrl("/comandes/{$id}");
                $response->addNotice(_('The confirmation e-mail has been resent.'));
                break;
            default:
                throw new BadRequestException('Operation not recognised.');
        }

        return $response;
    }

    private function getConfirmationResponse(Order $order)
    {
        if ($order->getStatus() != Order::STATUS_NEW) {
            throw new BadRequestException('This order has already been confirmed.');
        }

        $response = NULL;
        $validationErrors = array();

        $securityCode = $this->getRequest()->getParameter('security_code');

        // Check that a security code is presented.
        if (empty($securityCode)) {
            $validationErrors[] = _('The security code is missing.');
        } else {
            // Check that the security code is presented.
            if ($order->securityCode != $securityCode) {
                $validationErrors[] = _('The security code is incorrect.');
            }
        }

        // If the validation status is empty, everything looks ok.
        if (empty($validationErrors)) {
            // Set the status and notify the vendor.
            $order->setStatus(Order::STATUS_CONFIRMED_BY_CUSTOMER, TRUE);
            // Store the order with the new status.
            $order->store();
            // Display the order's new status.
            $response = new Redirection($this->getRequest());
            $response->setNextUrl("/comandes/{$order->id}");
        } else {
            // Validation failed.
            $response = new HtmlResponse($this->getRequest());
            $response->addErrorMessage(array('validation' => array(
                'security_code' => $validationErrors,
            )));
            $response->setTemplateId('orders/view');
            $response->setData('order', $order->getFieldsData());
        }

        return $response;
    }

    /**
     * Get an order, first checking if the current user can view it.
     */
    protected function getEntity($id)
    {
        // Fetch the order from the ID.
        $order = Orders::getInstance()->findEntity($id);

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
                );
            }

        }

        return $entities;
    }
}
