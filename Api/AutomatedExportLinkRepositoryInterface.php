<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Api;

use BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface AutomatedExportLinkRepositoryInterface
{
    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface $automatedExportLink
     *
     * @return \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(AutomatedExportLinkInterface $automatedExportLink): AutomatedExportLinkInterface;

    /**
     * @param $id
     *
     * @return \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id): AutomatedExportLinkInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface $automatedExportLink
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(AutomatedExportLinkInterface $automatedExportLink): bool;

    /**
     * @param $id
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($id): bool;
}
