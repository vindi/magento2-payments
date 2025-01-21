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

namespace Vindi\VP\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface AccessTokenInterface extends ExtensibleDataInterface
{
    public const ENTITY_ID = 'entity_id';

    public const STORE_ID = 'store_id';
    public const ACCESS_TOKEN = 'access_token';
    public const ACCESS_TOKEN_EXPIRATION = 'access_token_expiration';
    public const REFRESH_TOKEN = 'refresh_token';

    public const REFRESH_TOKEN_EXPIRATION = 'refresh_token_expiration';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'created_at';

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entityId
     */
    public function setEntityId(int $entityId);

    public function getStoreId(): int;

    public function setStoreId(int $storeId): void;

    public function getAccessToken(): string;

    public function setAccessToken(string $accessToken): void;

    public function getAccessTokenExpiration(): string;

    public function setAccessTokenExpiration(string $accessTokenExpiration): void;

    public function getRefreshToken(): string;

    public function setRefreshToken(string $refreshToken): void;

    public function getRefreshTokenExpiration(): string;

    public function setRefreshTokenExpiration(string $refreshTokenexpiration): void;

    public function getCreatedAt();

    public function setCreatedAt(string $createdAt): void;

    public function getUpdatedAt(): string;

    public function setUpdatedAt(string $updatedAt): void;
}

