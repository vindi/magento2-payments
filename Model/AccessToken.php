<?php

declare(strict_types=1);

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 */

namespace Vindi\VP\Model;

use Vindi\VP\Api\Data\AccessTokenInterface;
use Magento\Framework\Model\AbstractModel;

class AccessToken extends AbstractModel implements AccessTokenInterface
{
    const CACHE_TAG = 'vindi_vp_access_tokens';

    /**
     * @var string
     */
    protected $_cacheTag = 'vindi_vp_access_tokens';

    /**
     * @var string
     */
    protected $_eventPrefix = 'vindi_vp_access_tokens';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\AccessToken::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        $this->setData(self::ENTITY_ID, $entityId);
        return $this;
    }

    public function getStoreId(): int
    {
        return $this->getData(self::STORE_ID);
    }

    public function setStoreId(int $storeId): void
    {
        $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getAccessToken(): string
    {
        return $this->getData(self::ACCESS_TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->setData(self::ACCESS_TOKEN, $accessToken);
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenExpiration(): string
    {
        return $this->getData(self::ACCESS_TOKEN_EXPIRATION);
    }

    /**
     * @inheritDoc
     */
    public function setAccessTokenExpiration(string $accessTokenExpiration): void
    {
        $this->setData(self::ACCESS_TOKEN_EXPIRATION, $accessTokenExpiration);
    }

    /**
     * @inheritDoc
     */
    public function getRefreshToken(): string
    {
        return $this->getData(self::REFRESH_TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setRefreshToken(string $refreshToken): void
    {
        $this->setData(self::REFRESH_TOKEN, $refreshToken);
    }

    /**
     * @inheritDoc
     */
    public function getRefreshTokenExpiration(): string
    {
        return $this->getData(self::REFRESH_TOKEN_EXPIRATION);
    }

    /**
     * @inheritDoc
     */
    public function setRefreshTokenExpiration(string $refreshTokenexpiration): void
    {
        $this->setData(self::REFRESH_TOKEN_EXPIRATION, $refreshTokenexpiration);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt): void
    {
        $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): void
    {
        $this->setData(self::CREATED_AT, $createdAt);
    }

}
