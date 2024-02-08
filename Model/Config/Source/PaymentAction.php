<?php
/**
 * Vindi
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 * @copyright   Copyright (c) Vindi
 *
 */

namespace Vindi\VP\Model\Config\Source;

use Magento\Payment\Model\MethodInterface;

/**
 * Class PaymentAction
 */
class PaymentAction implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => MethodInterface::ACTION_AUTHORIZE,
                'label' => __('Authorize')
            ],
            [
                'value' => MethodInterface::ACTION_ORDER,
                'label' => __('Authorize and Capture')
            ]
        ];
    }
}
