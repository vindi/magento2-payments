<?php

namespace Vindi\VP\Api\Data;

/**
 * Interface CreditCardInterface
 * @package Vindi\VP\Api\Data
 */
interface CreditCardInterface
{
    const ENTITY_ID = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const CARD_TOKEN = 'card_token';
    const CUSTOMER_EMAIL = 'customer_email';
    const STATUS = 'status';
    const TYPE = 'type';
    const CC_TYPE = 'cc_type';
    const CC_LAST_4 = 'cc_last_4';
    const CC_NAME = 'cc_name';
    const CC_EXP_DATE = 'cc_exp_date';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getEntityId();
    public function setEntityId($entityId);

    public function getCustomerId();
    public function setCustomerId($customerId);

    public function getCardToken();
    public function setCardToken($cardToken);

    public function getCustomerEmail();
    public function setCustomerEmail($customerEmail);

    public function getStatus();
    public function setStatus($status);

    public function getType();
    public function setType($type);

    public function getCcType();
    public function setCcType($ccType);

    public function getCcLast4();
    public function setCcLast4($ccLast4);

    public function getCcName();
    public function setCcName($ccName);

    public function getCcExpDate();
    public function setCcExpDate($ccExpDate);

    public function getCreatedAt();
    public function setCreatedAt($createdAt);

    public function getUpdatedAt();
    public function setUpdatedAt($updatedAt);
}
