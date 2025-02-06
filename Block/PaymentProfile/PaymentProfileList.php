<?php
namespace Vindi\VP\Block\PaymentProfile;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Vindi\VP\Model\Config\Source\CardImages as CardImagesSource;
use Vindi\VP\Model\ResourceModel\CreditCard\Collection as CreditCardCollection;

/**
 * Class PaymentProfileList
 * @package Vindi\VP\Block\PaymentProfile
 */
class PaymentProfileList extends Template
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

    /**
     * @param Context $context
     * @param CreditCardCollection $paymentProfileCollection
     * @param CustomerSession $customerSession
     * @param CardImagesSource $creditCardTypeSource
     * @param array $data
     */
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
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getPaymentProfiles()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'custom.paymentProfile.list.pager'
            )->setAvailableLimit([10=>10, 20=>20, 50=>50])->setShowPerPage(true)->setCollection(
                $this->getPaymentProfiles()
            );
            $this->setChild('pager', $pager);
            $this->getPaymentProfiles()->load();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    public function getPaymentProfiles()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            if ($customerId) {
                $paymentProfileCollection = $this->paymentProfileCollection->addFieldToFilter('customer_id', $customerId)
                    ->setOrder('created_at', 'DESC');

                return $paymentProfileCollection;
            }
        }
        return null;
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    public function getCountPaymentProfiles()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            if ($customerId) {
                $paymentProfileCollection = $this->paymentProfileCollection->addFieldToFilter('customer_id', $customerId)
                    ->setOrder('created_at', 'DESC');

                return $paymentProfileCollection->getSize();
            }
        }

        return 0;
    }

    /**
     * Get credit card image by type
     *
     * @param $ccType
     * @return string
     */
    public function getCreditCardImage($ccType)
    {
        $ccType = strtolower(str_replace(' ', '_', $ccType));

        if ($ccType == 'amex') {
            $ccType = 'american_express';
        }

        $creditCardOptionArray = $this->creditCardTypeSource->toOptionArray();

        foreach ($creditCardOptionArray as $creditCardOption) {
            if ($creditCardOption['label']->getText() == $ccType) {
                return $creditCardOption['value'];
            }
        }
        return '';
    }
}
