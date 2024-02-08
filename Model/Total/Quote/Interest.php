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
 */

namespace Vindi\VP\Model\Total\Quote;

use Vindi\VP\Helper\Installments;
use Vindi\VP\Model\Ui\CreditCard\ConfigProvider;
use Magento\Checkout\Model\Session;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class Interest extends AbstractTotal
{
    /**  @var Session */
    protected $checkoutSession;

    /** @var SessionManagerInterface  */
    protected $session;

    /** @var Installments */
    protected $helperInstallments;

    /** @var CartRepositoryInterface */
    protected $quoteRepository;

    public function __construct(
        Session $checkoutSession,
        SessionManagerInterface $session,
        Installments $helperInstallments,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->setCode('vindi_interest');
        $this->checkoutSession = $checkoutSession;
        $this->session = $session;
        $this->helperInstallments = $helperInstallments;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Return selected installments
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getInstallments()
    {
        $installments = 0;

        /** @var Quote $quote */
        //Prepared for Vindi transparent
        $quoteId = $this->checkoutSession->getQuoteId();
        if ($quoteId) {
            $quote = $this->quoteRepository->get($quoteId);
            if ($quote->getPayment()->getMethod() == ConfigProvider::CODE) {
                $installments = (int) $this->checkoutSession->getData('vindi_installments');
            }
        }

        return $installments;
    }

    /**
     * Calculate interest rate amount
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    protected function getInterestAmount(): float
    {
        $installments = $this->getInstallments();
        if ($installments > 1) {
            $quoteId = $this->checkoutSession->getQuoteId();
            if ($quoteId) {
                /** @var Quote $quote */
                $quote = $this->quoteRepository->get($quoteId);
                $grandTotal = $quote->getGrandTotal() - $quote->getVindiInterestAmount();
                $installmentsPrice = $this->getInstallmentsPrice($quote, $grandTotal, $installments);
                if ($installmentsPrice > $grandTotal) {
                    return $installmentsPrice - $grandTotal;
                }
            }
        }

        return 0;
    }

    protected function getInstallmentsPrice(Quote $quote, float $grandTotal, int $installmentsNumber): float
    {
        try {
            $ccType = (string) $this->session->getVindiCcType();
            $allInstallments = $this->helperInstallments->getAllInstallments($grandTotal, $ccType, $quote->getStoreId());

            $selectedInstallment = array_filter(
                $allInstallments,
                fn($installment) => $installment['installments'] == $installmentsNumber
            );

            $installment = array_shift($selectedInstallment);

            return $installment['total'] ?? $grandTotal;
        } catch (\Exception $e) {
            return $grandTotal;
        }
    }

    /**
     * Collect address discount amount
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        $interest = $this->getInterestAmount();

        $quote->setVindiInterestAmount($interest);
        $quote->setBaseVindiInterestAmount($interest);

        $total->setVindiInterestDescription($this->getCode());
        $total->setVindiInterestAmount($interest);
        $total->setBaseVindiInterestAmount($interest);

        $total->addTotalAmount($this->getCode(), $interest);
        $total->addBaseTotalAmount($this->getCode(), $interest);

        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     *
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $result = null;
        $amount = $total->getVindiInterestAmount();

        if ($amount) {
            $result = [
                'code' => $this->getCode(),
                'title' => __('Interest Rate'),
                'value' => $amount
            ];
        }

        return $result;
    }
}
