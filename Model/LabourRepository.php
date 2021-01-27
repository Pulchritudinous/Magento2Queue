<?php
declare(strict_types=1);

namespace Pulchritudinous\Queue\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Pulchritudinous\Queue\Api\Data\LabourInterfaceFactory;
use Pulchritudinous\Queue\Api\Data\LabourSearchResultsInterfaceFactory;
use Pulchritudinous\Queue\Api\LabourRepositoryInterface;
use Pulchritudinous\Queue\Model\ResourceModel\Labour as ResourceLabour;
use Pulchritudinous\Queue\Model\ResourceModel\Labour\CollectionFactory as LabourCollectionFactory;

class LabourRepository implements LabourRepositoryInterface
{

    protected $dataLabourFactory;

    private $storeManager;

    protected $dataObjectProcessor;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    protected $labourFactory;

    protected $searchResultsFactory;

    protected $labourCollectionFactory;

    protected $extensionAttributesJoinProcessor;

    protected $resource;

    protected $dataObjectHelper;


    /**
     * @param ResourceLabour $resource
     * @param LabourFactory $labourFactory
     * @param LabourInterfaceFactory $dataLabourFactory
     * @param LabourCollectionFactory $labourCollectionFactory
     * @param LabourSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceLabour $resource,
        LabourFactory $labourFactory,
        LabourInterfaceFactory $dataLabourFactory,
        LabourCollectionFactory $labourCollectionFactory,
        LabourSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->labourFactory = $labourFactory;
        $this->labourCollectionFactory = $labourCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataLabourFactory = $dataLabourFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Pulchritudinous\Queue\Api\Data\LabourInterface $labour
    ) {
        /* if (empty($labour->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $labour->setStoreId($storeId);
        } */

        $labourData = $this->extensibleDataObjectConverter->toNestedArray(
            $labour,
            [],
            \Pulchritudinous\Queue\Api\Data\LabourInterface::class
        );

        $labourModel = $this->labourFactory->create()->setData($labourData);

        try {
            $this->resource->save($labourModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the labour: %1',
                $exception->getMessage()
            ));
        }
        return $labourModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($labourId)
    {
        $labour = $this->labourFactory->create();
        $this->resource->load($labour, $labourId);
        if (!$labour->getId()) {
            throw new NoSuchEntityException(__('Labour with id "%1" does not exist.', $labourId));
        }
        return $labour->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->labourCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Pulchritudinous\Queue\Api\Data\LabourInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Pulchritudinous\Queue\Api\Data\LabourInterface $labour
    ) {
        try {
            $labourModel = $this->labourFactory->create();
            $this->resource->load($labourModel, $labour->getLabourId());
            $this->resource->delete($labourModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Labour: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($labourId)
    {
        return $this->delete($this->get($labourId));
    }
}

