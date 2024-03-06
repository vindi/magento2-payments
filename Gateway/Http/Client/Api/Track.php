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

class Track extends Client
{
    public const LOG_NAME = 'vindi-track';

    /**
     * @param $data
     * @param $orderId
     * @return array
     */
    public function execute($data, $storeId = null): array
    {
        $path = $this->getEndpointPath('payments/track');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'payments', $data, $storeId);
    }
}
