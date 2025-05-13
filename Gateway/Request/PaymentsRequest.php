<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
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
     * Determine payment payload based on selected method.
     *
     * @param Order $order
     * @return array
     * @throws LocalizedException
     */
    protected function getPaymentMethod(Order $order): array
    {
        $payment    = $order->getPayment();
        $methodCode = $payment->getMethodInstance()->getCode();

        switch ($methodCode) {
            case 'vindi_cc':
                return $this->buildCreditCardPayment($payment);
            case 'vindi_boleto':
                return [
                    'payment_method_id' => $this->helper->getMethodId('BOLETO'),
                    'split'             => 1
                ];
            case 'vindi_pix':
                return [
                    'payment_method_id' => $this->helper->getMethodId('PIX'),
                    'split'             => 1
                ];
            default:
                throw new LocalizedException(__('Unsupported payment method "%1"', $methodCode));
        }
    }

    /**
     * Build transaction data array.
     *
     * @param Order $order
     * @param float $amount
     * @return array
     */
    protected function getTransaction(Order $order, float $amount): array
    {
        $transaction = [
            'token_account'        => $this->helper->getTokenAccount($order->getStoreId()),
            'finger_print'         => $order->getPayment()->getAdditionalInformation('finger_print'),
            'customer'             => $this->getCustomerData($order),
            'transaction'          => $this->getTransactionInfo($order, $amount),
            'transaction_shipping' => $this->getTransactionShipping($order),
            'transaction_product'  => $this->getItemsData($order),
            'payment'              => $this->getPaymentMethod($order)
        ];

        if ($resellerToken = $this->helper->getResellerToken($order->getStoreId())) {
            $transaction['reseller_token'] = $resellerToken;
        }

        return $transaction;
    }

    /**
     * Build credit card payment payload (new or saved).
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return array
     */
    protected function buildCreditCardPayment($payment): array
    {
        $profileId    = $payment->getAdditionalInformation('payment_profile');
        $installments = (int)$payment->getAdditionalInformation('cc_installments') ?: 1;

        if ($profileId) {
            return [
                'payment_method_id' => $this->helper->getMethodId($payment->getCcType()),
                'card_id'           => $profileId,
                'split'             => $installments
            ];
        }

        return [
            'payment_method_id'      => $this->helper->getMethodId($payment->getCcType()),
            'card_name'              => $payment->getCcOwner(),
            'card_number'            => $payment->getCcNumber(),
            'card_expdate_month'     => $payment->getCcExpMonth(),
            'card_expdate_year'      => $payment->getCcExpYear(),
            'card_cvv'               => $payment->getCcCid(),
            'split'                  => $installments
        ];
    }

    /**
     * Get the transaction information.
     *
     * @param Order $order
     * @param float $orderAmount
     * @return array
     */
    protected function getTransactionInfo(Order $order, float $orderAmount): array
    {
        return [
            'customer_ip'      => $order->getRemoteIp(),
            'order_number'     => $order->getIncrementId(),
            'price_discount'   => number_format($this->getDiscountAmount($order, $orderAmount), 2, '.', ''),
            'price_additional' => number_format($this->getPriceAdditional($order, $orderAmount), 2, '.', ''),
            'url_notification' => $this->helper->getPaymentsNotificationUrl($order),
            'free'             => 'MAGENTO_API_' . $this->helper->getModuleVersion()
        ];
    }

    /**
     * Get the shipping information for the transaction.
     *
     * @param Order $order
     * @return array
     */
    protected function getTransactionShipping(Order $order): array
    {
        $shippingType = $order->getShippingDescription() ?: 'SEM_FRETE';

        return [
            'type_shipping'  => $shippingType,
            'shipping_price' => number_format((float)$order->getShippingInclTax(), 2, '.', '')
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
        $discount = (float)$order->getDiscountAmount();
        $total    = $order->getBaseSubtotal() + $order->getShippingAmount() + $discount;

        if ($total > $orderAmount) {
            $discount = $total - $orderAmount;
        }

        return round(abs($discount), 2);
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
        $total = $order->getBaseSubtotal() + $order->getShippingAmount() + $order->getDiscountAmount();

        if ($total < $orderAmount) {
            return round($orderAmount - $total, 2);
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
        $address    = $order->getBillingAddress();
        $vat        = $address->getVatId() ?: $order->getCustomerTaxvat();
        $vindiVat   = $order->getPayment()->getAdditionalInformation('vindi_customer_taxvat');

        if ($vindiVat) {
            $vat = $vindiVat;
        }

        $firstName = $address->getFirstname() ?: $order->getCustomerFirstname();
        $lastName  = $address->getLastname() ?: $order->getCustomerLastname();
        $name      = $order->getCustomerName() ?: trim("$firstName $lastName");

        $data = [
            'name'      => $name,
            'cpf'       => preg_replace('/\D/', '', (string)$vat),
            'email'     => $order->getCustomerEmail(),
            'contacts'  => [[
                'type_contact'   => 'M',
                'number_contact' => $this->helper->formatPhoneNumber($address->getTelephone())
            ]],
            'addresses' => $this->getAddresses($order)
        ];

        $data = $this->helper->getCompanyData($order, $data);

        if ($order->getCustomerDob()) {
            $data['birth_date'] = $this->helper->formatDate($order->getCustomerDob());
        }

        return $data;
    }

    /**
     * Get addresses data.
     *
     * @param Order $order
     * @return array
     */
    protected function getAddresses($order): array
    {
        $addresses = [];
        $billing   = $order->getBillingAddress();

        $addresses[] = [
            'type_address' => 'B',
            'postal_code'  => $billing->getPostcode(),
            'street'       => $billing->getStreetLine($this->getStreetField('street')),
            'number'       => $billing->getStreetLine($this->getStreetField('number')),
            'completion'   => $billing->getStreetLine($this->getStreetField('complement')),
            'neighborhood' => $billing->getStreetLine($this->getStreetField('district')),
            'city'         => $billing->getCity(),
            'state'        => $billing->getRegionCode()
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

        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId() || $item->getParentItem() || $item->getPrice() == 0) {
                continue;
            }

            $unitPrice = $item->getRowTotalInclTax() / (float)$item->getQtyOrdered();

            $row = [
                'description' => $item->getName(),
                'quantity'    => (string)$item->getQtyOrdered(),
                'price_unit'  => number_format($unitPrice, 2, '.', ''),
                'code'        => $item->getProductId(),
                'sku_code'    => $item->getSku(),
                'extra'       => $item->getItemId()
            ];

            $this->eventManager->dispatch('vindi_payment_get_item', ['item' => &$row, 'quote_item' => $item]);

            $items[] = $row;
        }

        return $items;
    }
}
