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
use Vindi\VP\Model\PaymentLinkService;

class SendTransaction implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var PaymentLinkService
     */
    private PaymentLinkService $paymentLinkService;

    /**
     * @var RequestInterface
     */
    private RequestInterface $httpRequest;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param PaymentLinkService $paymentLinkService
     * @param RequestInterface $httpRequest
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        PaymentLinkService $paymentLinkService,
        RequestInterface $httpRequest,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $messageManager
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentLinkService = $paymentLinkService;
        $this->httpRequest = $httpRequest;
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = ['success' => false];
        $orderId = $this->httpRequest->getParam('order_id');
        $paymentData = $this->httpRequest->getParam('payment_data');
        $order = $this->paymentLinkService->getOrderByOrderId($orderId);

        try {
            foreach ($paymentData['additional_data'] as $index => $data) {
                $order->getPayment()->setData($index, $data);
            }

            $order->getPayment()->setAdditionalInformation((array) $paymentData);
            $order->getPayment()->setMethod(str_replace('vindi_payment_link_','', $order->getPayment()->getMethod()));
            $order->getPayment()->place();
            $this->orderRepository->save($order);
            $result['success'] = true;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return $resultJson->setData(['success' => false, 'error' => $e->getMessage()]);
        }

        return $resultJson->setData($result);
    }
}
