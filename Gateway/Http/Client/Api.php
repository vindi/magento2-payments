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
use Vindi\VP\Helper\Data;

class Api
{
    /**
     * @var Data
     */
    private $helper;

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

    public function __construct(
        Data $helper,
        Create $create,
        Refund $refund,
        Installments $installments,
        Query $query,
        Track $track
    ) {
        $this->helper = $helper;
        $this->create = $create;
        $this->refund = $refund;
        $this->installments = $installments;
        $this->query = $query;
        $this->track = $track;
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

    /**
     * @param $request
     * @param string $name
     */
    public function logRequest($request, $name = 'vindi-vp'): void
    {
        $this->helper->log('Request', $name);
        $this->helper->log($request, $name);
    }

    /**
     * @param $response
     * @param string $name
     */
    public function logResponse($response, $name = 'vindi-vp'): void
    {
        $this->helper->log('RESPONSE', $name);
        $this->helper->log($response, $name);
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
        $this->helper->saveRequest($request, $response, $statusCode, $method);
    }
}
