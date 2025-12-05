<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model;

use BCMarketplace\CustomReportSuite\Api\AutomatedExportLinkRepositoryInterface;
use BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface;
use BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExportLink\CollectionFactory;
use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class AutomatedExportLinkRepository implements AutomatedExportLinkRepositoryInterface
{
    /**
     * @var \BCMarketplace\CustomReportSuite\Model\AutomatedExportLinkFactory
     */
    protected $automatedExportLinkFactory;
    /**
     * @var \BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExportLink\CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var \Magento\Framework\Api\SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;
    /**
     * @var \BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExportLink
     */
    private $automatedExportLinkResource;

    /**
     * AutomatedExportLinkRepository constructor.
     *
     * @param \BCMarketplace\CustomReportSuite\Model\AutomatedExportLinkFactory                          $automatedExportLinkFactory
     * @param \BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExportLink\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Api\SearchResultsInterfaceFactory                  $searchResultsFactory
     * @param \BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExportLink                   $automatedExportLinkResource
     */
    public function __construct(
        AutomatedExportLinkFactory $automatedExportLinkFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        \BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExportLink $automatedExportLinkResource
    ) {
        $this->automatedExportLinkFactory = $automatedExportLinkFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->automatedExportLinkResource = $automatedExportLinkResource;
    }

    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface|\BCMarketplace\CustomReportSuite\Model\AutomatedExportLink $automatedExportLink
     *
     * @return \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(AutomatedExportLinkInterface $automatedExportLink): AutomatedExportLinkInterface
    {
        try {
            $this->automatedExportLinkResource->save($automatedExportLink);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $automatedExportLink;
    }

    /**
     * @param $id
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($id): bool
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface|\BCMarketplace\CustomReportSuite\Model\AutomatedExportLink $automatedExportLink
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(AutomatedExportLinkInterface $automatedExportLink): bool
    {
        try {
            $this->automatedExportLinkResource->delete($automatedExportLink);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param $id
     *
     * @return \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id): AutomatedExportLinkInterface
    {
        $automatedExportLink = $this->automatedExportLinkFactory->create();
        $this->automatedExportLinkResource->load($automatedExportLink, $id);
        if (!$automatedExportLink->getId()) {
            throw new NoSuchEntityException(__('Object with id "%1" does not exist.', $id));
        }

        return $automatedExportLink;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $objects = [];
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }
        $searchResults->setItems($objects);

        return $searchResults;
    }
}
