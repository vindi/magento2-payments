<?php

/**
 *
 * @category    Vindi
 * @package     Vindi_VP
 */

namespace Vindi\VP\Controller\Callback;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Vindi\VP\Controller\Callback;
use Vindi\VP\Gateway\Http\Client\Api;
use Vindi\VP\Helper\Order as HelperOrder;
use Magento\Sales\Model\Order as SalesOrder;

class Payments extends Callback
{
    /**
     * @var string
     */
    protected $eventName = 'pix';

    /**
     * Validate CSRF request.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $hash = $request->getParam('hash');
        $storeHash = sha1($this->helperData->getToken());
        return ($hash == $storeHash);
    }

    /**
     * Execute callback and register it in the callback table.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helperData->log(__('Webhook %1', __CLASS__), self::LOG_NAME);
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $statusCode = 200;

        try {
            $content = $this->getContent($this->getRequest()) ?? '';
            $params = $this->getRequest()->getParams();
            $this->logParams($content, $params);

            if (isset($params['transaction'])) {
                /** @var \Vindi\VP\Model\Callback $callBack */
                $callBack = $this->callbackFactory->create();
                $callBack->setStatus($params['transaction']['status_name'] ?? '');
                $callBack->setMethod('vindi-payments');
                $callBack->setIncrementId($params['transaction']['order_number'] ?? ($params['transaction']['free'] ?? ''));
                $callBack->setPayload($this->json->serialize($params));
                $callBack->setQueueStatus('pending');
                $this->callbackResourceModel->save($callBack);
            }
        } catch (\Exception $e) {
            $statusCode = 500;
            $this->helperData->log($e->getMessage());
        }

        $result->setHttpResponseCode($statusCode);
        return $result;
    }
}
