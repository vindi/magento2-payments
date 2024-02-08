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

namespace Vindi\VP\Model\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class Interest extends AbstractTotal
{
    /**
     * @param Invoice $invoice
     *
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $invoice->setVindiInterestAmount(0);
        $invoice->setBaseVindiInterestAmount(0);

        if (!$order->hasInvoices()) {
            $amount = $order->getVindiInterestAmount();
            $baseAmount = $order->getBaseVindiInterestAmount();
            if ($amount) {
                $invoice->setVindiInterestAmount($amount);
                $invoice->setBaseVindiInterestAmount($baseAmount);
                $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
                $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAmount);
            }
        }

        return $this;
    }
}
