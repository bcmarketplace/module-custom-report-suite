<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\ResourceModel\CustomReport;

use BCMarketplace\CustomReportSuite\Model\ResourceModel\CustomReport;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\BCMarketplace\CustomReportSuite\Model\CustomReport::class, CustomReport::class);
    }
}
