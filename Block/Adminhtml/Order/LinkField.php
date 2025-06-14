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

namespace Vindi\VP\Block\Adminhtml\Order;

use \Magento\Backend\Block\Template;
use \Magento\Backend\Block\Template\Context;
use Vindi\VP\Model\PaymentLinkService;

class LinkField extends Template
{
    const VINDI_PAYMENT_LINK = 'vindi_payment_link';

    /**
     * @var PaymentLinkService
     */
    private $paymentLinkService;

    /**
     * @param Context $context
     * @param PaymentLinkService $paymentLinkService
     * @param array $data
     */
    public function __construct(
        Context $context,
        PaymentLinkService $paymentLinkService,
        array $data = [])
    {
        $this->paymentLinkService = $paymentLinkService;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        return $orderId;
    }

    /**
     * @return string
     */
    public function getPaymentLink()
    {
        $paymentLinkData = $this->paymentLinkService->getPaymentLink($this->getOrderId());
        $isExpired = false;
        if ($paymentLinkData->getData()) $isExpired = $this->paymentLinkService->isLinkExpired($paymentLinkData->getCreatedAt());
        return $paymentLinkData->getLink() && !$isExpired ? $paymentLinkData->getLink() : '';
    }

    /**
     * @return string|null
     */
    public function getPaymentMethod()
    {
        $order = $this->paymentLinkService->getOrderByOrderId($this->getOrderId());

        if ($order->getData()) {
            return $order->getPayment()->getMethod();
        }
        return null;
    }

    /**
     * Check if the payment link status is "processed"
     *
     * @return bool
     */
    public function isLinkPaid(): bool
    {
        $paymentLink = $this->paymentLinkService->getPaymentLink($this->getOrderId());
        return $paymentLink->getStatus() === 'processed';
    }

    /**
     * Check if the payment link is expired
     *
     * @return bool
     */
    public function isLinkExpired(): bool
    {
        $paymentLink = $this->paymentLinkService->getPaymentLink($this->getOrderId());
        return $paymentLink->getStatus() === 'expired';
    }
}
