<?php

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

declare(strict_types=1);

namespace Vindi\VP\Plugin\Model\Service;

use Vindi\VP\Model\PaymentLinkService;

class OrderService
{
    /**
     * @var PaymentLinkService
     */
    private PaymentLinkService $paymentLinkService;

    /**
     * @param PaymentLinkService $paymentLinkService
     */
    public function __construct(
        PaymentLinkService  $paymentLinkService
    ) {
        $this->paymentLinkService = $paymentLinkService;
    }

    /**
     * @param \Magento\Sales\Model\Service\OrderService $subject
     * @param $result
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function afterPlace(\Magento\Sales\Model\Service\OrderService $subject, $result)
    {
        $this->paymentLinkService->createPaymentLink($result->getId(), str_replace('vindi_payment_link_','', $result->getPayment()->getMethod()));
        $this->paymentLinkService->sendPaymentLinkEmail($result->getId());
        return $result;
    }
}
