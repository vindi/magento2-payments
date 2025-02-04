<?php

namespace Vindi\VP\Controller\Auth;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Vindi\VP\Helper\Config as ConfigHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Vindi\VP\Logger\Logger;
use Magento\Framework\App\ResponseInterface;
use Vindi\VP\Helper\Data as HelperData;
use Magento\Framework\App\Cache\Manager as CacheManager;

class Index extends Action
{
    /** @var ConfigHelper */
    protected $configHelper;

    /** @var JsonFactory */
    protected $jsonFactory;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var Logger */
    private $logger;

    /** @var bool */
    protected $isDebugEnabled;

    /** @var HelperData */
    protected $helperData;

    /** @var CacheManager */
    protected $cacheManager;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param JsonFactory $jsonFactory
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param HelperData $helperData
     * @param CacheManager $cacheManager
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        JsonFactory $jsonFactory,
        StoreManagerInterface $storeManager,
        Logger $logger,
        HelperData $helperData,
        CacheManager $cacheManager
    ) {
        parent::__construct($context);
        $this->configHelper = $configHelper;
        $this->jsonFactory = $jsonFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->isDebugEnabled = $this->configHelper->isDebugEnabled();
        $this->helperData = $helperData;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Execute the controller logic
     *
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->logDebug('Auth/Index: Execution started.');

        $result = $this->jsonFactory->create();
        $code = $this->getRequest()->getParam('code');

        if (!$code) {
            $this->logDebug('Auth/Index: Authorization code is missing.', [], 'error');
            return $result->setData(['success' => false, 'message' => 'Authorization code is missing.']);
        }

        try {
            $this->logDebug('Auth/Index: Authorization code received.');

            $storeId = $this->storeManager->getStore()->getId();

            $this->logDebug('Auth/Index: Store ID retrieved successfully.');

            $this->configHelper->saveVindiCode($code, $storeId);

            $this->logDebug('Auth/Index: Authorization code saved successfully.');

            $accessToken = $this->helperData->generateNewAccessToken($storeId);

            if ($accessToken) {
                $this->logDebug('Auth/Index: Access token generated and saved successfully.');

                $this->cacheManager->clean(['config']);
                $this->logDebug('Auth/Index: Cache de configuração do banco de dados limpo com sucesso.');

                return $result->setData(['success' => true, 'message' => 'Authorization and access token saved successfully.']);
            } else {
                throw new \Exception('Failed to generate access token.');
            }
        } catch (\Exception $e) {
            $this->logDebug('Auth/Index: Failed to save authorization code or access token.', ['exception' => $e->getMessage()], 'error');

            return $result->setData([
                'success' => false,
                'message' => 'Failed to save authorization code or access token: ' . $e->getMessage()
            ]);
        } finally {
            $this->logDebug('Auth/Index: Execution completed.');
        }
    }

    /**
     * Logs debug messages if debug is enabled.
     *
     * @param string $message
     * @param array|string $data
     * @param string $type
     */
    private function logDebug(string $message, $data = [], string $type = 'info'): void
    {
        if ($this->isDebugEnabled) {
            if ($type === 'error') {
                $this->logger->error($message, is_array($data) ? $data : ['data' => $data]);
            } else {
                $this->logger->info($message, is_array($data) ? $data : ['data' => $data]);
            }
        }
    }
}
