<?php
/**
 *
 *
 *
 *
 *
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

namespace Vindi\VP\Api\Data;

interface RequestInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    public const ENTITY_ID = 'entity_id';
    public const INCREMENT_ID = 'increment_id';
    public const REQUEST = 'request';
    public const RESPONSE = 'response';
    public const METHOD = 'method';
    public const STATUS_CODE = 'status_code';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set EntityId.
     * @param $entityId
     */
    public function setEntityId($entityId);

    /**
     * Get IncrementID.
     *
     * @return string
     */
    public function getIncrementId();

    /**
     * Set IncrementId.
     * @param $orderId
     */
    public function setIncrementId($incrementId);

    /**
     * Get Status.
     *
     * @return string
     */
    public function getStatusCode();

    /**
     * Set Status.
     * @param $statusCode
     */
    public function setStatusCode($statusCode);

    /**
     * Get Method.
     *
     * @return string
     */
    public function getMethod();

    /**
     * Set Method.
     * @param $method
     */
    public function setMethod($method);

    /**
     * Get Request.
     *
     * @return string
     */
    public function getRequest();

    /**
     * Set Request.
     * @param $request
     */
    public function setRequest($request);

    /**
     * Get Response.
     *
     * @return string
     */
    public function getResponse();

    /**
     * Set Response.
     * @param $response
     */
    public function setResponse($response);

    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set CreatedAt.
     * @param $createdAt
     */
    public function setCreatedAt($createdAt);

    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Set Updated At.
     * @param $updatedAt
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return \Vindi\VP\Api\Data\RequestExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * @param \Vindi\VP\Api\Data\RequestExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(RequestExtensionInterface $extensionAttributes);
}
