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
use Vindi\VP\Helper\Data as VindiHelper;

class OrderService
{
    /**
     * @var PaymentLinkService
     */
    private PaymentLinkService $paymentLinkService;

    /**
     * @var VindiHelper
     */
    private VindiHelper $vindiHelper;

    /**
     * @param PaymentLinkService $paymentLinkService
     * @param VindiHelper $vindiHelper
     */
    public function __construct(
        PaymentLinkService  $paymentLinkService,
        VindiHelper $vindiHelper
    ) {
        $this->paymentLinkService = $paymentLinkService;
        $this->vindiHelper = $vindiHelper;
    }

    /**
     * @param \Magento\Sales\Model\Service\OrderService $subject
     * @param $result
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function afterPlace(\Magento\Sales\Model\Service\OrderService $subject, $result)
    {
        $paymentMethod = $result->getPayment()->getMethod();
        $allowedMethods = $this->vindiHelper->getAllowedMethods();

        if (in_array($paymentMethod, $allowedMethods)) {
            $this->paymentLinkService->createPaymentLink($result->getId(), str_replace('vindi_payment_link_', '', $paymentMethod));
            $this->paymentLinkService->sendPaymentLinkEmail($result->getId());
        }

        return $result;
    }
}
