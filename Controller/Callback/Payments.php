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
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $hash = $request->getParam('hash');
        $storeHash = sha1($this->helperData->getToken());
        return ($hash == $storeHash);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helperData->log(__('Webhook %1', __CLASS__), self::LOG_NAME);

        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $statusCode = 500;

        try {
            $content = $this->getContent($this->getRequest()) ?? '';
            $params = $this->getRequest()->getParams();
            $this->logParams($content, $params);

            if (isset($params['transaction'])) {
                $method = 'vindi-payments';
                $transaction = $params['transaction'];
                $orderIncrementId = $transaction['order_number'] ?? $transaction['free'];
                if (isset($transaction['status_id'])) {
                    $vindiStatus = $transaction['status_id'];
                    $order = $this->helperOrder->loadOrder($transaction['order_number']);
                    if ($order->getId()) {
                        $method = $order->getPayment()->getMethod();
                        $amount = $transaction['price_original'] ?? $order->getGrandTotal();
                        $this->helperOrder->updateOrder($order, $vindiStatus, $transaction, $amount, true);
                        $statusCode = 200;
                    }
                }

                /** @var \Vindi\VP\Model\Callback $callBack */
                $callBack = $this->callbackFactory->create();
                $callBack->setStatus($transaction['status_name'] ?? '');
                $callBack->setMethod($method);
                $callBack->setIncrementId($orderIncrementId);
                $callBack->setPayload($this->json->serialize($params));
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
