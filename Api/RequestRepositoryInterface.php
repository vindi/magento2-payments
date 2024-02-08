<?php

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
 */

declare(strict_types=1);

namespace Vindi\VP\Api;

interface RequestRepositoryInterface
{
    /**
     * Save Queue
     * @param \Vindi\VP\Api\Data\RequestInterface $callback
     * @return \Vindi\VP\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        Data\RequestInterface $callback
    );

    /**
     * Retrieve RequestInterface
     * @param string $id
     * @return \Vindi\VP\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);

    /**
     * Retrieve Queue matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vindi\VP\Api\Data\RequestSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
