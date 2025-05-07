<?php

/**
 * Vindi
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 * @copyright   Copyright (c) Vindi
 *
 */

namespace Vindi\VP\Model\Ui\CreditCard;

use Vindi\VP\Helper\Data;
use Vindi\VP\Model\ResourceModel\CreditCard\CollectionFactory as CreditCardCollectionFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;

/**
 * Class ConfigProvider
 */
class ConfigProvider extends CcGenericConfigProvider
{
    public const CODE = 'vindi_vp_cc';

    protected $icons = [];

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @var CreditCardCollectionFactory
     */
    protected $creditCardCollectionFactory;

    /**
     * @param Session $checkoutSession
     * @param CustomerSession $customerSession
     * @param Data $helper
     * @param CcConfig $ccConfig
     * @param UrlInterface $urlBuilder
     * @param PaymentHelper $paymentHelper
     * @param Source $assetSource
     * @param CreditCardCollectionFactory $creditCardCollectionFactory
     */
    public function __construct(
        Session $checkoutSession,
        CustomerSession $customerSession,
        Data $helper,
        CcConfig $ccConfig,
        UrlInterface $urlBuilder,
        PaymentHelper $paymentHelper,
        Source $assetSource,
        CreditCardCollectionFactory $creditCardCollectionFactory
    ) {
        parent::__construct($ccConfig, $paymentHelper, [self::CODE]);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->urlBuilder = $urlBuilder;
        $this->assetSource = $assetSource;
        $this->creditCardCollectionFactory = $creditCardCollectionFactory;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        $methodCode = self::CODE;

        $customerTaxvat = '';
        $customer = $this->customerSession->getCustomer();
        if ($customer && $customer->getTaxvat()) {
            $taxVat = preg_replace('/[^0-9]/', '', (string) $customer->getTaxvat());
            $customerTaxvat = strlen($taxVat) == 11 ? $taxVat : '';
        }

        return [
            'payment' => [
                self::CODE => [
                    'grand_total' => $grandTotal,
                    'customer_taxvat' => $customerTaxvat,
                    'sandbox' => (int) $this->helper->getGeneralConfig('use_sandbox'),
                    'fingerprint_url' => \Vindi\VP\Helper\Data::FINGERPRINT_URL,
                    'icons' => $this->getIcons(),
                    'availableTypes' => $this->getCcAvailableTypes($methodCode),
                    'saved_cards' => $this->getSavedCreditCards()
                ],
                'ccform' => [
                    'grandTotal' => [$methodCode => $grandTotal],
                    'months' => [$methodCode => $this->getCcMonths()],
                    'years' => [$methodCode => $this->getCcYears()],
                    'hasVerification' => [$methodCode => $this->hasVerification($methodCode)],
                    'cvvImageUrl' => [$methodCode => $this->getCvvImageUrl()],
                    'urls' => [
                        $methodCode => [
                            'retrieve_installments' => $this->urlBuilder->getUrl('vindi_vp/installments/retrieve')
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get saved credit cards for logged-in customers
     *
     * @return array
     */
    public function getSavedCreditCards()
    {
        $savedCards = [];

        if (!$this->customerSession->isLoggedIn()) {
            return $savedCards;
        }

        $customerId = $this->customerSession->getCustomerId();
        $creditCardCollection = $this->creditCardCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId);

        foreach ($creditCardCollection as $creditCard) {
            $savedCards[] = [
                'id' => $creditCard->getId(),
                'card_number' => $creditCard->getData('cc_last_4'),
                'card_type' => $creditCard->getData('cc_type')
            ];
        }

        return $savedCards;
    }

    /**
     * Get icons for available payment methods
     *
     * @return array
     */
    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }

        $types = $this->getCcAvailableTypes(self::CODE);
        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset('Vindi_VP::images/cc/' . strtolower($code) . '.png');
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesize($asset->getSourceFile());
                    $this->icons[$code] = [
                        'url' => $asset->getUrl(),
                        'width' => $width,
                        'height' => $height,
                        'title' => __($label),
                    ];
                }
            }
        }

        return $this->icons;
    }
}
