<?php

namespace Vindi\VP\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class CreditCard
 * Resource Model for Vindi Credit Cards
 */
class CreditCard extends AbstractDb
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('vindi_vp_credit_cards', 'entity_id');
    }
}
