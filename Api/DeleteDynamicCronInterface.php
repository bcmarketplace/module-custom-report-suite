<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Api;

interface DeleteDynamicCronInterface
{
    public function execute(string $automatedExportModelName);
}
