<?php

declare(strict_types=1);

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
 *
 */

namespace Vindi\VP\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class AccessToken extends AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Construct.
     *
     * @param Context $context
     * @param string|null $resourcePrefix
     */
    public function __construct(
        Context $context,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
    }

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('vindi_vp_access_tokens', 'entity_id');
    }

    public function deleteExpired(): void
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            ['expiration < ?' => time() - 365 * 24 * 60 * 60]
        );
    }

    /**
     * Get valid access token.
     *
     * @return string
     * @throws \Exception
     */
    public function getValidAccessToken($storeId = null): string
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['access_token'])
            ->where('access_token_expiration > ?', time() + 600)
            ->where('store_id = ?', $storeId ?? '0')
            ->order('entity_id DESC')
            ->limit(1);
        $token = $this->getConnection()->fetchOne($select);
        if (empty($token)) {
            throw new \Exception('No valid access token found');
        }
        return $token;
    }

    /**
     * Get valid access token.
     *
     * @return array
     */
    public function getLastRefreshToken($storeId = null): array
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['access_token', 'refresh_token'])
            ->where('refresh_token_expiration > ?', time() + 600)
            ->where('store_id = ?', $storeId ?? '0')
            ->order('entity_id DESC')
            ->limit(1);
        $lastToken = $this->getConnection()->fetchAssoc($select);

        if (!empty($lastToken)) {
            return $lastToken;
        }

        return [];
    }
}
