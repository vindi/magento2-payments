<?php

namespace Vindi\VP\Controller\Auth;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Vindi\VP\Helper\Config as ConfigHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResponseInterface;

class Index extends Action
{
    /** @var ConfigHelper */
    protected $configHelper;

    /** @var JsonFactory */
    protected $jsonFactory;

    /** @var StoreManagerInterface */
    protected $storeManager;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        JsonFactory $jsonFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->configHelper = $configHelper;
        $this->jsonFactory = $jsonFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute the controller logic
     *
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $code = $this->getRequest()->getParam('code');

        if (!$code) {
            return $result->setData(['success' => false, 'message' => 'Authorization code is missing.']);
        }

        try {
            $storeId = $this->storeManager->getStore()->getId();

            $this->configHelper->saveVindiCode($code, $storeId);
            return $result->setData(['success' => true, 'message' => 'Authorization code saved successfully.']);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => 'Failed to save authorization code: ' . $e->getMessage()]);
        }
    }
}
