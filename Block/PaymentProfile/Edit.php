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
class Edit extends Template
{
    /**
     * @var CreditCardCollection
     */
    protected $_paymentProfileCollection;

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
        $this->_paymentProfileCollection = $paymentProfileCollection;
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

    /**
     * @return CreditCardCollection
     */
    public function getPaymentProfiles()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            $this->_paymentProfileCollection->addFieldToFilter('customer_id', $customerId);
        }

        return $this->_paymentProfileCollection;
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
    public function getCurrentPaymentProfile()
    {
        $profileId = $this->getRequest()->getParam('id');
        if ($profileId) {
            $profile = $this->_paymentProfileCollection->getItemById($profileId);
            return $profile;
        }
        return null;
    }

}
