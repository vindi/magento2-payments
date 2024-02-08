<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 *
 */

namespace Vindi\VP\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;

class CaptureRequest implements BuilderInterface
{
    /**
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

        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        $payment = $buildSubject['payment']->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();
        $request = $this->getTransaction($order);

        $clientConfig = [
            'order_id' => $payment->getAdditionalInformation('order_id'),
            'store_id' => $order->getStoreId()
        ];

        return ['request' => $request, 'client_config' => $clientConfig];
    }

    public function getTransaction($order): array
    {
        return [
            'reference_id' => $order->getIncrementId()
        ];
    }
}
