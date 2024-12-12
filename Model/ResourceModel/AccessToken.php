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
 */

namespace Vindi\VP\Model\ResourceModel;

use DateTimeImmutable;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class AccessToken extends AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Buffer time in seconds to anticipate expiration
     */
    private const BUFFER_TIME_SECONDS = 600;

    /**
     * Expiration time in seconds for access tokens
     */
    private const TOKEN_EXPIRATION_SECONDS = 365 * 24 * 60 * 60;

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
        $currentTimestamp = (new DateTimeImmutable())->getTimestamp();
        $this->getConnection()->delete(
            $this->getMainTable(),
            ['access_token_expiration < ?' => $currentTimestamp - self::TOKEN_EXPIRATION_SECONDS]
        );
    }

    /**
     * Retrieve a token based on expiration and type.
     *
     * @param string $field
     * @param int|null $storeId
     * @return string|array
     * @throws \Exception
     */
    private function getTokenByType(string $field, ?int $storeId)
    {
        $currentTimestamp = (new DateTimeImmutable())->getTimestamp();
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), $field === 'refresh' ? ['access_token', 'refresh_token'] : ['access_token'])
            ->where($field . '_expiration > ?', $currentTimestamp + self::BUFFER_TIME_SECONDS)
            ->where('store_id = ?', $storeId ?? '0')
            ->order('entity_id DESC')
            ->limit(1);

        $token = $field === 'refresh' ? $this->getConnection()->fetchAssoc($select) : $this->getConnection()->fetchOne($select);

        if (empty($token)) {
            throw new \Exception('No valid ' . $field . ' token found');
        }

        return $token;
    }

    /**
     * Get valid access token.
     *
     * @param int|null $storeId
     * @return string
     * @throws \Exception
     */
    public function getValidAccessToken($storeId = null): string
    {
        return $this->getTokenByType('access', $storeId);
    }

    /**
     * Get the last refresh token.
     *
     * @param int|null $storeId
     * @return array
     * @throws \Exception
     */
    public function getLastRefreshToken($storeId = null): array
    {
        return $this->getTokenByType('refresh', $storeId);
    }
}
