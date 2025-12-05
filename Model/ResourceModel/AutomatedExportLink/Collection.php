<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExportLink;

use BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExportLink;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\BCMarketplace\CustomReportSuite\Model\AutomatedExportLink::class, AutomatedExportLink::class);
    }
}
