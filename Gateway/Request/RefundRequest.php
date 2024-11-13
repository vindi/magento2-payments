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

use Vindi\VP\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Interceptor;

class RefundRequest implements BuilderInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build(array $buildSubject)
    {
        if (
            !isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var Interceptor $payment */
        $payment = $buildSubject['payment']->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();
        $amountValue = $buildSubject['amount'] ?? $order->getGrandTotal();
        $accessToken = $this->helper->getAccessToken();

        $request = [
            'access_token' => $accessToken,
            'transaction_id' => (string) $payment->getAdditionalInformation('transaction_id'),
            'amount' => (string) $amountValue
        ];

        $clientConfig = [
            'order_id' => $payment->getAdditionalInformation('order_id'),
            'status' => $payment->getAdditionalInformation('status'),
            'store_id' => $order->getStoreId()
        ];

        return ['request' => $request, 'client_config' => $clientConfig];
    }
}
