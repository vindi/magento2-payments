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

namespace Vindi\VP\Logger;

class Logger extends \Monolog\Logger
{

    /**
     * Set logger name
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}
