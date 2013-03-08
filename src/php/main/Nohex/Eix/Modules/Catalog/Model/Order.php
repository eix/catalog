<?php

namespace Nohex\Eix\Modules\Catalog\Model;

use Nohex\Eix\Modules\Catalog\Model\Basket;
use Nohex\Eix\Modules\Catalog\Model\Customer;
use Nohex\Eix\Modules\Catalog\Model\Orders;
use Nohex\Eix\Services\Data\Sources\MongoDB as DataSource;

/**
 * An order is created when customers request to buy the contents of their
 * baskets.
 */
class Order extends \Nohex\Eix\Services\Data\Entity
{
    const COLLECTION = 'orders';

    const STATUS_NEW = 'new';
    const STATUS_CONFIRMED_BY_CUSTOMER = 'customer_confirmed';
    const STATUS_CANCELLED_BY_CUSTOMER = 'customer_cancelled';
    const STATUS_CONFIRMED_BY_VENDOR = 'vendor_confirmed';
    const STATUS_CANCELLED_BY_VENDOR = 'vendor_cancelled';
    const STATUS_READY = 'ready';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CLOSED = 'closed';

    private static $statuses = array();

    protected $customer;
    protected $comments;
    protected $vendorComments;
    protected $price;
    protected $weight;
    protected $securityCode;
    protected $statusHistory = array();

    public function __construct(array $data, $isNew = TRUE)
    {
        // Set the correspondence between status codes and IDs.
        self::$statuses = array(
            self::STATUS_NEW => _('new'),
            self::STATUS_CONFIRMED_BY_CUSTOMER => _('confirmed by customer'),
            self::STATUS_CANCELLED_BY_CUSTOMER => _('cancelled by customer'),
            self::STATUS_CONFIRMED_BY_VENDOR => _('confirmed by vendor'),
            self::STATUS_CANCELLED_BY_VENDOR => _('cancelled by vendor'),
            self::STATUS_READY => _('ready'),
            self::STATUS_DELIVERED => _('delivered'),
            self::STATUS_CLOSED => _('closed'),
        );

        if (empty($data['id'])) {
            $data['id'] = strtoupper(hash('crc32b', uniqid()));
        }

        parent::__construct($data, $isNew);

        // The default status for new orders is 'new'.
        if (empty($data['statusHistory'])) {
            $this->setStatus(self::STATUS_NEW);
        }
        // The default price for new orders is the basket price.
        if (empty($data['price'])) {
            $this->price = $this->customer->getBasket()->getPrice();
        }
        // The default weight for new orders is the basket weight.
        if (empty($data['weight'])) {
            $this->weight = $this->customer->getBasket()->getWeight();
        }
    }

    protected function assignDataSource()
    {
        $this->dataSource = DataSource::getInstance(static::COLLECTION);
    }

    protected function getFactory()
    {
        return Orders::getInstance();
    }

    public function update(array $data, $isAtomic = TRUE)
    {
        // Create a security code if none is present.
        if (empty($data['securityCode']) && empty($this->securityCode)) {
            $data['securityCode'] = sprintf('%06d', mt_rand(000000, 999999));
        }

        // Ensure the customer is the correct type.
        if (empty($data['customer'])) {
            if (empty($this->customer)) {
                throw new \InvalidArgumentException(
                    'No customer data found, the order will not be created.'
                );
            }
        } else {
            $customerData = @$data['customer'] ?: array();
            if (!($customerData instanceof Customer)) {
                // An entity is not retrieved from the registry here as it is
                // quite possible that the current user's data is no longer
                // the same as the one in the order.
                $data['customer'] = new Customer($customerData);
            }
        }

        parent::update($data, $isAtomic);
    }

    protected function getFields()
    {
        return array(
            'id',
            'customer',
            'price',
            'weight',
            'statusHistory',
            'comments',
            'vendorComments',
            'securityCode',
        );
    }

    public function getFieldsData()
    {
        $fieldsData = parent::getFieldsData();

        // Add the last status ID in its own field, for convenience.
        $lastStatus = end($fieldsData['statusHistory']);
        $fieldsData['status'] = $lastStatus['id'];

        return $fieldsData;
    }

    protected function getFieldValidators()
    {
        return array(
            'id' => array('NonEmpty'),
            'customer' => array('NonEmpty'),
        );
    }

