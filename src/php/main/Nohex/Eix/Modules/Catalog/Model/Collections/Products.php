<?php

namespace Nohex\Eix\Modules\Catalog\Model\Collections;

use Nohex\Eix\Services\Data\Model\Collections\Base as BaseCollection;

class Products extends BaseCollection
{
    /**
     * Get an order, first checking if the current user can view it.
     */
    protected function getEntity($id)
    {
        return Products::getInstance()->findEntity($id);
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
            $order->setStatus($status, true);
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
