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

namespace Vindi\VP\Model;

use Vindi\VP\Api\Data\RequestExtensionInterface;
use Vindi\VP\Api\Data\RequestInterface;
use Magento\Framework\Model\AbstractModel;

class Request extends AbstractModel implements RequestInterface
{
    /**
     * CMS page cache tag.
     */
    const CACHE_TAG = 'vindi_vp_request';

    /**
     * @var string
     */
    protected $_cacheTag = 'vindi_vp_request';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'vindi_vp_request';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Request::class);
    }

    /**
     * @ingeritdoc
     */
    public function getStatusCode()
    {
        return $this->getData(self::STATUS_CODE);
    }

    /**
     * @ingeritdoc
     */
    public function setStatusCode($statusCode)
    {
        $this->setData(self::STATUS_CODE, $statusCode);
    }

    /**
     * @ingeritdoc
     */
    public function getMethod()
    {
        return $this->getData(self::METHOD);
    }

    /**
     * @ingeritdoc
     */
    public function setMethod($method)
    {
        $this->setData(self::METHOD, $method);
    }

    /**
     * @ingeritdoc
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * @ingeritdoc
     */
    public function setIncrementId($incrementId)
    {
        $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * @ingeritdoc
     */
    public function getRequest()
    {
        return $this->getData(self::REQUEST);
    }

    /**
     * @ingeritdoc
     */
    public function setRequest($request)
    {
        $this->setData(self::REQUEST, $request);
    }

    /**
     * @ingeritdoc
     */
    public function getResponse()
    {
        return $this->getData(self::RESPONSE);
    }

    /**
     * @ingeritdoc
     */
    public function setResponse($response)
    {
        $this->setData(self::RESPONSE, $response);
    }

    /**
     * @ingeritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @ingeritdoc
     */
    public function setCreatedAt($createdAt)
    {
        $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @ingeritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @ingeritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(RequestExtensionInterface $extensionAttributes)
    {
        $this->_setExtensionAttributes($extensionAttributes);
    }
}
