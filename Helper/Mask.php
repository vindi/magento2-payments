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

namespace Vindi\VP\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Mask extends AbstractHelper
{
    /**
     * @param string $message
     * @return string
     */
    public function execute(string $message): string
    {
        $message = preg_replace('/"token_account":\s?"([^"]+)"/', '"token_account":"*********"', $message);
        $message = preg_replace('/"reseller_token":\s?"([^"]+)"/', '"reseller_token":"*********"', $message);
        $message = preg_replace('/"hash":\s?"([^"]+)"/', '"hash":"*********"', $message);
        $message = preg_replace('/"card_cvv":\s?"([^"]+)"/', '"card_cvv":"***"', $message);
        $message = preg_replace('/"card_expdate_month":\s?"([^"]+)"/', '"card_expdate_month":"**"', $message);
        $message = preg_replace('/"card_expdate_year":\s?"([^"]+)"/', '"card_expdate_year":"****"', $message);
        $message = preg_replace('/"notification_url":\s?\["([^"]+)"\]/', '"notification_url":["*********"]', $message);
        return preg_replace('/"card_number":\s?"(\d{6})\d{3,9}(\d{4})"/', '"card_number":"$1******$2"', $message);
    }
}
