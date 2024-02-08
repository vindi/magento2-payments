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

namespace Vindi\VP\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Config;

class Pix extends AbstractInfo
{
    protected $_template = 'Vindi_VP::payment/info/pix.phtml';

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Config
     */
    protected $paymentConfig;

    /**
     * @var  DateTime
     */
    protected $date;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * BankSlip constructor.
     * @param Context $context
     * @param ConfigInterface $config
     * @param Config $paymentConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param DateTime $date
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        Config $paymentConfig,
        PriceCurrencyInterface $priceCurrency,
        DateTime $date,
        array $data = []
    ) {
        parent::__construct($context, $config, $paymentConfig, $data);
        $this->paymentConfig = $paymentConfig;
        $this->date = $date;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->setTemplate($this->_template);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getEmv(): string
    {
        $payment = $this->getInfo();
        return (string) $payment->getAdditionalInformation('qr_code_emv');
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getQRCodeImage(): string
    {
        $payment = $this->getInfo();
        return (string) $payment->getAdditionalInformation('qr_code_url');
    }
}
