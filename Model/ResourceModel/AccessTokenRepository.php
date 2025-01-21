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

use Vindi\VP\Model\AccessTokenFactory;
use Vindi\VP\Api\AccessTokenRepositoryInterface;
use Vindi\VP\Model\ResourceModel\AccessToken as ResourceAccessToken;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var AccessToken
     */
    protected $resource;

    /**
     * @var AccessTokenFactory
     */
    protected AccessTokenFactory $accessTokenFactory;

    /**
     * @param AccessToken $resource
     * @param AccessTokenFactory $accessTokenFactory
     */
    public function __construct(
        ResourceAccessToken $resource,
        AccessTokenFactory $accessTokenFactory,
    ) {
        $this->resource = $resource;
        $this->accessTokenFactory = $accessTokenFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        /** @var \Vindi\VP\Model\AccessToken $accessToken */
        $accessToken = $this->accessTokenFactory->create();
        $this->resource->load($accessToken, $id);
        if (!$accessToken->getId()) {
            throw new NoSuchEntityException(__('Item with id "%1" does not exist.', $id));
        }
        return $accessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function save(\Vindi\VP\Api\Data\AccessTokenInterface $accessToken)
    {
        try {
            $accessToken = $this->resource->save($accessToken);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the accessToken info: %1',
                $exception->getMessage()
            ));
        }
        return $accessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(\Vindi\VP\Api\Data\AccessTokenInterface $accessToken)
    {
        try {
            $this->resource->delete($accessToken);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the entry: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    public function getValidAccessToken($storeId = null): string
    {
        return $this->resource->getValidAccessToken($storeId);
    }

    /**
     * @throws \Exception
     */
    public function getLastRefreshToken($storeId = null): array
    {
        return $this->resource->getLastRefreshToken($storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExpired()
    {
        try {
            $this->resource->deleteExpired();
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete expired tokens', $exception->getMessage())
            );
        }
        return true;
    }

    public function saveNewAccessToken(
        string $accessToken,
        string $refreshToken,
        string $expiration,
        string $refreshExpiration,
        $storeId = null
    ): void {
        $accessTokenModel = $this->accessTokenFactory->create();
        $storeId = $storeId !== null ? (int) $storeId : 0;
        $accessTokenModel->setStoreId($storeId);
        $accessTokenModel->setAccessToken($accessToken);
        $accessTokenModel->setRefreshToken($refreshToken);
        $accessTokenModel->setAccessTokenExpiration($expiration);
        $accessTokenModel->setRefreshTokenExpiration($refreshExpiration);
        $this->resource->save($accessTokenModel);
    }
}
