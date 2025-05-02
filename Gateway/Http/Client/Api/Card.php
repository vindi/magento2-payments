<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 */

namespace Vindi\VP\Gateway\Http\Client\Api;

use Vindi\VP\Gateway\Http\Client;
use Laminas\Http\Request;

class Card extends Client
{
    /**
     * Create a new credit card
     *
     * @param array $data
     * @param int|null $storeId
     * @return array
     */
    public function create(array $data, $storeId = null): array
    {
        $path = $this->getEndpointPath('card/create');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'payments', $data, $storeId, 'xml');
    }

    /**
     * Retrieve stored credit cards
     *
     * @param array $data
     * @param int|null $storeId
     * @return array
     */
    public function retrieve(array $data, $storeId = null): array
    {
        $path = $this->getEndpointPath('card/get');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'payments', $data, $storeId, 'xml');
    }

    /**
     * Deactivate a credit card
     *
     * @param array $data
     * @param int|null $storeId
     * @return array
     */
    public function deactivate(array $data, $storeId = null): array
    {
        $path = $this->getEndpointPath('card/delete');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'payments', $data, $storeId, 'xml');
    }
}
