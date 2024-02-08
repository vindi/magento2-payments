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
 *
 */

namespace Vindi\VP\Gateway\Http\Client\Api;

use Vindi\VP\Gateway\Http\Client;
use Laminas\Http\Request;

class Installments extends Client
{
    /**
     * @param array $data
     * @param int $storeId
     * @return array
     */
    public function execute(array $data, $storeId = null): array
    {
        $path = $this->getEndpointPath('installments');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'installments', $data, $storeId);
    }
}
