<?php
namespace Vindi\VP\Block\PaymentProfile;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Vindi\VP\Model\Config\Source\CardImages as CardImagesSource;
use Vindi\VP\Model\ResourceModel\CreditCard\Collection as CreditCardCollection;

/**
 * Class Edit
 * @package Vindi\VP\Block

 */
class Remove extends Template
{
    /**
     * @var CreditCardCollection
     */
    protected $paymentProfileCollection;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CardImagesSource
     */
    protected $creditCardTypeSource;

    public function __construct(
        Context $context,
        CreditCardCollection $paymentProfileCollection,
        CustomerSession $customerSession,
        CardImagesSource $creditCardTypeSource,
        array $data = []
    ) {
        $this->paymentProfileCollection = $paymentProfileCollection;
        $this->customerSession = $customerSession;
        $this->creditCardTypeSource = $creditCardTypeSource;
        parent::__construct($context, $data);
    }

    /**
     * @param $ccType
     * @return mixed|void
     */
    public function getCreditCardImage($ccType)
    {
        $creditCardOptionArray = $this->creditCardTypeSource->toOptionArray();

        foreach ($creditCardOptionArray as $creditCardOption) {
            if ($creditCardOption['label']->getText() == $ccType) {
                return $creditCardOption['value'];
            }
        }
    }

    /**
     * Retrieve current payment profile based on ID in URL.
     *
     * @return \Vindi\VP\Model\PaymentProfile|null
     */
    public function getPaymentProfile()
    {
        $profileId = $this->getRequest()->getParam('id');
        if ($profileId) {
            return $this->paymentProfileCollection->getItemById($profileId);
        }
        return null;
    }
}
