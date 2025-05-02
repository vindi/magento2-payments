<?php

namespace Vindi\VP\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vindi\VP\Api\Data\CreditCardInterface;

/**
 * Interface CreditCardRepositoryInterface
 * @package Vindi\VP\Api
 */
interface CreditCardRepositoryInterface
{
    /**
     * Save a credit card.
     *
     * @param CreditCardInterface $creditCard
     * @return CreditCardInterface
     */
    public function save(CreditCardInterface $creditCard);

    /**
     * Get credit card by ID.
     *
     * @param int $id
     * @return CreditCardInterface
     */
    public function getById($id);

    /**
     * Delete credit card.
     *
     * @param CreditCardInterface $creditCard
     * @return bool
     */
    public function delete(CreditCardInterface $creditCard);

    /**
     * Delete credit card by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteById($id);

    /**
     * Get list of credit cards.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
