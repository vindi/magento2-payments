<?php

/**
 *
 *
 *
 *
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 *
 *
 */

namespace Vindi\VP\Gateway\Request;

use Vindi\VP\Gateway\Http\Client\Api;
use Vindi\VP\Helper\Data;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;

class PaymentsRequest
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var string
     */
    protected $currencyCode;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(
        ManagerInterface $eventManager,
        Data $helper,
        DateTime $date,
        ConfigInterface $config,
        CustomerSession $customerSession,
        DateTime $dateTime,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        Api $api
    ) {
        $this->eventManager = $eventManager;
        $this->helper = $helper;
        $this->date = $date;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->dateTime = $dateTime;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->api = $api;
    }

    protected function getPaymentMethod(Order $order): array
    {
        return [
            'payment_method_id' => $this->helper->getMethodId('PIX'),
            'split' => 1
        ];
    }

    protected function getTransaction(Order $order, float $amount): array
    {
        return [
            'token_account' => $this->helper->getToken($order->getStoreId()),
            'finger_print' => $order->getPayment()->getAdditionalInformation('finger_print'),
            'customer' => $this->getCustomerData($order),
            'transaction' => $this->getTransactionInfo($order),
            'transaction_product' => $this->getItemsData($order)
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getTransactionInfo($order): array
    {
        return [
            'customer_ip' => $order->getRemoteIp(),
            'order_number' => $order->getIncrementId(),
            'shipping_type' => $order->getShippingDescription(),
            'shipping_price' => (string) $order->getShippingAmount(),
            'price_discount' => (string) $order->getDiscountAmount(),
            'url_notification' => $this->helper->getPaymentsNotificationUrl($order),
            'free' => $order->getIncrementId()
        ];
    }

    public function getCustomerData(Order $order): array
    {
        $address = $order->getBillingAddress();
        $customerTaxVat = $address->getVatId() ?: $order->getCustomerTaxvat();
        $vindiCustomerTaxVat = $order->getPayment()->getAdditionalInformation('vindi_customer_taxvat');
        if ($vindiCustomerTaxVat) {
            $customerTaxVat = $vindiCustomerTaxVat;
        }

        $firstName = $address->getFirstname() ?: $order->getCustomerFirstname();
        $lastName = $address->getLastname() ?: $order->getCustomerLastname();
        $fullName = $order->getCustomerName() ?: $firstName . ' ' . $lastName;

        $customerData = [
            'name' => $fullName,
            'cpf' => $customerTaxVat,
            'email' => $order->getCustomerEmail(),
            'contacts' => [
                [
                    'type_contact' => 'M',
                    'number_contact' => $this->helper->formatPhoneNumber($address->getTelephone())
                ]
            ],
            'addresses' => $this->getAddresses($order)
        ];

        if ($order->getCustomerDob()) {
            $customerData['birth_date'] = $this->helper->formatDate($order->getCustomerDob());
        }

        return $customerData;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function getAddresses($order): array
    {
        $billingAddress = $order->getBillingAddress();
        $addresses = [
            [
                'type_address' => 'B',
                'postal_code' => $billingAddress->getPostcode(),
                'street' => $billingAddress->getStreetLine($this->getStreetField('street')),
                'number' => $billingAddress->getStreetLine($this->getStreetField('number')),
                'completion' => $billingAddress->getStreetLine($this->getStreetField('complement')),
                'neighborhood' => $billingAddress->getStreetLine($this->getStreetField('district')),
                'city' => $billingAddress->getCity(),
                'state' => $billingAddress->getRegionCode()
            ]
        ];

        if ($order->getShippingAddress()) {
            $shippingAddress = $order->getShippingAddress();
            $addresses[] = [
                'type_address' => 'D',
                'postal_code' => $shippingAddress->getPostcode(),
                'street' => $shippingAddress->getStreetLine($this->getStreetField('street')),
                'number' => $shippingAddress->getStreetLine($this->getStreetField('number')),
                'completion' => $shippingAddress->getStreetLine($this->getStreetField('complement')),
                'neighborhood' => $shippingAddress->getStreetLine($this->getStreetField('district')),
                'city' => $shippingAddress->getCity(),
                'state' => $shippingAddress->getRegionCode()
            ];
        }

        return $addresses;
    }

    public function getStreetField(string $config): int
    {
        return (int) $this->helper->getConfig($config, 'address', 'vindi') + 1;
    }

    protected function getItemsData(Order $order): array
    {
        $items = [];
        $quoteItems = $order->getAllItems();

        /** @var OrderItemInterface $quoteItem */
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getParentItemId() || $quoteItem->getParentItem() || $quoteItem->getPrice() == 0) {
                continue;
            }

            $item = [];
            $item['description'] = $quoteItem->getName();
            $item['quantity'] = (string) $quoteItem->getQtyOrdered();
            $item['price_unit'] = (string) $quoteItem->getPrice();
            $item['code'] = $quoteItem->getProductId();
            $item['sku_code'] = $quoteItem->getSku();
            $item['extra'] = $quoteItem->getItemId();

            $this->eventManager->dispatch('vindi_payment_get_item', ['item' => &$item, 'quote_item' => $quoteItem]);

            $items[] = $item;
        }

        return $items;
    }
}
