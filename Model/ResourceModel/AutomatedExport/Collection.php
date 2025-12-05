<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport;

use BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(\BCMarketplace\CustomReportSuite\Model\AutomatedExport::class, AutomatedExport::class);
    }

    /**
     * Add report IDs to collection
     *
     * @return $this
     */
    public function addReportIds(): Collection
    {
        $this->getSelect()->joinLeft(
            ['mapping' => $this->getTable('bcmarketplace_schedule_report_mapping')],
            'mapping.schedule_id = main_table.schedule_id',
            ['GROUP_CONCAT(mapping.report_id ORDER BY mapping.execution_order) as report_ids']
        )->group('main_table.schedule_id');

        return $this;
    }

    /**
     * Add report IDs to collection (alias for addReportIds)
     *
     * @return $this
     */
    public function addCustomreportIds(): Collection
    {
        return $this->addReportIds();
    }

    /**
     * Filter by active schedules only
     *
     * @return $this
     */
    public function addActiveFilter(): Collection
    {
        $this->addFieldToFilter('is_enabled', 1);
        return $this;
    }
}
