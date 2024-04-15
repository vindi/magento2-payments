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

namespace Vindi\VP\Observer\Sales;

use Vindi\VP\Helper\Data;
use Vindi\VP\Helper\Order as HelperOrder;
use Vindi\VP\Gateway\Http\Client\Api;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Shipping\Helper\Data as ShippingHelper;

class SendTrackingData implements ObserverInterface
{
    /** @var Data  */
    protected $helper;

    /** @var HelperOrder */
    protected $helperOrder;

    /** @var ShippingHelper */
    protected $shippingHelper;

    /** @var Api */
    protected $api;

    public function __construct(
        Api $api,
        Data $helper,
        HelperOrder $helperOrder,
        ShippingHelper $shippingHelper
    ) {
        $this->api = $api;
        $this->helper = $helper;
        $this->helperOrder = $helperOrder;
        $this->shippingHelper = $shippingHelper;
    }

    public function execute(EventObserver $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            $track = $observer->getEvent()->getTrack();

            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $track->getShipment();
            $payment = $shipment->getOrder()->getPayment();
            if (in_array($payment->getMethod(), $this->helper->getAllowedMethods())) {
                $carrierCode = $track->getCarrierCode();
                $trackingNumber = $track->getTrackNumber();
                $trackingUrl = $this->shippingHelper->getTrackingPopupUrlBySalesModel($track);
                $tokenTransaction = $payment->getAdditionalInformation('token_transaction');
                $postedDate = $this->helper->formatDate($track->getCreatedAt());
                if ($this->helper->isUrl($trackingNumber)) {
                    $trackingUrl = $trackingNumber;
                    $trackingNumber = $shipment->getIncrementId();
                }

                $response = $this->sendTrackingCode([
                    'token_account' => $this->helper->getToken($shipment->getStoreId()),
                    'transaction_token' => $tokenTransaction,
                    'url' => $trackingUrl,
                    'code' => $trackingNumber,
                    'posted_date' => $postedDate
                ]);

                if (isset($response['transaction_id'])) {
                    $payment->setAdditionalInformation('track_transaction_id', $response['track_transaction_id']);
                    $this->helperOrder->savePayment($payment);
                }
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }

    protected function sendTrackingCode(array $data): array
    {
        $this->api->logRequest($data, Api\Track::LOG_NAME);
        $trackData = $this->api->track()->execute($data);
        $this->api->logResponse($trackData, Api\Track::LOG_NAME);
        $statusCode = $trackData['status'] ?? null;
        $this->api->saveRequest($data, $trackData['response'], $statusCode, 'track');

        return $trackData['response'];
    }
}

