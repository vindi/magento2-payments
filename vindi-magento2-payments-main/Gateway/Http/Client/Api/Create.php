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

namespace Vindi\VP\Gateway\Http\Client\Api;

use Vindi\VP\Gateway\Http\Client;
use Vindi\VP\Gateway\Http\Client\Api;
use Laminas\Http\Request;

class Create extends Client
{
    /**
     * @param $data
     * @return array
     */
    public function execute($data, $storeId = null): array
    {
        $path = $this->getEndpointPath('payments/create');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'payments', $data, $storeId);
    }
}
