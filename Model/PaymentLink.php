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

use Vindi\VP\Api\Data\PaymentLinkInterface;
use Magento\Framework\Model\AbstractModel;

class PaymentLink extends AbstractModel implements PaymentLinkInterface
{
    const CACHE_TAG = 'vindi_vp_payment_link';

    /**
     * @var string
     */
    protected $_cacheTag = 'vindi_vp_payment_link';

    /**
     * @var string
     */
    protected $_eventPrefix = 'vindi_vp_payment_link';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\PaymentLink::class);
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

    /**
     * @inheritDoc
     */
    public function getLink()
    {
        return $this->getData(self::LINK);
    }

    /**
     * @inheritDoc
     */
    public function setLink(string $link)
    {
        $this->setData(self::LINK, $link);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(int $orderId)
    {
        $this->setData(self::ORDER_ID, $orderId);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        $this->setData(self::CREATED_AT, $createdAt);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVindiPaymentMethod()
    {
        return $this->getData(self::VINDI_PAYMENT_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function setVindiPaymentMethod($vindiPaymentMethod)
    {
        $this->setData(self::VINDI_PAYMENT_METHOD, $vindiPaymentMethod);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status)
    {
        $this->setData(self::STATUS, $status);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSuccessPageAccessed()
    {
        return $this->getData(self::SUCCESS_PAGE_ACCESSED);
    }

    /**
     * @inheritdoc
     */
    public function setSuccessPageAccessed(bool $successPageAccessed)
    {
        $this->setData(self::SUCCESS_PAGE_ACCESSED, $successPageAccessed);
    }

    /**
     * @inheritdoc
     */
    public function getExpiredAt()
    {
        return $this->getData(self::EXPIRED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setExpiredAt($expiredAt)
    {
        $this->setData(self::EXPIRED_AT, $expiredAt);
        return $this;
    }
}
