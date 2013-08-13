<?php

namespace Nohex\Eix\Modules\Catalog\Model\Collections;

use Nohex\Eix\Services\Data\Model\Collections\Base as BaseCollection;

class Orders extends BaseCollection
{
    const COLLECTION_NAME = 'orders';
    const ITEM_NAME = 'order';
    const ITEM_CLASS = '\\Nohex\\Eix\\Modules\\Catalog\\Model\\Order';

    /**
     * Get an order, first checking if the current user can view it.
     */
    protected function getEntity($id)
    {
        // Fetch the order from the ID.
        $order = Orders::getInstance()->findEntity($id);
        // Find the customer whom the order belongs to.
        $orderCustomerId = $order->getCustomer()->getId();
        if ($orderCustomerId != Customers::getCurrent()->getId()) {
            // A 'not found' exception is thrown instead of the expected 'not
            // authorised' to prevent order enumeration from malicious users.
            throw new NotFoundException(
            'The current customer cannot use this order.'
            );
        }

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
                $filter = array('status' => 'customer_confirmed');
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

    /**
     * Get a posted order's data from the request.
     */
    /*
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
     */
}
