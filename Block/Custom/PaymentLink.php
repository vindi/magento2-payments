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

namespace Vindi\VP\Block\Custom;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Vindi\VP\Helper\Data as Helper;
use Vindi\VP\Model\PaymentLinkService;
use Vindi\VP\Model\Ui\CreditCard\ConfigProvider;
use Magento\Tax\Model\TaxConfigProvider;

class PaymentLink extends Template
{
    /**
     * @var array
     */
    protected $icons = [];

    /**
     * @var PaymentLinkService
     */
    private $paymentLinkService;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @var TaxConfigProvider
     */
    public $taxConfigProvider;

    /**
     * @param Context $context
     * @param PaymentLinkService $paymentLinkService
     * @param ConfigProvider $configProvider
     * @param FormKey $formKey
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param PriceHelper $priceHelper
     * @param TaxConfigProvider $taxConfigProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        PaymentLinkService $paymentLinkService,
        ConfigProvider $configProvider,
        FormKey $formKey,
        Helper $helper,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        PriceHelper $priceHelper,
        TaxConfigProvider $taxConfigProvider,
        array $data = [])
    {
        $this->paymentLinkService = $paymentLinkService;
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->formKey = $formKey;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->priceHelper = $priceHelper;
        $this->taxConfigProvider = $taxConfigProvider;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->getRequest()->getParam('hash');
    }

    /**
     * @return mixed
     */
    public function getPaymentLink()
    {
        return $this->paymentLinkService->getPaymentLinkByHash($this->getHash());
    }

    /**
     * @param \Vindi\VP\Model\PaymentLink $paymentLink
     * @return void
     */
    public function deletePaymentLink(\Vindi\VP\Model\PaymentLink $paymentLink)
    {
        $this->paymentLinkService->deletePaymentLink($paymentLink);
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        return $this->paymentLinkService->getOrderByOrderId($this->getPaymentLink()->getOrderId());
    }

    /**
     * @return false|string
     */
    public function getIcons()
    {
        $icons = [];
        foreach ($this->configProvider->getIcons() as $index => $icon) {
            $icons[$index] = [
                'height' => $icon['height'],
                'title' => $icon['title']->getText(),
                'url' => $icon['url'],
                'width' => $icon['width']
            ];
        }
        return json_encode($icons);
    }

    /**
     * @throws LocalizedException
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @throws NoSuchEntityException
     */
    public function isSandbox()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->helper->getGeneralConfig('use_sandbox', $storeId);
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @param int|string $customerId
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerById($customerId)
    {
        return $this->customerRepository->getById($customerId);
    }

    /**
     * @param float $price
     * @return float|string
     */
    public function getFormattedPrice(float $price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * @return string
     */
    public function getInstructions()
    {
        $method = str_replace('vindi_payment_link_','', $this->getPaymentLink()->getVindiPaymentMethod());
        return $this->helper->getConfig('checkout_instructions', $method);
    }

    /**
     * @return string
     */
    public function getDisplayShippingMode()
    {
        return $this->taxConfigProvider->getDisplayShippingMode();
    }

    /**
     * @return bool
     */
    public function isTaxDisplayedInGrandTotal()
    {
        return $this->taxConfigProvider->isTaxDisplayedInGrandTotal();
    }

}
