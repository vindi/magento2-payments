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

namespace Vindi\VP\Model\ResourceModel;

use Vindi\VP\Model\RequestFactory;
use Vindi\VP\Api\Data\RequestInterfaceFactory;
use Vindi\VP\Api\Data\RequestSearchResultsInterfaceFactory;
use Vindi\VP\Api\RequestRepositoryInterface;
use Vindi\VP\Model\ResourceModel\Request as ResourceRequest;
use Vindi\VP\Model\ResourceModel\Request\CollectionFactory as RequestCollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class RequestRepository implements RequestRepositoryInterface
{

    /** @var ResourceRequest  */
    protected $resource;

    /** @var RequestFactory  */
    protected $requestFactory;

    /** @var RequestCollectionFactory  */
    protected $requestCollectionFactory;

    /** @var RequestSearchResultsInterfaceFactory  */
    protected $searchResultsFactory;

    /** @var RequestInterfaceFactory  */
    protected $dataRequestFactory;

    /** @var JoinProcessorInterface  */
    protected $extensionAttributesJoinProcessor;

    /** @var CollectionProcessorInterface  */
    private $collectionProcessor;

    /**
     * @param ResourceRequest $resource
     * @param RequestFactory $requestFactory
     * @param RequestInterfaceFactory $dataRequestFactory
     * @param RequestCollectionFactory $requestCollectionFactory
     * @param RequestSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     */
    public function __construct(
        ResourceRequest $resource,
        RequestFactory $requestFactory,
        RequestInterfaceFactory $dataRequestFactory,
        RequestCollectionFactory $requestCollectionFactory,
        RequestSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $this->resource = $resource;
        $this->requestFactory = $requestFactory;
        $this->requestCollectionFactory = $requestCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataRequestFactory = $dataRequestFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        /** @var \Vindi\VP\Model\Request $request */
        $request = $this->requestFactory->create();
        $this->resource->load($request, $id);
        if (!$request->getId()) {
            throw new NoSuchEntityException(__('Item with id "%1" does not exist.', $id));
        }
        return $request;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Vindi\VP\Api\Data\RequestInterface $request
    ) {
        try {
            $request = $this->resource->save($request);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the request info: %1',
                $exception->getMessage()
            ));
        }
        return $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->requestCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Vindi\VP\Api\Data\RequestInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }
}
