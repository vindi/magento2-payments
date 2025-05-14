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
declare(strict_types=1);

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
use Magento\Framework\Exception\LocalizedException;

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
     * @var CustomerSession
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
     * @var Api
     */
    protected $api;

    /**
     * PaymentsRequest constructor.
     *
     * @param ManagerInterface           $eventManager
     * @param Data                       $helper
     * @param DateTime                   $date
     * @param ConfigInterface            $config
     * @param CustomerSession            $customerSession
     * @param DateTime                   $dateTime
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Api                        $api
     */
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
        $this->eventManager       = $eventManager;
        $this->helper             = $helper;
        $this->date               = $date;
        $this->config             = $config;
        $this->customerSession    = $customerSession;
        $this->dateTime           = $dateTime;
        $this->productRepository  = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->api                = $api;
    }

    /**
     * Get the payment method data.
     *
     * @param Order $order
     * @return array
     */
    protected function getPaymentMethod(Order $order): array
    {
        return [
            'payment_method_id' => $this->helper->getMethodId('PIX'),
            'split'             => 1
        ];
    }

    /**
     * Get the transaction data.
     *
     * @param Order $order
     * @param float $amount
     * @return array
     */
    protected function getTransaction(Order $order, float $amount): array
    {
        $transaction = [
            'token_account'        => $this->helper->getToken($order->getStoreId()),
            'finger_print'         => $order->getPayment()->getAdditionalInformation('finger_print'),
            'customer'             => $this->getCustomerData($order),
            'transaction'          => $this->getTransactionInfo($order, $amount),
            'transaction_product'  => $this->getItemsData($order)
        ];

        $resellerToken = $this->helper->getResellerToken($order->getStoreId());
        if ($resellerToken) {
            $transaction['reseller_token'] = $resellerToken;
        }

        return $transaction;
    }

    /**
     * @param Order $order
     * @param float $orderAmount
     * @return array
     */
    protected function getTransactionInfo(Order $order, float $orderAmount): array
    {
        $shippingDescription = $order->getShippingDescription();
        $shippingType        = $shippingDescription ?: 'SEM_FRETE';

        $shippingAmount = (float) $order->getShippingAmount();
        if ($shippingAmount <= 0 && method_exists($order, 'getShippingInclTax')) {
            $shippingAmount = (float) $order->getShippingInclTax();
        }

        return [
            'customer_ip'      => $order->getRemoteIp(),
            'order_number'     => $order->getIncrementId(),
            'price_discount'   => number_format($this->getDiscountAmount($order, $orderAmount), 2, '.', ''),
            'price_additional' => number_format($this->getPriceAdditional($order, $orderAmount), 2, '.', ''),
            'url_notification' => $this->helper->getPaymentsNotificationUrl($order),
            'free'             => 'MAGENTO_API_' . $this->helper->getModuleVersion(),
            'shipping_type'  => $shippingType,
            'shipping_price' => number_format($shippingAmount, 2, '.', '')
        ];
    }

    /**
     * Get the discount amount for the order.
     *
     * @param Order $order
     * @param float $orderAmount
     * @return float
     */
    public function getDiscountAmount(Order $order, $orderAmount): float
    {
        $discountAmount   = (float)$order->getDiscountAmount();
        $transactionTotal = $order->getBaseSubtotal() + $order->getShippingAmount() + $discountAmount;
        if ($transactionTotal > $orderAmount) {
            $discountAmount = $transactionTotal - $orderAmount;
        }
        return round(abs($discountAmount), 2);
    }

    /**
     * Get the additional price for the transaction.
     *
     * @param Order $order
     * @param float $orderAmount
     * @return float
     */
    protected function getPriceAdditional(Order $order, float $orderAmount): float
    {
        $transactionTotal = $order->getBaseSubtotal() + $order->getShippingAmount() + $order->getDiscountAmount();
        if ($transactionTotal < $orderAmount) {
            return round($orderAmount - $transactionTotal, 2);
        }
        return 0.00;
    }

    /**
     * Get customer data.
     *
     * @param Order $order
     * @return array
     */
    public function getCustomerData(Order $order): array
    {
        $address             = $order->getBillingAddress();
        $customerTaxVat      = $address->getVatId() ?: $order->getCustomerTaxvat();
        $vindiCustomerTaxVat = $order->getPayment()->getAdditionalInformation('vindi_customer_taxvat');
        if ($vindiCustomerTaxVat) {
            $customerTaxVat = $vindiCustomerTaxVat;
        }

        $firstName = $address->getFirstname() ?: $order->getCustomerFirstname();
        $lastName  = $address->getLastname()  ?: $order->getCustomerLastname();
        $fullName  = $order->getCustomerName() ?: trim("$firstName $lastName");

        $customerData = [
            'name'      => $fullName,
            'cpf'       => preg_replace('/\D/', '', (string)$customerTaxVat),
            'email'     => $order->getCustomerEmail(),
            'contacts'  => [[
                'type_contact'   => 'M',
                'number_contact' => $this->helper->formatPhoneNumber($address->getTelephone())
            ]],
            'addresses' => $this->getAddresses($order)
        ];

        return $this->helper->getCompanyData($order, $customerData);
    }

    /**
     * Get addresses data.
     *
     * @param Order $order
     * @return array
     */
    protected function getAddresses($order): array
    {
        $addresses      = [];
        $billingAddress = $order->getBillingAddress();

        $addresses[] = [
            'type_address' => 'B',
            'postal_code'  => $billingAddress->getPostcode(),
            'street'       => $billingAddress->getStreetLine($this->getStreetField('street')),
            'number'       => $billingAddress->getStreetLine($this->getStreetField('number')),
            'completion'   => $billingAddress->getStreetLine($this->getStreetField('complement')),
            'neighborhood' => $billingAddress->getStreetLine($this->getStreetField('district')),
            'city'         => $billingAddress->getCity(),
            'state'        => $billingAddress->getRegionCode()
        ];

        if ($shipping = $order->getShippingAddress()) {
            $addresses[] = [
                'type_address' => 'D',
                'postal_code'  => $shipping->getPostcode(),
                'street'       => $shipping->getStreetLine($this->getStreetField('street')),
                'number'       => $shipping->getStreetLine($this->getStreetField('number')),
                'completion'   => $shipping->getStreetLine($this->getStreetField('complement')),
                'neighborhood' => $shipping->getStreetLine($this->getStreetField('district')),
                'city'         => $shipping->getCity(),
                'state'        => $shipping->getRegionCode()
            ];
        }

        return $addresses;
    }

    /**
     * Get the street field position.
     *
     * @param string $config
     * @return int
     */
    public function getStreetField(string $config): int
    {
        return (int)$this->helper->getConfig($config, 'address', 'vindi_vp') + 1;
    }

    /**
     * Get items data for the transaction.
     *
     * @param Order $order
     * @return array
     */
    protected function getItemsData(Order $order): array
    {
        $items = [];
        foreach ($order->getAllItems() as $quoteItem) {
            if ($quoteItem->getParentItemId() || $quoteItem->getParentItem() || $quoteItem->getPrice() == 0) {
                continue;
            }

            $unitPrice = $quoteItem->getRowTotalInclTax() / (float)$quoteItem->getQtyOrdered();

            $item = [
                'description' => $quoteItem->getName(),
                'quantity'    => (string)$quoteItem->getQtyOrdered(),
                'price_unit'  => number_format($unitPrice, 2, '.', ''),
                'code'        => $quoteItem->getProductId(),
                'sku_code'    => $quoteItem->getSku(),
                'extra'       => $quoteItem->getItemId()
            ];

            $this->eventManager->dispatch(
                'vindi_payment_get_item',
                ['item' => &$item, 'quote_item' => $quoteItem]
            );

            $items[] = $item;
        }

        return $items;
    }
}
