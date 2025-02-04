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

class Token extends Client
{
    /**
     * @param string $accessToken
     * @param string $refreshToken
     * @param $storeId
     * @return string
     * @throws \Exception
     */
    public function updateAccessToken(string $accessToken, string $refreshToken, $storeId = null): array
    {
        $data = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
        $path = $this->getEndpointPath('payments/auth/refresh');
        $method = Request::METHOD_POST;
        $response = $this->makeRequest($path, $method, 'payments', $data, $storeId, 'xml');

        if (isset($response["response"])) {
            $response = $response["response"];
        }

        if ($response["message_response"]["message"] != 'success') {
            throw new \Exception('Error updating access token');
        }

        return $response["data_response"]["authorization"];
    }

    /**
     * @param string $resellerToken
     * @param string $tokenAccount
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param $storeId
     * @return string
     * @throws \Exception
     */
    public function generateCode(
        string $resellerToken,
        string $tokenAccount,
        string $consumerKey,
        string $consumerSecret,
        $storeId = null
    ): string {
        $data = [
            'reseller_token' => $resellerToken,
            'token_account' => $tokenAccount,
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
            'type_response' => 'J'
        ];
        $path = $this->getEndpointPath('payments/auth/create');
        $method = Request::METHOD_POST;

        $response = $this->makeRequest($path, $method, 'payments', $data, $storeId);

        if (isset($response["response"])) {
            $response = $response["response"];
        }

        if ($response["message_response"]["message"] != 'success') {
            throw new \Exception('Error generating code');
        }

        return $response["data_response"]["authorization"];
    }

    /**
     * @param string $code
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param $storeId
     * @return array
     * @throws \Exception
     */
    public function generateAccessToken(
        string $code,
        string $consumerKey,
        string $consumerSecret,
        $storeId = null
    ): array {
        $data = [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
            'code' => $code
        ];
        $path = $this->getEndpointPath('payments/auth/token');
        $method = Request::METHOD_POST;

        $response = $this->makeRequest($path, $method, 'payments', $data, $storeId, 'xml');

        if (isset($response["response"])) {
            $response = $response["response"];
        }

        if ($response["message_response"]["message"] != 'success') {
            throw new \Exception('Error generating access token');
        }

        return $response["data_response"]["authorization"];
    }
}
