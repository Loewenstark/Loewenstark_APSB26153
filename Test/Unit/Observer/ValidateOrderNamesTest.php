<?php

declare(strict_types=1);

namespace Loewenstark\APSB26153\Test\Unit\Observer;

use Loewenstark\APSB26153\Observer\ValidateOrderNames;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;

class ValidateOrderNamesTest extends TestCase
{
    /**
     * @dataProvider forbiddenNamesProvider
     */
    public function testRejectsForbiddenNames(string $field, string $value): void
    {
        $observer = $this->createObserver([$field => $value]);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Your order could not be placed.');

        (new ValidateOrderNames())->execute($observer);
    }

    public static function forbiddenNamesProvider(): array
    {
        $cases = [];
        $fields = [
            'customer_firstname',
            'customer_lastname',
            'billing_firstname',
            'billing_lastname',
            'shipping_firstname',
            'shipping_lastname',
        ];
        $values = [
            '{{config path="general/store_information/name"}}',
            'Max {{CoNfIg path="test"}}',
            '<svg onload="alert(1)">',
            'Max <SvG/onload=alert(1)>',
            '<img src=x onerror="alert(1)">',
            'Max <IMG src=x>',
        ];

        foreach ($fields as $field) {
            foreach ($values as $index => $value) {
                $cases[$field . '_' . $index] = [$field, $value];
            }
        }

        return $cases;
    }

    /**
     * @dataProvider validNamesProvider
     */
    public function testAllowsValidNames(?string $value, bool $hasShipping): void
    {
        $names = array_fill_keys([
            'customer_firstname',
            'customer_lastname',
            'billing_firstname',
            'billing_lastname',
            'shipping_firstname',
            'shipping_lastname',
        ], $value);
        $observer = $this->createObserver($names, $hasShipping);

        (new ValidateOrderNames())->execute($observer);

        $this->assertSame($value, $observer->getEvent()->getData('order')->getCustomerFirstname());
    }

    public static function validNamesProvider(): array
    {
        return [
            'regular name' => ['Max', true],
            'apostrophe' => ["O'Connor", true],
            'hyphen' => ['Anne-Marie', true],
            'unicode' => ['Müller', true],
            'empty name' => ['', true],
            'guest without customer name' => [null, true],
            'virtual order' => ['Max', false],
        ];
    }

    public function testRejectsInvalidBillingNameWithoutShippingAddress(): void
    {
        $observer = $this->createObserver(['billing_lastname' => '<img src=x>'], false);

        $this->expectException(LocalizedException::class);

        (new ValidateOrderNames())->execute($observer);
    }

    public function testDoesNotValidateOtherAddressFields(): void
    {
        $observer = $this->createObserver([]);
        $billing = $observer->getEvent()->getData('order')->getBillingAddress();
        $billing->expects($this->never())->method('getCompany');
        $billing->expects($this->never())->method('getStreet');
        $billing->expects($this->never())->method('getMiddlename');

        (new ValidateOrderNames())->execute($observer);
    }

    private function createObserver(array $names, bool $hasShipping = true): Observer
    {
        $names += [
            'customer_firstname' => 'Max',
            'customer_lastname' => 'Mustermann',
            'billing_firstname' => 'Max',
            'billing_lastname' => 'Mustermann',
            'shipping_firstname' => 'Max',
            'shipping_lastname' => 'Mustermann',
        ];

        $billing = $this->createMock(OrderAddressInterface::class);
        $billing->method('getFirstname')->willReturn($names['billing_firstname']);
        $billing->method('getLastname')->willReturn($names['billing_lastname']);

        $shipping = null;
        if ($hasShipping) {
            $shipping = $this->createMock(OrderAddressInterface::class);
            $shipping->method('getFirstname')->willReturn($names['shipping_firstname']);
            $shipping->method('getLastname')->willReturn($names['shipping_lastname']);
        }

        $order = $this->createMock(Order::class);
        $order->method('getCustomerFirstname')->willReturn($names['customer_firstname']);
        $order->method('getCustomerLastname')->willReturn($names['customer_lastname']);
        $order->method('getBillingAddress')->willReturn($billing);
        $order->method('getShippingAddress')->willReturn($shipping);

        return new Observer(['event' => new Event(['order' => $order])]);
    }
}
