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

namespace Vindi\VP\Model\Ui\BankSlipPix;

use Vindi\VP\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\Action\Context;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'vindi_vp_bankslippix';

    /** @var MethodInterface */
    protected $methodInstance;

    /** @var Session */
    protected $checkoutSession;

    /** @var Context */
    protected $context;

    /** @var Data */
    protected $helper;

    /** @var Repository */
    protected $assetRepo;

    /** @var RequestInterface */
    protected $request;

    /** @var CustomerSession  */
    protected $customerSession;

    /**
     * @param Context $context
     * @param Repository $assetRepo
     * @param RequestInterface $request
     * @param PaymentHelper $paymentHelper
     * @param Session $checkoutSession
     * @param CustomerSession $customerSession
     * @param Data $helper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        Repository $assetRepo,
        RequestInterface $request,
        PaymentHelper $paymentHelper,
        Session $checkoutSession,
        CustomerSession $customerSession,
        Data $helper
    ) {
        $this->context = $context;
        $this->assetRepo = $assetRepo;
        $this->request = $request;
        $this->methodInstance = $paymentHelper->getMethodInstance(self::CODE);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
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
        $customerTaxvat = '';
        $customer = $this->customerSession->getCustomer();
        if ($customer && $customer->getTaxvat()) {
            $taxVat = preg_replace('/[^0-9]/', '', (string) $customer->getTaxvat());
            $customerTaxvat = strlen($taxVat) == 11 ? $taxVat : '';
        }

        return [
            'payment' => [
                self::CODE => [
                    'grand_total' => $this->checkoutSession->getQuote()->getGrandTotal(),
                    'checkout_instructions' =>  $this->helper->getConfig('checkout_instructions', self::CODE),
                    'sandbox' => (int) $this->helper->getGeneralConfig('use_sandbox'),
                    'fingerprint_url' => \Vindi\VP\Helper\Data::FINGERPRINT_URL,
                    'customer_taxvat' =>  $customerTaxvat
                ]
            ]
        ];
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = []): string
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (\Exception $e) {
            return '';
        }
    }
}
