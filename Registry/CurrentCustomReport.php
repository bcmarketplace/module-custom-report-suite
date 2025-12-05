<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Registry;

use BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface;

class CurrentCustomReport
{
    /**
     * @var \BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface|null
     */
    private $currentCustomReport;

    /**
     * @param \BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface|null $currentCustomReport
     */
    public function set(CustomReportInterface $currentCustomReport): void
    {
        $this->currentCustomReport = $currentCustomReport;
    }

    /**
     * @return \BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface|null
     */
    public function get(): ?CustomReportInterface
    {
        return $this->currentCustomReport;
    }
}
