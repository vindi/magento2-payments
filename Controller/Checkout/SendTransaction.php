<?php

declare(strict_types=1);

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
 *
 */

namespace Vindi\VP\Controller\Checkout;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use Vindi\VP\Helper\Order as HelperOrder;
use Vindi\VP\Model\PaymentLinkService;

class SendTransaction implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var PaymentLinkService
     */
    private $paymentLinkService;

    /**
     * @var RequestInterface
     */
    private $httpRequest;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var HelperOrder
     */
    private $helperOrder;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param PaymentLinkService $paymentLinkService
     * @param RequestInterface $httpRequest
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $messageManager
     * @param HelperOrder $helperOrder
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        PaymentLinkService $paymentLinkService,
        RequestInterface $httpRequest,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $messageManager,
        HelperOrder $helperOrder
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentLinkService = $paymentLinkService;
        $this->httpRequest = $httpRequest;
        $this->messageManager = $messageManager;
        $this->helperOrder = $helperOrder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = ['success' => false];

        $orderId     = $this->httpRequest->getParam('order_id');
        $paymentData = $this->httpRequest->getParam('payment_data');

        if (!$orderId || !$paymentData) {
            return $resultJson->setData(['success' => false, 'error' => 'Invalid request parameters.']);
        }

        $order = $this->paymentLinkService->getOrderByOrderId($orderId);

        if (isset($paymentData['additional_data']['taxvat'])) {
            $paymentData['vindi_customer_taxvat'] = $paymentData['additional_data']['taxvat'];
        }

        try {
            foreach ($paymentData['additional_data'] as $index => $data) {
                $order->getPayment()->setData($index, $data);
            }

            $order->getPayment()->setAdditionalInformation((array) $paymentData);
            $order->getPayment()->setMethod(str_replace('vindi_payment_link_','', $order->getPayment()->getMethod()));
            $order->getPayment()->place();
            $this->orderRepository->save($order);
            $apiStatus = (int) $order->getPayment()->getAdditionalInformation('status');

            if ($order->getPayment()->getMethod() == \Vindi\VP\Model\Ui\CreditCard\ConfigProvider::CODE) {
                if ($apiStatus == HelperOrder::STATUS_APPROVED) {
                    $this->helperOrder->captureOrder($order, Invoice::CAPTURE_ONLINE);
                }
            }

            $paymentLink = $this->paymentLinkService->getPaymentLinkByOrderId($orderId);
            if ($paymentLink) {
                $paymentLink->setStatus('processed');
                $this->paymentLinkService->savePaymentLink($paymentLink);
            }

            $result['success'] = true;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return $resultJson->setData(['success' => false, 'error' => $e->getMessage()]);
        }

        return $resultJson->setData($result);
    }
}
