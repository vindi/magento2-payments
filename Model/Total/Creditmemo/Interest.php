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

namespace Vindi\VP\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class Interest extends AbstractTotal
{
    /**
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $creditmemo->setVindiInterestAmount(0);
        $creditmemo->setBaseVindiInterestAmount(0);

        if (!$order->hasCreditmemos()) {
            $amount = $order->getVindiInterestAmount();
            $baseAmount = $order->getBaseVindiInterestAmount();
            if ($amount) {
                $creditmemo->setVindiInterestAmount($amount);
                $creditmemo->setBaseVindiInterestAmount($baseAmount);
                $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
                $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAmount);
            }
        }

        return $this;
    }
}
