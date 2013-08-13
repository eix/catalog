<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Core\Request;
use Nohex\Eix\Core\Responses\Http\Redirection as RedirectionResponse;
use Nohex\Eix\Modules\Catalog\Model\Customers;
use Nohex\Eix\Modules\Catalog\Model\Order;
use Nohex\Eix\Modules\Catalog\Model\Products;
use Nohex\Eix\Services\Data\Responders\CollectionBrowser;
use Nohex\Eix\Services\Data\Validators\Exception as ValidationException;
use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Net\Http\BadRequestException;
use Nohex\Eix\Services\Net\Http\NotFoundException;

/**
 * Manages a customer's basket.
 */
class BasketManager extends CollectionBrowser
{
    /**
     * GET /cistella
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpGetForHtml()
    {
        return $this->getViewPageResponse();
    }

    /**
     * GET /cistella/buida
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    protected function httpGetClearForHtml()
    {
        return $this->getClearBasketResponse();
    }

    /**
     * POST /cistella
     * @return \Nohex\Eix\Core\Responses\Http
     */
    public function httpPostForHtml()
    {
        $operation = $this->getRequest()->getParameter('operation');
        $productId = $this->getRequest()->getParameter('product');

        switch ($operation) {
            case 'add': // Not an ID, but a page.
                $response = $this->getAddToBasketResponse($productId);
                break;
            case 'remove':
                $response = $this->getRemoveFromBasketResponse($productId);
                break;
            default:
                // Store the product data that came in the request.
                throw new BadRequestException('Operation not supported.');
        }

        // Force basket contents to update.
        Customers::getCurrent()->getBasket()->getContents();
        // Add the basket data to the response.
        $response->setData('basket', Customers::getCurrent()->getBasket());

        return $response;
    }

    public function httpPostClearForHtml()
    {
        return $this->getBasketClearedResponse();
    }

    public function httpGetCheckoutForHtml()
    {
        if (Customers::getCurrent()->getBasket()->isEmpty()) {
            return $this->getViewPageResponse();
        } else {
            return $this->getStartCheckoutResponse();
        }
    }

    public function httpPostCheckoutForHtml()
    {
        if (Customers::getCurrent()->getBasket()->isEmpty()) {
            // Subtle hint that there's nothing to check out.
            return $this->getViewPageResponse();
        } else {
            return $this->getEndCheckoutResponse();
        }
    }

    /**
     * Provides the standard HTML response for the basket section.
     */
    protected function getHtmlResponse()
    {
        $response = parent::getHtmlResponse();
        $response->appendToTitle(_('Basket'));

        return $response;
    }

    private function getViewPageResponse()
    {
        $response = $this->getHtmlResponse();
        $response->setTemplateId('basket/view');

        // Force basket contents to update.
        Customers::getCurrent()->getBasket()->getContents();
        // Add the basket data to the response.
        $response->setData('basket', Customers::getCurrent()->getBasket());

        return $response;
    }

    private function getClearBasketResponse()
    {
        $response = $this->getHtmlResponse();
        $response->setTemplateId('basket/clear');

        return $response;
    }

    private function getStartCheckoutResponse()
    {
        $response = $this->getHtmlResponse();
        $response->setTemplateId('basket/checkout');
        $response->setData('customer', Customers::getCurrent()->getFieldsData());
        $response->setData('basket', Customers::getCurrent()->getBasket());

        return $response;
    }

    private function getEndCheckoutResponse()
    {
        $customer = Customers::getCurrent();

        try {
            // Validate additional fields.
            $this->updateCustomerData();
            // Generate order
            $order = new Order(array(
                'basket' => $customer->getBasket(),
                'customer' => $customer,
                'comments' => $this->getRequest()->getParameter('comments'),
            ));
            // Save the new order.
            $order->store();
            // Send a confirmation request e-mail.
            $order->sendConfirmationRequestMessage();
            // Get rid of the customer data.
            $customer->destroy();
            // View the order that has just been created.
            $response = new \Nohex\Eix\Core\Responses\Http\Redirection($this->getRequest());
            $response->setNextUrl("/comandes/{$order->id}");
        } catch (ValidationException $exception) {
            $response = $this->getHtmlResponse();
            $response->setTemplateId('basket/checkout');
            $response->addErrorMessage(array(
                'validation' => $exception->getValidationStatus(),
            ));
        }
        // Add the customer data to the response.
        $response->addData('customer', $customer->getFieldsData());

        return $response;
    }

    /**
     * Makes sure the customer data is correct. Since a customer's name, email
     * and phone is only required when ordering, they cannot be required as
     * part of the entity.
     */
    private function updateCustomerData()
    {
        // Get the current customer.
        $customer = Customers::getCurrent();
        // Since an order is being place, there are some fields that are now
        // required.
        $customer->addFieldValidators(array(
            'name' => array('NonEmpty'),
            'phone' => array('NonEmpty'),
            'email' => array('NonEmpty', 'Email'),
        ));
        // Update the customer data with the new fields.
        $customer->update(array(
            'name' =>  $this->getRequest()->getParameter('name'),
            'email' =>  $this->getRequest()->getParameter('email'),
            'phone' =>  $this->getRequest()->getParameter('phone'),
        ), FALSE /* Non-atomic operation */);
    }

    /**
     * Adds the selected product to the basket.
     *
     * @param string $productId the ID of the product to add.
     */
    private function getAddToBasketResponse($productId)
    {
        $response = $this->getViewPageResponse();
        try {
            // Add the selected product to the current customer's basket.
            Customers::getCurrent()->getBasket()->add(
                Products::getInstance()->findEntity($productId)
            );
        } catch (NotFoundException $exception) {
            $response->addErrorMessage(_('There is no such product.'));
        } catch (\Exception $exception) {
            $customerId = Customers::getCurrent()->getId();
            Logger::get()->error(
                "Failed to add product {$productId} to customer {$customerId}'s basket."
            );
            $response->addErrorMessage(_('The product could not be dropped in the basket.'));
        }

        return $response;
    }

    /**
     * Removes the selected product from the basket.
     *
     * @param string $productId the ID of the product to remove.
     */
    private function getRemoveFromBasketResponse($productId)
    {
        $response = $this->getViewPageResponse();
        try {
            // Remove the selected product from the current customer's basket.
            Customers::getCurrent()->getBasket()->remove(
                Products::getInstance()->findEntity($productId)
            );
        } catch (NotFoundException $exception) {
            $response->addErrorMessage(_('There is no such product.'));
        } catch (\Exception $exception) {
            $customerId = Customers::getCurrent()->getId();
            Logger::get()->error(
                "Failed to remove product {$productId} from customer {$customerId}'s basket."
            );
            $response->addErrorMessage(_('The product could not be removed from the basket.'));
        }

        return $response;
    }

    /**
     * Empties the basket.
     */
    private function getBasketClearedResponse()
    {
        $response = NULL;
        try {
            // Remove all products from the current customer's basket.
            Customers::getCurrent()->getBasket()->clear();
            $response = new RedirectionResponse($this->getRequest());
            $response->setNextUrl('/cistella');
        } catch (\Exception $exception) {
            $response = $this->getViewPageResponse();
            $customerId = Customers::getCurrent()->getId();
            Logger::get()->error(
                "Failed to clear customer {$customerId}'s basket."
            );
            $response->addErrorMessage(_('The basket could not be cleared.'));
        }

        return $response;
    }

    public function getDefaultFactory()
    {
        throw new Exception('The basket manager should not need an entity factory.');
    }
}
