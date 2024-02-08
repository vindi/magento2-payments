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
 * @copyright   Copyright (c) Vindi
 *
 */

namespace Vindi\VP\Model\Config\Source;

/**
 * Class CcType
 * @codeCoverageIgnore
 */
class CcType extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @var string[]
     */
    protected $_allowedTypes = ['MC', 'VI', 'ELO', 'AE', 'HC', 'HI'];

}
