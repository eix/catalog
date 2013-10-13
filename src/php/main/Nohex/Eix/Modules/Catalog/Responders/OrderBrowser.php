<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\Responses\Http\Redirection;
use Nohex\Eix\Modules\Catalog\Model\Order;
use Nohex\Eix\Modules\Catalog\Model\Orders;
use Nohex\Eix\Modules\Catalog\Responses\Html as HtmlResponse;
use Nohex\Eix\Services\Data\Responders\CollectionBrowser;
use Nohex\Eix\Services\Net\Http\BadRequestException;
use Nohex\Eix\Services\Net\Http\NotFoundException;

class OrderBrowser extends CollectionBrowser
{
    public function getCollectionName()
    {
        return 'orders';
    }

    public function getItemName()
    {
        return 'order';
    }

    /**
     * GET /{collection}[/:id]
     * 
     * The parent method is overriden to avoid 404ing on non-existent orders, in
     * order to avoid entity enumeration by malicious users.
     * 
     * The order authentication request is always required, whether the order
     * exists or not. The result of non-existent order or an incorrectly
     * authenticated one is indistinguishable.
     * 
     * @return HtmlResponse
     */
    public function httpGetForHtml()
    {
        $id = $this->getRequest()->getParameter('id');
        $authenticationCode = $this->getRequest()->getParameter('authenticationCode');
        
        $response = $this->getHtmlResponse();
        $response->setTemplateId($this->getViewTemplateId());
        // Add the identifier to the response.
        $response->setData('order', ['id' => $id]);

        // No authentication code found.
        if ($authenticationCode) {
            try {
                $order = $this->getEntity($id);
                if ($order && ($order->getAuthenticationCode() == $authenticationCode)) {
                    $response = $this->getViewResponse($order);

                    $response->setData('order', $order->getFieldsData());
                    $response->appendToTitle(sprintf(
                        _('%s %s'),
                        ucfirst($this->getItemName()),
                        $order->getId()
                    ));
                }
            } catch (NotFoundException $exception) {
                // Swallow it, no one needs to know which orders exist and which
                // do not.
            }
        }


        return $response;
    }

    /**
     * POST /orders[/:id]
     * POST /comandes[/:id]
     * @return Html
     */
    public function httpPostForHtml()
    {
        $id = $this->getRequest()->getParameter('id');
        $operation = $this->getRequest()->getParameter('operation');

        $response = NULL;
        switch ($operation) {
            case 'authenticate':
                $response = $this->getAuthenticationResponse($id);
                break;
            case 'resend':
                try {
                    $order = $this->getEntity($id);
                    // Resend the confirmation.
                    $order->sendConfirmationRequest();
                } catch (NotFoundException $exception) {
                    // If the order was not found, just go ahead and pretend it
                    // does, to avoid enumeration.
                }
                $response = new Redirection($this->getRequest());
                $response->setNextUrl("/comandes/{$id}");
                $response->addNotice(_('The confirmation e-mail has been resent.'));
                break;
            default:
                throw new BadRequestException('Operation not recognised.');
        }

        return $response;
    }

    private function getAuthenticationResponse($orderId)
    {
        $validationErrors = array();
        
        $order = null;
        try {
            $order = $this->getEntity($orderId);
            $securityCode = $this->getRequest()->getParameter('security_code');
            // Check that a security code is presented.
            if (empty($securityCode)) {
                $validationErrors[] = _('The security code is missing.');
            } else {
                // Check that the security code matches the expected one.
                if ($order->securityCode != $securityCode) {
                    $validationErrors[] = _('The security code is incorrect.');
                }
            }
        } catch (NotFoundException $exception) {
            // Same error for wrong codes and non-existing orders, to avoid
            // enumeration.
            $validationErrors[] = _('The security code is incorrect.');
        }

        $response = NULL;

        // If the validation status is empty, everything looks ok.
        if (empty($validationErrors)) {
            // If the order is new, this step just validated the customer's
            // e-mail address.
            if ($order->getStatus() == Order::STATUS_NEW) {
                // Set the status and notify the vendor.
                $order->setStatus(Order::STATUS_CONFIRMED_BY_CUSTOMER, true);
                // Store the order with the new status.
                $order->store();
                // Display the order's new status.
            }
            // Redirect to the order view page with the authentication code.
            $response = new Redirection($this->getRequest());
            $response->setNextUrl(sprintf('/comandes/%s/%s',
                $order->getId(),
                $order->getAuthenticationCode()
            ));
        } else {
            // Validation failed.
            $response = new HtmlResponse($this->getRequest());
            $response->addErrorMessage(array('validation' => array(
                'security_code' => $validationErrors,
            )));
            $response->setTemplateId('orders/view');
            $response->setData('order', ['id' => $orderId]);
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
                    'customer' => $order->getCustomer(),
                    'lastUpdatedOn' => $order->lastUpdatedOn,
                    'status' => $order->status,
                );
            }

        }

        return $entities;
    }

    public function getDefaultFactory()
    {
        return Orders::getInstance();
    }
}
