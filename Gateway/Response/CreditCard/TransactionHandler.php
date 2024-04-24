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

namespace Vindi\VP\Gateway\Response\CreditCard;

use Magento\Sales\Api\Data\TransactionInterface;
use Vindi\VP\Gateway\Http\Client\Api;
use Vindi\VP\Helper\Data;
use Vindi\VP\Helper\Order as HelperOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class TransactionHandler implements HandlerInterface
{
    /** @var \Vindi\VP\Helper\Order  */
    protected $helperOrder;

    /** @var Data */
    protected $helper;

    /** @var SessionManagerInterface */
    protected $session;

    /** @var Api */
    protected $api;

    public function __construct(
        HelperOrder $helperOrder,
        Data $helper,
        SessionManagerInterface $session,
        Api $api
    ) {
        $this->helperOrder = $helperOrder;
        $this->helper = $helper;
        $this->session = $session;
        $this->api = $api;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException(__('Payment data object should be provided'));
        }

        /** @var PaymentDataObjectInterface $paymentData */
        $paymentData = $handlingSubject['payment'];
        $transaction = $response['transaction'];

        if (
            (isset($response['status_code']) && $response['status_code'] >= 300)
            || !isset($transaction['data_response'])
        ) {
            throw new LocalizedException(__('There was an error processing your request.'));
        }

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentData->getPayment();
        $responseTransaction = $transaction['data_response']['transaction'];
        $payment = $this->helperOrder->updateDefaultAdditionalInfo($payment, $responseTransaction);

        if (
            $responseTransaction['status_id'] == HelperOrder::STATUS_PENDING
            || $responseTransaction['status_id'] == HelperOrder::STATUS_MONITORING
            || $responseTransaction['status_id'] == HelperOrder::STATUS_CONTESTATION
        ) {
            $payment->getOrder()->setState('new');
            $payment->setSkipOrderProcessing(true);
        }
    }

}
