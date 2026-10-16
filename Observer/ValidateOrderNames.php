<?php

declare(strict_types=1);

namespace Loewenstark\APSB26153\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

class ValidateOrderNames implements ObserverInterface
{
    protected $forbidden_patterns = [
        '{{config',
        '{{var',
        '<svg',
        '<img',
        'AfterFilterCallback',
        'getTemplateFilter',
        'base64_decode'
    ];

    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');

        $names = [
            $order->getCustomerFirstname(),
            $order->getCustomerLastname(),
        ];

        foreach ([$order->getBillingAddress(), $order->getShippingAddress()] as $address) {
            if ($address !== null) {
                $names[] = $address->getFirstname();
                $names[] = $address->getLastname();
            }
        }

        foreach ($names as $name) {
            if ($name === null) {
                continue;
            }

            foreach ($this->forbidden_patterns as $pattern) {
                if (stripos($name, $pattern) !== false) {
                    throw new LocalizedException(
                        __(
                            'Your order could not be placed. Please check the first and last names '
                            . 'in your customer details and billing and shipping addresses. '
                            . 'These fields contain an invalid character sequence.'
                        )
                    );
                }
            }
        }
    }
}
