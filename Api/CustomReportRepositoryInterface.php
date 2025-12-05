<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Api;

use BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface;
use BCMarketplace\CustomReportSuite\Model\CustomReport;
use Magento\Framework\Api\SearchCriteriaInterface;

interface CustomReportRepositoryInterface
{
    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface $customReport
     *
     * @return \BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(CustomReportInterface $customReport): CustomReportInterface;

    /**
     * @param $id
     *
     * @return \BCMarketplace\CustomReportSuite\Model\CustomReport
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id): CustomReport;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface $customReport
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(CustomReportInterface $customReport): bool;

    /**
     * @param $id
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($id): bool;
}
