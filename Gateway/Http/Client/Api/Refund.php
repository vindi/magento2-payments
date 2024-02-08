<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 *
 */

namespace Vindi\VP\Gateway\Http\Client\Api;

use Vindi\VP\Gateway\Http\Client;
use Laminas\Http\Request;

class Refund extends Client
{
    /**
     * @param $data
     * @param $orderId
     * @return array
     */
    public function execute($data, $storeId = null): array
    {
        return $this->refund($data, $storeId);
    }

    public function cancel($data, $storeId): array
    {
        return $this->refund($data, $storeId);
    }

    public function refund($data, $storeId): array
    {
        $path = $this->getEndpointPath('payments/refund');
        $method = Request::METHOD_PATCH;
        return $this->makeRequest($path, $method, 'payments', $data, $storeId);
    }
}
