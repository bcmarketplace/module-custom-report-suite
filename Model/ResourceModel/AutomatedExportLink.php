<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AutomatedExportLink extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('bcmarketplace_schedule_report_mapping', 'mapping_id');
    }
}
