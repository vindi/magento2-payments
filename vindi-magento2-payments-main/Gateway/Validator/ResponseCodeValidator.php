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

namespace Vindi\VP\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class ResponseCodeValidator extends AbstractValidator
{
    /**
     * Performs validation of result code
     *
     * @param $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response'];

        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(true, []);
        } else {
            $error = __('There was an error processing your request.');
            return $this->createResult(false, [$error]);
        }
    }

    /**
     * @param $response
     * @return bool
     */
    private function isSuccessfulTransaction($response)
    {
        return (
            isset($response['status_code'])
            && $response['status_code'] >= 200
            && $response['status_code'] < 300
        );
    }
}
