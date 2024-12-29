<?php

namespace Vindi\VP\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Vindi\VP\Helper\Data as HelperData;

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
class AuthenticateButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Vindi_VP::system/config/button.phtml';

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * AuthenticateButton constructor.
     *
     * @param Context $context
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperData = $helperData;
    }

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get custom URL
     *
     * @return string|null
     */
    public function getCustomUrl()
    {
        $storeId = $this->_request->getParam('store');
        $consumerKey = $this->helperData->getConsumerKey($storeId);
        $isSandbox = $this->_scopeConfig->getValue('vindi_vp/general/use_sandbox', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

        if (empty($consumerKey)) {
            return null;
        }

        $baseUrl = $isSandbox
                ? 'https://tc-intermediador-sandbox.yapay.com.br/authentication'
            : 'https://tc.intermediador.yapay.com.br/authentication';

        return $baseUrl . '?consumer_key=' . urlencode($consumerKey);
    }

    /**
     * Get button HTML
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $url = $this->getCustomUrl();
        if (!$url) {
            return '<p style="color: red;">' . __('Please configure the Consumer Key before proceeding.') . '</p>';
        }

        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData([
            'id' => 'auth_button',
            'label' => __('Authenticate Application'),
            'onclick' => "window.open('$url', '_blank')",
        ]);

        return $button->toHtml();
    }

    /**
     * Render HTML for the button
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
