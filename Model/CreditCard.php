<?php

namespace Vindi\VP\Model;

use Magento\Framework\Model\AbstractModel;
use Vindi\VP\Model\ResourceModel\CreditCard as CreditCardResource;

/**
 * Class CreditCard
 * Model for Vindi Credit Cards
 */
class CreditCard extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(CreditCardResource::class);
    }
}
