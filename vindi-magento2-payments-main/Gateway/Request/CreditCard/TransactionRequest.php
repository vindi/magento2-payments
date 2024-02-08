<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 *
 */

namespace Vindi\VP\Gateway\Request\CreditCard;

use Vindi\VP\Gateway\Request\PaymentsRequest;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class TransactionRequest extends PaymentsRequest implements BuilderInterface
{
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $buildSubject['payment']->getPayment();
        $order = $payment->getOrder();

        $request = $this->getTransaction($order, $buildSubject['amount']);
        $request['payment'] = $this->getPaymentMethod($order);

        return ['request' => $request, 'client_config' => ['store_id' => $order->getStoreId()]];
    }

    protected function getPaymentMethod($order): array
    {
        return [
            'payment_method_id' => $this->helper->getMethodId($order->getPayment()->getCcType()),
            'card_name' => $order->getPayment()->getCcOwner(),
            'card_number' => $order->getPayment()->getCcNumber(),
            'card_expdate_month' => $order->getPayment()->getCcExpMonth(),
            'card_expdate_year' => $order->getPayment()->getCcExpYear(),
            'card_cvv' => $order->getPayment()->getCcCid(),
            'split' => (string) $this->getInstallments($order)
        ];
    }

    protected function getInstallments($order): int
    {
        return (int) $order->getPayment()->getAdditionalInformation('cc_installments') ?: 1;
    }
}
