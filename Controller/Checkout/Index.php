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
     * @param PageFactory $resultPageFactory
     * @param PaymentLinkService $paymentLinkService
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        PageFactory        $resultPageFactory,
        PaymentLinkService $paymentLinkService,
        RequestInterface   $request,
        RedirectFactory    $redirectFactory,
        ManagerInterface   $messageManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentLinkService = $paymentLinkService;
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
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

        if ($paymentLink->getData() && $this->paymentLinkService->isLinkExpired($paymentLink->getCreatedAt())) {
            $this->paymentLinkService->deletePaymentLink($paymentLink);
            $isLinkExpired = true;
        }

        if (!$paymentLink->getData() || $isLinkExpired) {
            $this->messageManager->addErrorMessage(__('This link has expired or is no longer available,
            please try another link or contact the shopkeeper to get a new payment link.'));
            return $this->redirectFactory->create()->setPath('/');
        }

        return $result;
    }
}
