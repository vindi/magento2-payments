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

namespace Vindi\VP\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class Data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Config extends AbstractHelper
{
    /** @var WriterInterface */
    private $configWriter;

    public function __construct(
        Context $context,
        WriterInterface $configWriter
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
    }


    public function getConfig(
        string $config,
        string $group = 'vindi_vp_bankslip',
        string $section = 'payment',
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig->getValue(
            $section . '/' . $group . '/' . $config,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function saveConfig(
        string $value,
        string $config,
        string $group = 'general',
        string $section = 'vindi_vp'
    ): void {
        $this->configWriter->save(
            $section . '/' . $group . '/' . $config,
            $value
        );
    }

    public function getGeneralConfig(string $config, $scopeCode = null): string
    {
        return $this->getConfig($config, 'general', 'vindi_vp', $scopeCode);
    }

    public function getEndpointConfig(string $config, $scopeCode = null): string
    {
        return $this->getConfig($config, 'endpoints', 'vindi_vp', $scopeCode);
    }
}
