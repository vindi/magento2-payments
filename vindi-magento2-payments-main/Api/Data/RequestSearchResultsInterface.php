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

declare(strict_types=1);

namespace Vindi\VP\Api\Data;

interface RequestSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get transaction list.
     * @return \Vindi\VP\Api\Data\RequestInterface[]
     */
    public function getItems();

    /**
     * Set entity_id list.
     * @param \Vindi\VP\Api\Data\RequestInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

