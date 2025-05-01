<?php

namespace Vindi\VP\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Vindi\VP\Api\CreditCardRepositoryInterface;
use Vindi\VP\Api\Data\CreditCardInterface;
use Vindi\VP\Model\ResourceModel\CreditCard as CreditCardResource;
use Vindi\VP\Model\ResourceModel\CreditCard\CollectionFactory;

/**
 * Class CreditCardRepository
 * @package Vindi\VP\Model
 */
class CreditCardRepository implements CreditCardRepositoryInterface
{
    /**
     * @var CreditCardResource
     */
    private $creditCardResource;

    /**
     * @var CreditCardFactory
     */
    private $creditCardFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsFactory
     */
    private $searchResultsFactory;

    /**
     * Constructor
     *
     * @param CreditCardResource $creditCardResource Resource model for Credit Card
     * @param CreditCardFactory $creditCardFactory Factory for creating Credit Card instances
     * @param CollectionFactory $collectionFactory Factory for creating collections
     * @param SearchResultsFactory $searchResultsFactory Factory for search results
     *
     * @return void
     */
    public function __construct(
        CreditCardResource $creditCardResource,
        CreditCardFactory $creditCardFactory,
        CollectionFactory $collectionFactory,
        SearchResultsFactory $searchResultsFactory
    ) {
        $this->creditCardResource = $creditCardResource;
        $this->creditCardFactory = $creditCardFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * Save Credit Card
     *
     * @param CreditCardInterface $creditCard
     * @return CreditCardInterface
     * @throws CouldNotSaveException
     */
    public function save(CreditCardInterface $creditCard)
    {
        try {
            $this->creditCardResource->save($creditCard);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save credit card: %1', $e->getMessage()));
        }
        return $creditCard;
    }

    /**
     * @param $id
     * @return CreditCardInterface
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $creditCard = $this->creditCardFactory->create();
        $this->creditCardResource->load($creditCard, $id);
        if (!$creditCard->getId()) {
            throw new NoSuchEntityException(__('Credit card with ID %1 does not exist.', $id));
        }
        return $creditCard;
    }

    /**
     * @param CreditCardInterface $creditCard
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(CreditCardInterface $creditCard): bool
    {
        try {
            $this->creditCardResource->delete($creditCard);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Unable to delete credit card: %1', $e->getMessage())
            );
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws NoSuchEntityException
     */
    public function deleteById($id)
    {
        $creditCard = $this->getById($id);
        return $this->delete($creditCard);
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResults|SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }
}
