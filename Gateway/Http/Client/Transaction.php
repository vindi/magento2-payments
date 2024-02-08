<?php
/**
 *
 *
 *
 *
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 *
 *
 */

namespace Vindi\VP\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Vindi\VP\Helper\Data;

class Transaction implements ClientInterface
{
    public const LOG_NAME = 'vindi-transaction';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var string
     */
    protected $methodCode;

    /**
     * @param Data $helper
     * @param Api $api
     */
    public function __construct(
        Api $api,
        string $methodCode = 'vindi_payment'
    ) {
        $this->methodCode = $methodCode;
        $this->api = $api;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $requestBody = $transferObject->getBody();
        $config = $transferObject->getClientConfig();

        $this->api->logRequest($requestBody, self::LOG_NAME);
        $transaction = $this->api->create()->execute($requestBody, $config['store_id']);
        $this->api->logResponse($transaction, self::LOG_NAME);

        $statusCode = $transaction['status'] ?? null;
        $status = $transaction['response']['status'] ?? $statusCode;

        $this->api->saveRequest($requestBody, $transaction['response'], $statusCode, $this->methodCode);

        return ['status' => $status, 'status_code' => $statusCode, 'transaction' => $transaction['response']];
    }
}
