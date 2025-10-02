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
 */

namespace Vindi\VP\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Vindi\VP\Helper\Data;
use Vindi\VP\Model\PaymentLinkService;

class Success implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var PaymentLinkService
     */
    private $paymentLinkService;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param PageFactory $resultPageFactory
     * @param PaymentLinkService $paymentLinkService
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param Data $helperData
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        PageFactory $resultPageFactory,
        PaymentLinkService $paymentLinkService,
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        Data $helperData,
        ManagerInterface $messageManager
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentLinkService = $paymentLinkService;
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->helperData = $helperData;
        $this->messageManager = $messageManager;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $result = $this->resultPageFactory->create();
        $orderId = $this->request->getParam('order_id');

        try {
            if (!$orderId) {
                $this->messageManager->addWarningMessage(
                    __('The order ID is missing or invalid. Please contact support or try again.')
                );
                return $this->redirectFactory->create()->setPath('/');
            }

            $paymentLink = $this->paymentLinkService->getPaymentLinkByOrderId($orderId);

            if ($paymentLink && $paymentLink->getSuccessPageAccessed()) {
                $this->messageManager->addWarningMessage(
                    __('The payment success page has already been accessed.')
                );
                return $this->redirectFactory->create()->setPath('/');
            }

            $order = $this->paymentLinkService->getOrderByOrderId($orderId);
            $orderStatus = $order->getStatus();
            $configStatus = $this->helperData->getConfig('order_status', $order->getPayment()->getMethod());
            $isCcMethod = str_contains($order->getPayment()->getMethod(), 'cc');

            if (!$isCcMethod && $orderStatus !== $configStatus) {
                return $this->redirectFactory->create()->setPath('/');
            }

            $paymentLink->setSuccessPageAccessed(true);
            $this->paymentLinkService->savePaymentLink($paymentLink);

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while processing your request. Please try again later.')
            );
            return $this->redirectFactory->create()->setPath('/');
        }

        return $result;
    }
}
