<?php

namespace Vindi\VP\Observer\Sales;

use Vindi\VP\Helper\Data;
use Vindi\VP\Helper\Order as HelperOrder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Invoice;

class QuoteSubmitSuccess implements ObserverInterface
{
    /** @var Data  */
    protected $helper;

    /** @var HelperOrder  */
    protected $helperOrder;

    /**
     * @param Data $helper
     * @param HelperOrder $helperOrder
     */
    public function __construct(
        Data $helper,
        HelperOrder $helperOrder
    ) {
        $this->helper = $helper;
        $this->helperOrder = $helperOrder;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();
            $apiStatus = (int) $payment->getAdditionalInformation('status');
            if ($payment->getMethod() == \Vindi\VP\Model\Ui\CreditCard\ConfigProvider::CODE) {
                if ($apiStatus == HelperOrder::STATUS_APPROVED) {
                    $this->helperOrder->captureOrder($order, Invoice::CAPTURE_ONLINE);
                }
            }
        } catch (\Exception $e) {
            $this->helper->log('CAPTURE ERROR', $e->getMessage());
        }
    }
}
