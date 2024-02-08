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

namespace Vindi\VP\Gateway\Response\BankSlip;

use Vindi\VP\Gateway\Http\Client\Api;
use Vindi\VP\Helper\Order as HelperOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\TransactionInterface;

class TransactionHandler implements HandlerInterface
{
    /**
     * @var \Vindi\VP\Helper\Order
     */
    protected $helperOrder;

    /**
     * constructor.
     * @param HelperOrder $helperOrder
     */
    public function __construct(
        HelperOrder $helperOrder
    ) {
        $this->helperOrder = $helperOrder;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
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
        $payment = $this->helperOrder->updateBankSlipAdditionalInfo($payment, $responseTransaction);

        $payment->setIsTransactionClosed(false);
        $state = $this->helperOrder->getPaymentStatusState($payment);

        if ($this->helperOrder->canSkipOrderProcessing($state)) {
            $payment->getOrder()->setState($state);
            $payment->setSkipOrderProcessing(true);
            $payment->addTransaction(TransactionInterface::TYPE_ORDER);
        }
    }
}
