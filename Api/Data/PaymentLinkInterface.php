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

interface PaymentLinkInterface extends ExtensibleDataInterface
{
    public const ENTITY_ID = 'entity_id';
    public const LINK = 'link';
    public const ORDER_ID = 'order_id';
    public const VINDI_PAYMENT_METHOD = 'vindi_payment_method';
    public const CUSTOMER_ID = 'customer_id';
    public const CREATED_AT = 'created_at';

    public const STATUS = 'status';

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entityId
     */
    public function setEntityId(int $entityId);

    /**
     * @return int
     */
    public function getLink();

    /**
     * @param string $link
     */
    public function setLink(string $link);

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * @param int $orderId
     */
    public function setOrderId(int $orderId);

    /**
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     */
    public function setCreatedAt(string $createdAt);

    /**
     * @return string
     */
    public function getVindiPaymentMethod();

    /**
     * @param string $vindiPaymentMethod
     */
    public function setVindiPaymentMethod(string $vindiPaymentMethod);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $customerId
     */
    public function setCustomerId(int $customerId);

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @param string $status
     */
    public function setStatus(string $status);
}

