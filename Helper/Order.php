<?php
declare(strict_types=1);

namespace Vindi\VP\Helper;

use BaconQrCode\Renderer\ImageRenderer as QrCodeImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd as QrCodeImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle as QrCodeRendererStyle;
use BaconQrCode\Writer as QrCodeWritter;
use Magento\Framework\Exception\LocalizedException;
use Vindi\VP\Helper\Data as HelperData;
use Vindi\VP\Gateway\Http\Client;
use Vindi\VP\Gateway\Http\Client\Api;
use Vindi\VP\Model\Ui\CreditCard\ConfigProvider as CcConfigProvider;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Payment as ResourcePayment;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\App\Emulation;

class Order extends \Magento\Payment\Helper\Data
{
    const STATUS_APPROVED = 6;
    const STATUS_PENDING = 4;
    const STATUS_DENIED = 7;
    const STATUS_REFUNDED = 89;
    const STATUS_CONTESTATION = 24;
    const STATUS_CHARGEBACK = 24;
    const STATUS_MONITORING = 87;
    const DEFAULT_QRCODE_WIDTH = 400;
    const DEFAULT_QRCODE_HEIGHT = 400;
    const DEFAULT_EXPIRATION_TIME = 30;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @var CreditmemoFactory
     */
    protected $creditmemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditmemoService;

    /**
     * @var ResourcePayment
     */
    protected $resourcePayment;

