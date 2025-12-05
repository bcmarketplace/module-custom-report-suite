<?php
declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ExportType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'local_file_drop', 'label' => __('Local File Drop')],
        ];
    }
}
