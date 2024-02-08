<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi_VP
 */

namespace Vindi\VP\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Vindi\VP\Helper\Data;

class Fingerprint extends Template
{

    /**
     * @var Data $helper
     */
    protected $helper;

    /**
     * @param Template\Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    public function getFingerprintUrl(): string
    {
        return Data::FINGERPRINT_URL;
    }

    public function getIsSandbox(): int
    {
        return (int) $this->helper->getGeneralConfig('use_sandbox');
    }
}
