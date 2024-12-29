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
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class Config
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Config extends AbstractHelper
{
    private const VINDI_CODE_PATH = 'vindi_vp/authorization/code';

    /** @var WriterInterface */
    private $configWriter;

    /** @var EncryptorInterface */
    private $encryptor;

    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
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

    /**
     * Save the Vindi authorization code (encrypted)
     *
     * @param string $code
     * @param int|null $storeId
     */
    public function saveVindiCode(string $code, ?int $storeId = null): void
    {
        $this->configWriter->save(self::VINDI_CODE_PATH, $code, ScopeInterface::SCOPE_STORES, $storeId);
    }

    /**
     * Retrieve the Vindi authorization code
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getVindiCode(?int $storeId = null): ?string
    {
        $code = $this->scopeConfig->getValue(self::VINDI_CODE_PATH, ScopeInterface::SCOPE_STORES, $storeId);

        if ($code) {
            return $code;
        }

        return null;
    }

    /**
     * Retrieve the debug configuration value
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            'vindi_vp/general/debug',
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
}
