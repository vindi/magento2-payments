<?php
/**
 * Biz
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Biz.com license that is
 * available through the world-wide-web at this URL:
 * https://www.bizcommerce.com.br/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 * @copyright   Copyright (c) Biz (https://www.bizcommerce.com.br/)
 * @license     https://www.bizcommerce.com.br/LICENSE.txt
 */

namespace Vindi\VP\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class Capture implements ClientInterface
{
    const LOG_NAME = 'vindi-capture';

    /**
     * @var Transaction
     */
    private $api;

    /**
     * @param Api $api
     */
    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }

    /**
     * Vindi doens't have a capture method, so we use this to have an ONLINE Invoice and then, be able to refund online
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $requestBody = $transferObject->getBody();
        $config = $transferObject->getClientConfig();

        $statusCode = 200;
        $transaction = [];

        return ['status' => $statusCode, 'status_code' => $statusCode, 'transaction' => []];
    }
}
