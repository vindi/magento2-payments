<?php

namespace Vindi\VP\Controller\Auth;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Vindi\VP\Helper\Config as ConfigHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Vindi\VP\Logger\Logger;
use Magento\Framework\App\ResponseInterface;

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

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        JsonFactory $jsonFactory,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->configHelper = $configHelper;
        $this->jsonFactory = $jsonFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->isDebugEnabled = $this->configHelper->isDebugEnabled();
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

            return $result->setData(['success' => true, 'message' => 'Authorization code saved successfully.']);
        } catch (\Exception $e) {
            $this->logDebug('Auth/Index: Failed to save authorization code.', ['exception' => $e->getMessage()], 'error');

            return $result->setData([
                'success' => false,
                'message' => 'Failed to save authorization code: ' . $e->getMessage()
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
