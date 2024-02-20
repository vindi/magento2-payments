<?php

namespace Vindi\VP\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Psr\Log\LoggerInterface;

class ImportSettings implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ScopeConfigInterface
     */
    private $configResource;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ScopeConfigInterface $configResource,
        WriterInterface $configWriter,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configResource = $configResource;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
    }

    public function apply()
    {
        try {
            $this->moduleDataSetup->startSetup();

            $configMapping = [
                'payment/yapay_configuration/environment_configuration_yapay' => 'vindi_vp/general/use_sandbox',
                'payment/yapay_configuration/token_configuration_yapay' => 'vindi_vp/general/token',
                'payment/yapay_configuration/email_configuration_yapay' => 'vindi_vp/general/email'
            ];

            foreach ($configMapping as $oldPath => $newPath) {
                if (!$this->configResource->getValue($newPath)) {
                    $oldValue = $this->configResource->getValue($oldPath);
                    $this->configWriter->save($newPath, $oldValue);
                }
            }

            $this->moduleDataSetup->endSetup();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
