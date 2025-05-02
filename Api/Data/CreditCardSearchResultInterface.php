<?php

namespace Vindi\VP\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface CreditCardSearchResultInterface
 * @package Vindi\VP\Api\Data
 * Search result interface for credit cards.
 */
interface CreditCardSearchResultInterface extends SearchResultsInterface
{
    /**
     * Get list of credit cards.
     *
     * @return \Vindi\VP\Api\Data\CreditCardInterface[]
     */
    public function getItems();

    /**
     * Set list of credit cards.
     *
     * @param \Vindi\VP\Api\Data\CreditCardInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
