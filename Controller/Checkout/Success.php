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

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Vindi\VP\Helper\Data;
use Vindi\VP\Model\PaymentLinkService;

class Success implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    /**
     * @var PaymentLinkService
     */
    private PaymentLinkService $paymentLinkService;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var RedirectFactory
     */
    private RedirectFactory $redirectFactory;

    /**
     * @var Data
     */
    private Data $helperData;

    /**
     * @param PageFactory $resultPageFactory
     * @param PaymentLinkService $paymentLinkService
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param Data $helperData
     */
    public function __construct(
        PageFactory $resultPageFactory,
        PaymentLinkService $paymentLinkService,
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        Data $helperData
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentLinkService = $paymentLinkService;
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->helperData = $helperData;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $result = $this->resultPageFactory->create();
        $orderId = $this->request->getParam('order_id');
        $order = $this->paymentLinkService->getOrderByOrderId($orderId);
        $orderStatus = $order->getStatus();
        $configStatus = $this->helperData->getConfig('order_status', $order->getPayment()->getMethod());
        $isCcMethod = str_contains($order->getPayment()->getMethod(), 'cc');

        try {
            if (!$orderId || (!$isCcMethod && $orderStatus !== $configStatus)) {
                return $this->redirectFactory->create()->setPath('noroute');
            }
        } catch (\Exception $e) {
            return $this->redirectFactory->create()->setPath('noroute');
        }

        return $result;
    }
}
