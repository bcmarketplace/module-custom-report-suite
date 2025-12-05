<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Api;

use BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportInterface;
use BCMarketplace\CustomReportSuite\Model\AutomatedExport;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface AutomatedExportRepositoryInterface
{
    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportInterface $automatedExport
     *
     * @return \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(AutomatedExportInterface $automatedExport): AutomatedExportInterface;

    /**
     * @param $id
     *
     * @return \BCMarketplace\CustomReportSuite\Model\AutomatedExport
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id): AutomatedExport;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;

    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportInterface $automatedExport
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(AutomatedExportInterface $automatedExport): bool;

    /**
     * @param $id
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($id): bool;
}
