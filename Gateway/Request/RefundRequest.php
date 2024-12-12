<?php

namespace Vindi\VP\Gateway\Request;

use Vindi\VP\Helper\Config;
use Vindi\VP\Helper\Data;
use Vindi\VP\Logger\Logger;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Interceptor;

/**
 * Class RefundRequest
 *
 * Handles the refund request preparation.
 */
class RefundRequest implements BuilderInterface
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var bool
     */
    private $isDebugEnabled;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * RefundRequest constructor.
     *
     * @param Config $configHelper
     * @param Logger $logger
     * @param Data $helperData
     */
    public function __construct(
        Config $configHelper,
        Logger $logger,
        Data $helperData
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->isDebugEnabled = $this->configHelper->isDebugEnabled();
        $this->helperData = $helperData;
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
        $this->logDebug('RefundRequest: Starting build process.');

        if (
            !isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            $this->logDebug('RefundRequest: Invalid payment data object provided.', 'error');
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var Interceptor $payment */
        $payment = $buildSubject['payment']->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();
        $amountValue = $buildSubject['amount'] ?? $order->getGrandTotal();

        $this->logDebug('RefundRequest: Retrieved amount value.');

        $storeId = (int) $order->getStoreId();

        $accessToken = $this->helperData->getAccessToken($storeId);

        if (empty($accessToken)) {
            $this->logDebug('RefundRequest: Unable to retrieve a valid access token.', 'error');
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to retrieve a valid access token.')
            );
        }

        $this->logDebug('RefundRequest: Access token retrieved successfully.');

        $request = [
            'access_token' => $accessToken,
            'transaction_id' => (string) $payment->getAdditionalInformation('transaction_id'),
            'refund_amount' => (string) $amountValue
        ];

        $this->logDebug('RefundRequest: Prepared request data.');

        $clientConfig = [
            'order_id' => $payment->getAdditionalInformation('order_id'),
            'status'   => $payment->getAdditionalInformation('status'),
            'store_id' => $storeId
        ];

        $this->logDebug('RefundRequest: Prepared client configuration.');

        $this->logDebug('RefundRequest: Build process completed successfully.');

        return ['request' => $request, 'client_config' => $clientConfig];
    }

    /**
     * Logs debug messages if debug is enabled.
     *
     * @param string $message
     * @param array|string $data
     * @param string $type
     */
    private function logDebug(string $message, $data = [], string $type = 'info'): void
    {
        if ($this->isDebugEnabled) {
            if ($type === 'error') {
                $this->logger->error($message, is_array($data) ? $data : ['data' => $data]);
            } else {
                $this->logger->info($message, is_array($data) ? $data : ['data' => $data]);
            }
        }
    }
}
