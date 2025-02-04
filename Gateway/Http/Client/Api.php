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

namespace Vindi\VP\Gateway\Http\Client;

use Vindi\VP\Gateway\Http\Client\Api\Create;
use Vindi\VP\Gateway\Http\Client\Api\Query;
use Vindi\VP\Gateway\Http\Client\Api\Refund;
use Vindi\VP\Gateway\Http\Client\Api\Installments;
use Vindi\VP\Gateway\Http\Client\Api\Track;
use Vindi\VP\Gateway\Http\Client\Api\Token;
use Vindi\VP\Helper\Logger;

class Api
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Create
     */
    private $create;

    /**
     * @var Refund
     */
    private $refund;

    /**
     * @var Installments
     */
    private $installments;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Track
     */
    private $track;

    /**
     * @var Token
     */
    private $token;

    public function __construct(
        Logger $logger,
        Create $create,
        Refund $refund,
        Installments $installments,
        Query $query,
        Track $track,
        Token $token
    ) {
        $this->logger = $logger;
        $this->create = $create;
        $this->refund = $refund;
        $this->installments = $installments;
        $this->query = $query;
        $this->track = $track;
        $this->token = $token;
    }

    public function create(): Create
    {
        return $this->create;
    }

    public function query(): Query
    {
        return $this->query;
    }

    public function refund(): Refund
    {
        return $this->refund;
    }

    public function track(): Track
    {
        return $this->track;
    }

    public function installments(): Installments
    {
        return $this->installments;
    }

    public function token(): Token
    {
        return $this->token;
    }

    /**
     * @param $request
     * @param string $name
     */
    public function logRequest($request, $name = 'vindi-vp'): void
    {
        $this->logger->execute('Request', $name);
        $this->logger->execute($request, $name);
    }

    /**
     * @param $response
     * @param string $name
     */
    public function logResponse($response, $name = 'vindi-vp'): void
    {
        $this->logger->execute('RESPONSE', $name);
        $this->logger->execute($response, $name);
    }

    /**
     * @param $request
     * @param $response
     * @param $statusCode
     * @return void
     */
    public function saveRequest(
        $request,
        $response,
        $statusCode,
        $method = \Vindi\VP\Model\Ui\CreditCard\ConfigProvider::CODE
    ): void {
        $this->logger->saveRequest($request, $response, $statusCode, $method);
    }
}
