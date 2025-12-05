<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Api;

use BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportInterface;

interface CreateDynamicCronInterface
{
    public function execute(AutomatedExportInterface $automatedExport);
}
