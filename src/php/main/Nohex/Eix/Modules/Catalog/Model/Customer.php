<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Net\Http\NotFoundException;
use Nohex\Eix\Core\User;

/**
 * Representation of a user who can interact with the products.
 */
class Customer extends User
{
    protected $basket;
    protected $name;
    protected $phone;
    protected $email;

    private $entityFields;

    public function __construct(array $data, $isNew = true)
    {
        // Give the customer a basket.
        $this->basket = new Basket(array());

        parent::__construct($data, $isNew);
    }

    public function update(array $data, $isAtomic = true)
    {
        // If basket data is present, create a Basket object.
        if (
            isset($data['basket'])
            && !($data['basket'] instanceof Basket)
        ) {
            Logger::get()->debug(sprintf(
                'Filling customer %s\'s basket...',
                $this->id
            ));
            // Fill it up.
            $products = Products::getInstance();
            $basket = new Basket(array());
            foreach ($data['basket'] as $productId => $count) {
                Logger::get()->debug(
                    "Dropping {$count} of {$productId} in the basket..."
                );
                try {
                    $basket->add($products->findEntity($productId), $count);
                    Logger::get()->debug('Dropped.');
                } catch (NotFoundException $exception) {
                    Logger::get()->debug('Not found!');
                }
            }
            Logger::get()->debug('Basket is filled.');
            $data['basket'] = $basket;
        }

        // Update the rest of the customer data.
        parent::update($data, $isAtomic);
    }

    protected function getFieldValidators()
    {
        return array(
            'email' => array('Email'),
        );
    }

    /**
     * Provides access to the customer's basket.
     *
     * @return type
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * Adds the basket to the list of persistable fields.
     *
     * @return string
     */
    protected function getFields()
    {
        if (empty($this->entityFields)) {
            $this->entityFields = array_merge(
                parent::getFields(), array(
                    'basket',
                    'name',
                    'phone',
                    'email',
                )
            );
        }

        return $this->entityFields;
    }

    /**
     * Clears all personal data.
     */
    public function clear()
    {
        $this->getBasket()->clear();
        $this->name = NULL;
        $this->phone = NULL;
        $this->email = NULL;
    }
}
