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
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Vindi\VP\Model\PaymentLinkService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Api\OrderRepositoryInterface;

class Index implements HttpGetActionInterface
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
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @param PageFactory $resultPageFactory
     * @param PaymentLinkService $paymentLinkService
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param ManagerInterface $messageManager
     * @param CustomerSession $customerSession
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        PageFactory        $resultPageFactory,
        PaymentLinkService $paymentLinkService,
        RequestInterface   $request,
        RedirectFactory    $redirectFactory,
        ManagerInterface   $messageManager,
        CustomerSession    $customerSession,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentLinkService = $paymentLinkService;
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $result = $this->resultPageFactory->create();
        $result->getConfig()->getTitle()->set(__('Payment Link'));
        $hash = $this->request->getParam('hash');
        $paymentLink = $this->paymentLinkService->getPaymentLinkByHash($hash);
        $isLinkExpired = false;

        if (!$paymentLink->getData()) {
            $this->messageManager->addWarningMessage(
                __('The link you used has expired or does not exist anymore. Please contact support or try again.')
            );
            return $this->redirectFactory->create()->setPath('/');
        }

        if ($this->paymentLinkService->isLinkExpired($paymentLink->getCreatedAt())) {
            $this->paymentLinkService->updatePaymentLinkStatusToExpired($paymentLink);
            $isLinkExpired = true;
        }

        if ($paymentLink->getData('status') !== 'pending') {
            $this->messageManager->addWarningMessage(
                __('Only pending payment links can be accessed.')
            );
            return $this->redirectFactory->create()->setPath('/');
        }

        if (!$this->customerSession->isLoggedIn()) {
            $this->customerSession->setBeforeAuthUrl($this->request->getUriString());

            $this->messageManager->addWarningMessage(
                __('You need to log in to access the payment link.')
            );
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }

        $customerId = $paymentLink->getData('customer_id');
        if (!$customerId) {
            $orderId = $paymentLink->getData('order_id');
            $order = $this->orderRepository->get($orderId);
            $customerId = $order->getCustomerId();
        }

        $loggedInCustomerId = $this->customerSession->getCustomerId();

        if ($customerId !== $loggedInCustomerId) {
            $this->messageManager->addWarningMessage(
                __('Only the customer associated with this payment link can access it.')
            );
            return $this->redirectFactory->create()->setPath('/');
        }

        return $result;
    }
}
