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

namespace Vindi\VP\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Vindi\VP\Helper\Data as VindiHelper;

class ChangePaymentBeforePlace implements ObserverInterface
{
    /**
     * @var VindiHelper
     */
    private $vindiHelper;

    /**
     * @param VindiHelper $vindiHelper
     */
    public function __construct(
        VindiHelper $vindiHelper
    ) {
        $this->vindiHelper = $vindiHelper;
    }

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        $allowedMethods = $this->vindiHelper->getAllowedMethods();

        if ($order->getData() && in_array($paymentMethod, $allowedMethods)) {
            $order->getPayment()->setMethod('vindi_payment_link_' . $paymentMethod);
        }
    }
}
