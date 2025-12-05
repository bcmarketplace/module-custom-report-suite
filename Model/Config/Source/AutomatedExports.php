<?php
declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\Config\Source;

use BCMarketplace\CustomReportSuite\Api\AutomatedExportRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

class AutomatedExports implements OptionSourceInterface
{
    private $searchCriteriaBuilder;
    private $automatedExportRepository;

    /**
     * AutomatedExports constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder              $searchCriteriaBuilder
     * @param \BCMarketplace\CustomReportSuite\Api\AutomatedExportRepositoryInterface $automatedExportRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AutomatedExportRepositoryInterface $automatedExportRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->automatedExportRepository = $automatedExportRepository;
    }

    public function toOptionArray(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $automatedExports = $this->automatedExportRepository->getList($searchCriteria);

        $options = [];

        foreach ($automatedExports as $automatedExport) {
            $options[] = ['value' => $automatedExport->getId(), 'label' => $automatedExport->getName()];
        }

        return $options;
    }
}
