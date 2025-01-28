<?php

namespace Vindi\VP\Model\ResourceModel\CreditCard;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Vindi\VP\Model\CreditCard as CreditCardModel;
use Vindi\VP\Model\ResourceModel\CreditCard as CreditCardResource;

/**
 * Class Collection
 * Collection for Vindi Credit Cards
 */
class Collection extends AbstractCollection
{
    /**
     * Define model and resource model
     */
    protected function _construct()
    {
        $this->_init(CreditCardModel::class, CreditCardResource::class);
    }
}
