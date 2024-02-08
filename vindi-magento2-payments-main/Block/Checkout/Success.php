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

namespace Vindi\VP\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Helper\Data as PaymentHelper;

class Success extends Template
{
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * @param Template\Context $context
     * @param Session $checkoutSession
     * @param Context $httpContext
     * @param PaymentHelper $paymentHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        Context $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentHelper = $paymentHelper;
        $this->checkoutSession = $checkoutSession;
        $this->httpContext = $httpContext;
        $this->order = $this->checkoutSession->getLastRealOrder();
    }

    /**
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getPayment()
    {
        /** @var \Magento\Payment\Model\MethodInterface $payment */
        $payment = $this->order->getPayment()->getMethodInstance();
        return $payment;
    }

    /**
     * Return payment info block as html
     * @return string
     * @throws \Exception
     */
    public function getInfoBlockHtml()
    {
        /** @var  $infoBlock */
        $infoBlock = $this->paymentHelper->getInfoBlock(
            $this->order->getPayment()
        );
        $infoBlock->setIsSecureMode(true);

        return $infoBlock->toHtml();
    }
}
