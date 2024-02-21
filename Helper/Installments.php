<?php

/**
 *
 *
 *
 *
 *
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

namespace Vindi\VP\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Vindi\VP\Gateway\Http\Client\Api;

/**
 * Installments data helper, prepared for Vindi Transparent
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Installments extends AbstractHelper
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        Api $api,
        Data $helper
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->helper = $helper;
        $this->api = $api;
        parent::__construct($context);
    }

    public function getAllInstallments(float $total = 0, string $ccType = '', int $storeId = 0): array
    {
        $installments = [];

        try {
            $token = $this->helper->getToken($storeId);

            $responseData = $this->api->installments()->execute([
                'token_account' => $token,
                'price' => (string) $total,
                'type_response' => 'J'
            ], $storeId);

            if ($responseData['status'] == 200) {
                if (isset($responseData['response']['data_response'])) {
                    $installments = $this->handleResponse(
                        $responseData['response']['data_response']['payment_methods'],
                        $ccType
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
        return !empty($installments) ? $installments : $this->getDefaultInstallments($total);
    }

    public function getDefaultInstallments(float $total): array
    {
        return [
            [
                'installments' => 1,
                'interest_rate' => 0,
                'installment_price' => $total,
                'total' => $total,
                'formatted_installments_price' => $this->priceCurrency->format($total, false),
                'formatted_total' => $this->priceCurrency->format($total, false),
                'text' => $this->getInterestText(1, $total, 0, $total)
            ]
        ];
    }

    public function getInterestText(int $installments, float $value, float $interestRate, float $grandTotal): string
    {
        if ($interestRate > 0) {
            $interestText = __('with interest');
        } elseif ($interestRate < 0) {
            $interestText = __('with discount');
        } else {
            $interestText = __('without interest');
        }

        return __(
            '%1x of %2 (%3). Total: %4',
            $installments,
            $this->priceCurrency->format($value, false),
            $interestText,
            $this->priceCurrency->format($grandTotal, false)
        );
    }

    protected function handleResponse(array $paymentMethods, string $ccType): array
    {
        $installments = [];

        foreach ($paymentMethods as $paymentMethod) {
            if ($this->helper->getMethodName($ccType) == $paymentMethod['payment_method_name']) {
                foreach ($paymentMethod['splittings'] as $installment) {
                    if (!$this->validate($installment)) {
                        continue;
                    }

                    $price = (float) $installment['value_split'];
                    $total = (float) $installment['value_transaction'];
                    $installmentNumber = (int) $installment['split'];
                    $interestRate = (float) $installment['split_rate'];
                    $installments[] = [
                        'installments' => $installmentNumber,
                        'interest_rate' => $interestRate,
                        'installment_price' => $price,
                        'total' => $total,
                        'formatted_installments_price' => $this->priceCurrency->format($price, false),
                        'formatted_total' => $this->priceCurrency->format($total, false),
                        'text' => $this->getInterestText(
                            $installmentNumber,
                            $price,
                            $interestRate,
                            $total
                        )
                    ];
                }
                break;
            }
        }

        return $installments;
    }

    protected function validate(array $installment): bool
    {
        $maxInstallments = (int) $this->helper->getConfig('max_installments', 'vindi_vp_cc');
        if ($maxInstallments > 0 && $installment['split'] > $maxInstallments) {
            return false;
        }

        $minInstallmentsAmount = (float) $this->helper->getConfig('min_installments_amount', 'vindi_vp_cc');
        if ($installment['value_split'] < $minInstallmentsAmount) {
            return false;
        }

        return true;
    }

    protected function logError(string $message): void
    {
        $this->_logger->error($message);
    }
}
