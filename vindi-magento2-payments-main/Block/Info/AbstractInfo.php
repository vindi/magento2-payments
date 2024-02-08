<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 */

namespace Vindi\VP\Block\Info;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Config;

class AbstractInfo extends ConfigurableInfo
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Config
     */
    protected $paymentConfig;

    /**
     * Info constructor.
     * @param Context $context
     * @param ConfigInterface $config
     * @param Config $paymentConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        Config $paymentConfig,
        array $data = []
    ) {
        parent::__construct($context, $config, $data);
        $this->paymentConfig = $paymentConfig;
        $this->config = $config;

        if (isset($data['pathPattern'])) {
            $this->config->setPathPattern($data['pathPattern']);
        }

        if (isset($data['methodCode'])) {
            $this->config->setMethodCode($data['methodCode']);
        }
    }

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->setTemplate($this->_template);
    }

    /**
     * Prepare payment information
     *
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = \Magento\Payment\Block\Info::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();
        $storedFields = explode(',', (string)$this->config->getValue('paymentInfoKeys'));
        if (!$this->isAdmin()) {
            $storedFields = array_diff(
                $storedFields,
                explode(',', (string)$this->config->getValue('privateInfoKeys'))
            );
        }

        foreach ($storedFields as $field) {
            if ($payment->getAdditionalInformation($field) !== null) {
                $this->setDataToTransfer(
                    $transport,
                    $field,
                    $payment->getAdditionalInformation($field)
                );
            }
        }

        return $transport;
    }

    /**
     * Returns label
     *
     * @param string $field
     * @return \Magento\Framework\Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string|array $value
     * @return string | Phrase
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getValueView($field, $value)
    {
        if (is_array($value)) {
            $value = $this->toJson($value);
        }
        return __($value);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAdmin()
    {
        return ($this->_appState->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML);
    }

}