    /**
     * Sends out notifications to whomever needs to be made aware that the
     * status of the order has changed.
     *
     * @param string $previousStatus the status the order had until now.
     * @param string $newStatus      the status the order has now.
     */
    public function notifyStatusChange($previousStatus, $newStatus)
    {
        $transition = array($previousStatus, $newStatus);
        switch ($transition) {
            case array(NULL, self::STATUS_NEW):
                $this->sendConfirmationRequestMessage();
                break;
            case array(self::STATUS_CONFIRMED_BY_CUSTOMER, self::STATUS_CONFIRMED_BY_VENDOR):
                $this->sendAcceptanceNotice();
                break;
            default:
                $this->notifyVendor();
        }
    }

    public function sendConfirmationRequestMessage()
    {
        $basketContents = join("\n", array_map(function ($item) {
            return utf8_decode(sprintf('- %d × %s',
                    $item['count'],
                    $item['name']
            ));
        }, $this->getBasket()->getContents()));

        $this->sendMessage(
            $this->getCustomer()->email,
            'orderConfirmation',
            array(
                'basketContents' => $basketContents,
                'basketPrice' => sprintf('%1.2f', $this->getBasket()->getPrice()),
                'orderId' => $this->id,
                'securityCode' => $this->getSecurityCode(),
            )
        );
    }

    public function sendAcceptanceMessage()
    {
        $basketContents = join("\n", array_map(function ($item) {
            return utf8_decode(sprintf('- %d × %s',
                    $item['count'],
                    $item['name']
            ));
        }, $this->getBasket()->getContents()));

        $this->sendMessage(
            $this->getCustomer()->email,
            'orderAccepted',
            array(
                'basketContents' => $basketContents,
                'orderId' => $this->id,
            )
        );
    }

    /**
     * Sends an e-mail message.
     */
    private function sendMessage($recipient, $templateId, array $data)
    {
        $mailSettings = \Nohex\Eix\Core\Application::getSettings()->mail;
        $messageFile = @$mailSettings->templates->$templateId;
        if (!is_readable($messageFile)) {
            throw new \Exception('No template found for the message.');
        }

        $message = str_replace(
            array_map(function ($item) {
                return '{{' . $item . '}}';
            }, array_keys($data)),
            array_values($data),
            file_get_contents($messageFile)
        );

        $mailMessage = new \Nohex\Eix\Services\Net\Mail\Message;
        $mailMessage->setSender(
            $mailSettings->sender->address,
            $mailSettings->sender->name
        );
        $mailMessage->setBody($message);
        $mailMessage->addRecipient($recipient);
        $mailMessage->setSubject(sprintf(_('Order %s'), $this->id));

        $mailMessage->send();
    }

    /**
     * Sends the vendor the order status.
     */
    public function notifyVendor()
    {
        $mailSettings = \Nohex\Eix\Core\Application::getSettings()->mail;
        $messageFile = @$mailSettings->templates->orderStatus;
        if (!is_readable($messageFile)) {
            throw new \Exception('No template found for the vendor notification.');
        }

        $message = sprintf(file_get_contents($messageFile),
            $this->id,
            self::$statuses[$this->getStatus()]
        );

        $mailMessage = new \Nohex\Eix\Services\Net\Mail\Message;
        $mailMessage->setSender(
            $mailSettings->sender->address,
            $mailSettings->sender->name
        );
        $mailMessage->setBody($message);
        $mailMessage->addRecipient($mailSettings->sender->address);
        $mailMessage->setSubject(sprintf(_('Order %s'), $this->id));

        $mailMessage->send();
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getBasket()
    {
        return $this->getCustomer()->getBasket();
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function getSecurityCode()
    {
        return $this->securityCode;
    }

    /**
     * Returns the latest status in the history.
     */
    public function getStatus()
    {
        $lastStatus = end($this->statusHistory);

        return @$lastStatus['id'];
    }

    public function getStatusHistory()
    {
        return $this->statusHistory;
    }

    /**
     * Sets the order's new status.
     *
     * @param string  $newStatus the new status.
     * @param boolean $notify    whether the parties should be notified of the change.
     */
    public function setStatus($newStatus, $notify = FALSE)
    {
        // TODO: Implement a basic workflow control which allows setting
        // only statuses that follow from the current one.

        // Add the current status to the order's history.
        $previousStatus = $this->getStatus();
        if ($previousStatus != $newStatus) {
            $this->statusHistory[] = array(
                'id' => $newStatus,
                'timestamp' => new \DateTime,
            );

            // Status changed, so send appropriate notifications.
            if ($notify) {
                $this->notifyStatusChange($previousStatus, $newStatus);
            }
        }
    }

    public static function getStatuses()
    {
        return self::$statuses;
    }
}