    /**
     * @var CollectionFactory
     */
    protected $orderStatusCollectionFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param OrderFactory $orderFactory
     * @param CreditmemoFactory $creditmemoFactory
     * @param OrderRepository $orderRepository
     * @param InvoiceRepository $invoiceRepository
     * @param CreditmemoService $creditmemoService
     * @param ResourcePayment $resourcePayment
     * @param CollectionFactory $orderStatusCollectionFactory
     * @param Filesystem $filesystem
     * @param Client $client
     * @param Api $api
     * @param DateTime $dateTime
     * @param HelperData $helperData
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        OrderFactory $orderFactory,
        CreditmemoFactory $creditmemoFactory,
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository,
        CreditmemoService $creditmemoService,
        ResourcePayment $resourcePayment,
        CollectionFactory $orderStatusCollectionFactory,
        Filesystem $filesystem,
        Client $client,
        Api $api,
        DateTime $dateTime,
        HelperData $helperData
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->helperData = $helperData;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->resourcePayment = $resourcePayment;
        $this->filesystem = $filesystem;
        $this->dateTime = $dateTime;
        $this->client = $client;
        $this->api = $api;
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
    }

    /**
     * Update Order Status.
     *
     * @param SalesOrder $order
     * @param string $vindiStatus
     * @param array $content
     * @param float $amount
     * @param bool $callback
     * @return bool
     */
    public function updateOrder(
        SalesOrder $order,
        string $vindiStatus,
        array $content,
        float $amount,
        bool $callback = false
    ): bool {
        try {
            /** @var Payment $payment */
            $payment = $order->getPayment();
            $orderStatus = $payment->getAdditionalInformation('status');
            $order->addCommentToStatusHistory(__('Callback received %1 -> %2', $orderStatus, $vindiStatus));

            if ($vindiStatus !== $orderStatus) {
                if ($vindiStatus == self::STATUS_APPROVED) {
                    if ($order->canInvoice()) {
                        $this->invoiceOrder($order, $amount);
                    }

                    $updateStatus = $order->getIsVirtual()
                        ? $this->helperData->getConfig('paid_virtual_order_status')
                        : $this->helperData->getConfig('paid_order_status');

                    $message = __('Your payment for the order %1 was confirmed', $order->getIncrementId());
                    $order->addCommentToStatusHistory($message, $updateStatus, true);
                } elseif ($vindiStatus == self::STATUS_DENIED) {
                    $order = $this->cancelOrder($order, $amount, $callback);
                } elseif ($vindiStatus == self::STATUS_REFUNDED) {
                    $order = $this->refundOrder($order, $amount, $callback);
                }

                $payment->setAdditionalInformation('status', $vindiStatus);
                if (isset($content['status_name'])) {
                    $payment->setAdditionalInformation('status_name', $content['status_name']);
                }
            }

            $this->orderRepository->save($order);
            $this->savePayment($payment);

            return true;
        } catch (\Exception $e) {
            $this->helperData->log($e->getMessage());
        }

        return false;
    }

    /**
     * Save Payment.
     *
     * @param Payment $payment
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function savePayment(Payment $payment): void
    {
        $this->resourcePayment->save($payment);
    }

    /**
     * Invoice Order.
     *
     * @param SalesOrder $order
     * @param float $amount
     * @return void
     */
    protected function invoiceOrder(SalesOrder $order, float $amount): void
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        $payment->setParentTransactionId($payment->getLastTransId());
        $payment->registerCaptureNotification($amount);
    }

    /**
     * Cancel Order.
     *
     * @param SalesOrder $order
     * @param float $amount
     * @param bool $callback
     * @return SalesOrder
     * @throws LocalizedException
     */
    public function cancelOrder(SalesOrder $order, float $amount, bool $callback = false): SalesOrder
    {
        if ($order->canCreditmemo()) {
            $creditMemo = $this->creditmemoFactory->createByOrder($order);
            $this->creditmemoService->refund($creditMemo, true);
        } elseif ($order->canCancel()) {
            $order->cancel();
        }

        $cancelledStatus = $this->helperData->getConfig(
            'cancelled_order_status',
            $order->getPayment()->getMethod(),
            'payment',
            $order->getStoreId()
        ) ?: false;

        $order->addCommentToStatusHistory(__('The order %1 was cancelled. Amount of %2', $cancelledStatus, $amount));

        return $order;
    }

    /**
     * Refund Order.
     *
     * @param SalesOrder $order
     * @param float $amount
     * @param bool $callback
     * @return SalesOrder
     * @throws LocalizedException
     */
    public function refundOrder(SalesOrder $order, float $amount, bool $callback = false): SalesOrder
    {
        if ((float)$order->getBaseGrandTotal() === $amount) {
            return $this->cancelOrder($order, $amount, $callback);
        }

        $totalRefunded = (float)$order->getTotalRefunded() + $amount;
        $order->setTotalRefunded($totalRefunded);
        $order->addCommentToStatusHistory(__('The order had the amount refunded by Vindi. Amount of %1', $amount));

        return $order;
    }

    /**
     * Create credit memo for order.
     *
     * @param SalesOrder $order
     * @return void
     * @throws LocalizedException
     */
    public function credimemoOrder(SalesOrder $order): void
    {
        $creditMemo = $this->creditmemoFactory->createByOrder($order);
        $this->creditmemoService->refund($creditMemo);
    }

    /**
     * Capture Order.
     *
     * @param SalesOrder $order
     * @param string $captureCase
     * @return void
     */
    public function captureOrder(SalesOrder $order, string $captureCase = 'online'): void
    {
        if ($order->canInvoice()) {
            /** @var Invoice $invoice */
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase($captureCase);
            $invoice->register();
            $invoice->pay();

            $this->invoiceRepository->save($invoice);

            $order->getPayment()->setAdditionalInformation('captured', true);
            $this->orderRepository->save($order);
        }
    }

    /**
     * Set transaction information in Payment.
     *
     * @param Payment $payment
     * @param array $content
     * @param string $prefix
     * @return Payment
     */
    protected function setTransactionInformation(Payment $payment, array $content, string $prefix = ''): Payment
    {
        foreach ($content as $key => $value) {
            if (!is_array($value)) {
                $payment->setAdditionalInformation($prefix . $key, $value);
            }
        }
        return $payment;
    }

    /**
     * Update default additional information in Payment.
     *
     * @param Payment $payment
     * @param array $content
     * @return Payment
     */
    public function updateDefaultAdditionalInfo(Payment $payment, array $content): Payment
    {
        try {
            $payment = $this->setTransactionInformation($payment, $content);
            $tid = $content['token_transaction'];

            if (isset($content['payment'])) {
                $paymentResponse = $content['payment'];
                $payment = $this->setTransactionInformation($payment, $paymentResponse);
                if (!empty($paymentResponse['tid'])) {
                    $tid = $paymentResponse['tid'];
                }
            }

            $payment->setTransactionId($tid);
            $payment->setLastTransId($tid);
            $payment->setAdditionalInformation('tid', $tid);

            if (isset($content['token_transaction'])) {
                $payment->setTransactionId($content['token_transaction']);
            }

            $payment->setAdditionalInformation('status', $content['status_id'] ?? '');
            $payment->setIsTransactionClosed(false);
        } catch (\Exception $e) {
            $this->_logger->warning($e->getMessage());
        }

        return $payment;
    }

    /**
     * Update bank slip additional information in Payment.
     *
     * @param Payment $payment
     * @param array $content
     * @return Payment
     */
    public function updateBankSlipAdditionalInfo(Payment $payment, array $content): Payment
    {
        try {
            if (isset($content['payment'])) {
                $paymentResponse = $content['payment'];
                $payment->setAdditionalInformation('bank_slip_url', $paymentResponse['url_payment']);
                $payment->setAdditionalInformation('bank_slip_number', $paymentResponse['linha_digitavel']);
            }
        } catch (\Exception $e) {
            $this->_logger->warning($e->getMessage());
        }

        return $payment;
    }

    /**
     * Update pix additional information in Payment.
     *
     * @param Payment $payment
     * @param array $content
     * @return Payment
     */
    public function updatePixAdditionalInfo(Payment $payment, array $content): Payment
    {
        try {
            if (isset($content['payment'])) {
                $paymentResponse = $content['payment'];
                $payment->setAdditionalInformation('qr_code_emv', $paymentResponse['qrcode_original_path']);
                $payment->setAdditionalInformation('qr_code_original_url', $paymentResponse['qrcode_path'] ?? '');
                $QRCodeUrl = $this->generateQrCode($payment, $paymentResponse['qrcode_original_path']);
                $payment->setAdditionalInformation('qr_code_url', $QRCodeUrl);
            }

            $payment->setIsTransactionClosed(false);
        } catch (\Exception $e) {
            $this->_logger->warning($e->getMessage());
        }

        return $payment;
    }

    /**
     * Update refunded additional information in Payment.
     *
     * @param Payment $payment
     * @param array $transaction
     * @return Payment
     */
    public function updateRefundedAdditionalInformation(Payment $payment, array $transaction): Payment
    {
        if (isset($transaction['refunds'])) {
            foreach ($transaction['refunds'] as $i => $refund) {
                $this->setTransactionInformation($payment, $refund, 'refund-' . $i . '-');
            }
        }
        return $payment;
    }

    /**
     * Generate QR code for pix payment.
     *
     * @param Payment $payment
     * @param string $qrCode
     * @return string
     */
    public function generateQrCode($payment, string $qrCode): string
    {
        $pixUrl = '';
        if ($qrCode) {
            try {
                $renderer = new QrCodeImageRenderer(
                    new QrCodeRendererStyle(self::DEFAULT_QRCODE_WIDTH),
                    new QrCodeImagickImageBackEnd()
                );
                $writer = new QrCodeWritter($renderer);
                $pixQrCode = $writer->writeString($qrCode);

                $filename = 'vindi_vp/pix-' . $payment->getOrder()->getIncrementId() . '.png';
                $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                $media->writeFile($filename, $pixQrCode);

                $pixUrl = $this->helperData->getMediaUrl() . $filename;
            } catch (\Exception $e) {
                $this->helperData->log($e->getMessage());
            }
        }

        return $pixUrl;
    }

    /**
     * Load order by increment ID.
     *
     * @param string $incrementId
     * @return SalesOrder
     */
    public function loadOrder(string $incrementId): SalesOrder
    {
        $order = $this->orderFactory->create();
        if ($incrementId) {
            $order->loadByIncrementId($incrementId);
        }
        return $order;
    }

    /**
     * Get status state.
     *
     * @param string $status
     * @return string
     */
    public function getStatusState($status): string
    {
        if ($status) {
            $statuses = $this->orderStatusCollectionFactory
                ->create()
                ->joinStates()
                ->addFieldToFilter('main_table.status', $status);

            if ($statuses->getSize()) {
                return $statuses->getFirstItem()->getState();
            }
        }
        return '';
    }

    /**
     * Get payment status state.
     *
     * @param Payment $payment
     * @return string
     */
    public function getPaymentStatusState(Payment $payment): string
    {
        $defaultState = $payment->getOrder()->getState();
        $paymentMethod = $payment->getMethodInstance();
        if (!$paymentMethod) {
            return $defaultState;
        }

        $status = $paymentMethod->getConfigData('order_status');
        if (!$status) {
            return $defaultState;
        }

        $state = $this->getStatusState($status);
        if (!$state) {
            return $defaultState;
        }

        return $state;
    }

    /**
     * Check if order processing can be skipped.
     *
     * @param string $state
     * @return bool
     */
    public function canSkipOrderProcessing($state): bool
    {
        return $state !== SalesOrder::STATE_PROCESSING;
    }
}
