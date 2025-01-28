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
 */
class CreditCardRepository implements CreditCardRepositoryInterface
{
    private $creditCardResource;
    private $creditCardFactory;
    private $collectionFactory;
    private $searchResultsFactory;

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

    public function save(CreditCardInterface $creditCard)
    {
        try {
            $this->creditCardResource->save($creditCard);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save credit card: %1', $e->getMessage()));
        }
        return $creditCard;
    }

    public function getById($id)
    {
        $creditCard = $this->creditCardFactory->create();
        $this->creditCardResource->load($creditCard, $id);
        if (!$creditCard->getId()) {
            throw new NoSuchEntityException(__('Credit card with ID %1 does not exist.', $id));
        }
        return $creditCard;
    }

    public function delete(CreditCardInterface $creditCard)
    {
        try {
            $this->creditCardResource->delete($creditCard);
        } catch (\Exception $e) {
            throw new \Exception(__('Unable to delete credit card: %1', $e->getMessage()));
        }
        return true;
    }

    public function deleteById($id)
    {
        $creditCard = $this->getById($id);
        return $this->delete($creditCard);
    }

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
