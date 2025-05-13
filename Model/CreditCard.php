<?php
declare(strict_types=1);

namespace Vindi\VP\Model;

use Magento\Framework\Model\AbstractModel;
use Vindi\VP\Api\Data\CreditCardInterface;
use Vindi\VP\Model\ResourceModel\CreditCard as CreditCardResource;

/**
 * Class CreditCard
 *
 * @package Vindi\VP\Model
 */
class CreditCard extends AbstractModel implements CreditCardInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(CreditCardResource::class);
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
    public function getCardToken()
    {
        return $this->getData(self::CARD_TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setCardToken($cardToken)
    {
        $this->setData(self::CARD_TOKEN, $cardToken);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCustomerEmail()
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerEmail($customerEmail)
    {
        $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
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
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType($type)
    {
        $this->setData(self::TYPE, $type);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCcType()
    {
        return $this->getData(self::CC_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setCcType($ccType)
    {
        $this->setData(self::CC_TYPE, $ccType);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCcLast4()
    {
        return $this->getData(self::CC_LAST_4);
    }

    /**
     * @inheritDoc
     */
    public function setCcLast4($ccLast4)
    {
        $this->setData(self::CC_LAST_4, $ccLast4);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCcName()
    {
        return $this->getData(self::CC_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCcName($ccName)
    {
        $this->setData(self::CC_NAME, $ccName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCcExpDate()
    {
        return $this->getData(self::CC_EXP_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setCcExpDate($ccExpDate)
    {
        $this->setData(self::CC_EXP_DATE, $ccExpDate);
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
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->setData(self::UPDATED_AT, $updatedAt);
        return $this;
    }
}
