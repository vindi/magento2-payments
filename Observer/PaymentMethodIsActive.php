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

namespace Vindi\VP\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Vindi\VP\Helper\Data as HelperData;
use Vindi\VP\Model\Ui\CreditCard\ConfigProvider;

class PaymentMethodIsActive implements ObserverInterface
{
    protected $helper;

    public function __construct(
        HelperData $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $methodCode = $event->getMethodInstance()->getCode();

        if (
            $methodCode == ConfigProvider::CODE
            && empty($this->helper->getConfig('cctypes', $methodCode))
        ) {
            /** @var DataObject $result */
            $result = $observer->getEvent()->getResult();
            $result->setData('is_available', false);
        }
    }
}
