<?php

declare(strict_types=1);

namespace Vindi\VP\Model;

use Magento\Framework\Model\AbstractModel;
use Vindi\VP\Api\Data\CreditCardInterface;
use Vindi\VP\Model\ResourceModel\CreditCard as CreditCardResource;

/**
 * Class CreditCard
 * @package Vindi\VP\Model
 *
 * Model for Vindi Credit Cards
 */
class CreditCard extends AbstractModel implements CreditCardInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(CreditCardResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->getData(CreditCardInterface::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(CreditCardInterface::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return $this->getData(CreditCardInterface::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(CreditCardInterface::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getCardToken()
    {
        return $this->getData(CreditCardInterface::CARD_TOKEN);
    }

    /**
     * @inheritdoc
     */
    public function setCardToken($cardToken)
    {
        return $this->setData(CreditCardInterface::CARD_TOKEN, $cardToken);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerEmail()
    {
        return $this->getData(CreditCardInterface::CUSTOMER_EMAIL);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerEmail($customerEmail)
    {
        return $this->setData(CreditCardInterface::CUSTOMER_EMAIL, $customerEmail);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->getData(CreditCardInterface::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(CreditCardInterface::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->getData(CreditCardInterface::TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setType($type)
    {
        return $this->setData(CreditCardInterface::TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getCcType()
    {
        return $this->getData(CreditCardInterface::CC_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setCcType($ccType)
    {
        return $this->setData(CreditCardInterface::CC_TYPE, $ccType);
    }

    /**
     * @inheritdoc
     */
    public function getCcLast4()
    {
        return $this->getData(CreditCardInterface::CC_LAST_4);
    }

    /**
     * @inheritdoc
     */
    public function setCcLast4($ccLast4)
    {
        return $this->setData(CreditCardInterface::CC_LAST_4, $ccLast4);
    }

    /**
     * @inheritdoc
     */
    public function getCcName()
    {
        return $this->getData(CreditCardInterface::CC_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setCcName($ccName)
    {
        return $this->setData(CreditCardInterface::CC_NAME, $ccName);
    }

    /**
     * @inheritdoc
     */
    public function getCcExpDate()
    {
        return $this->getData(CreditCardInterface::CC_EXP_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setCcExpDate($ccExpDate)
    {
        return $this->setData(CreditCardInterface::CC_EXP_DATE, $ccExpDate);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(CreditCardInterface::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(CreditCardInterface::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(CreditCardInterface::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(CreditCardInterface::UPDATED_AT, $updatedAt);
    }
}
