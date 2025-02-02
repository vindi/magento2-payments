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

namespace Vindi\VP\Observer;

use Vindi\VP\Helper\Data;
use Vindi\VP\Helper\Installments;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Model\Quote\Payment;

class CreditCardAssignObserver extends AbstractDataAssignObserver
{
    /** @var Data */
    protected $helper;

    /** @var Session  */
    protected $checkoutSession;

    /** @var Installments  */
    protected $installmentsHelper;

    /** @var Json  */
    protected $json;

    public function __construct(
        Session $checkoutSession,
        Data $helper,
        Installments $installmentsHelper,
        Json $json
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->installmentsHelper = $installmentsHelper;
        $this->json = $json;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        /** @var array $additionalData */
        $additionalData = $data->getAdditionalData();

        if (!empty($additionalData)) {
            if (isset($additionalData['cc_number'])) {
                $installments = $additionalData['installments'] ?? 1;
                $ccOwner = $additionalData['cc_owner'] ?? null;
                $ccType = $additionalData['cc_type'] ?? null;
                $ccLast4 = substr((string) $additionalData['cc_number'], -4);
                $ccBin = substr((string) $additionalData['cc_number'], 0, 6);
                $ccExpMonth = $additionalData['cc_exp_month'] ?? null;
                $ccExpYear = $additionalData['cc_exp_year'] ?? null;
                $paymentProfile = $additionalData["payment_profile"] ?? null;

                $this->updateInterest((int) $installments);

                /** @var Payment $paymentInfo */
                $paymentInfo = $this->readPaymentModelArgument($observer);

                $paymentInfo->addData([
                    'cc_type' => $ccType,
                    'cc_owner' => $ccOwner,
                    'cc_number' => $additionalData['cc_number'],
                    'cc_last_4' => $ccLast4,
                    'cc_cid' => $additionalData['cc_cid'],
                    'cc_exp_month' => $ccExpMonth,
                    'cc_exp_year' => $ccExpYear
                ]);

                $paymentInfo->setAdditionalInformation('installments', $installments);
                $paymentInfo->setAdditionalInformation('cc_installments', $installments);
                $paymentInfo->setAdditionalInformation('cc_bin', $ccBin);
                $paymentInfo->setAdditionalInformation('payment_method', $this->helper->getMethodName($ccType));
                $paymentInfo->setAdditionalInformation('payment_profile', $paymentProfile);
            }
        }
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function updateInterest(int $installments): void
    {
        $this->checkoutSession->setData('vindi_installments', $installments);
        $quote = $this->checkoutSession->getQuote();
        $quote->setTotalsCollectedFlag(false)->collectTotals();
    }
}
