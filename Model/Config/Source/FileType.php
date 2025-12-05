<?php
declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FileType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'csv', 'label' => __('CSV')],
        ];
    }
}
