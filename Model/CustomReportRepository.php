<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model;

use BCMarketplace\CustomReportSuite\Api\CustomReportRepositoryInterface;
use BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface;
use BCMarketplace\CustomReportSuite\Model\ResourceModel\CustomReport\CollectionFactory;
use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class CustomReportRepository implements CustomReportRepositoryInterface
{
    /**
     * @var \BCMarketplace\CustomReportSuite\Model\CustomReportFactory
     */
    protected $customReportFactory;
    /**
     * @var \BCMarketplace\CustomReportSuite\Model\ResourceModel\CustomReport\CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var \Magento\Framework\Api\SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;
    /**
     * @var \BCMarketplace\CustomReportSuite\Model\ResourceModel\CustomReport
     */
    private $customReportResource;

    /**
     * CustomReportRepository constructor.
     *
     * @param \BCMarketplace\CustomReportSuite\Model\CustomReportFactory                          $customReportFactory
     * @param \BCMarketplace\CustomReportSuite\Model\ResourceModel\CustomReport\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Api\SearchResultsInterfaceFactory                  $searchResultsFactory
     * @param \BCMarketplace\CustomReportSuite\Model\ResourceModel\CustomReport                   $customReportResource
     */
    public function __construct(
        CustomReportFactory $customReportFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        \BCMarketplace\CustomReportSuite\Model\ResourceModel\CustomReport $customReportResource
    ) {
        $this->customReportFactory = $customReportFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->customReportResource = $customReportResource;
    }

    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface|\BCMarketplace\CustomReportSuite\Model\CustomReport $customReport
     *
     * @return \BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(CustomReportInterface $customReport): CustomReportInterface
    {
        try {
            $this->customReportResource->save($customReport);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $customReport;
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
     * @param \BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface|\BCMarketplace\CustomReportSuite\Model\CustomReport $customReport
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(CustomReportInterface $customReport): bool
    {
        try {
            $this->customReportResource->delete($customReport);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param $id
     *
     * @return \BCMarketplace\CustomReportSuite\Model\CustomReport
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id): CustomReport
    {
        $customReport = $this->customReportFactory->create();
        $this->customReportResource->load($customReport, $id);
        if (!$customReport->getId()) {
            throw new NoSuchEntityException(__('Object with id "%1" does not exist.', $id));
        }

        return $customReport;
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
