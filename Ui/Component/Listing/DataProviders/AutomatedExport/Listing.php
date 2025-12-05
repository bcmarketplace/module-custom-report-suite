<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Ui\Component\Listing\DataProviders\AutomatedExport;

use BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class Listing extends AbstractDataProvider
{
    /**
     * Customreports constructor.
     *
     * @param string                                                                $name
     * @param string                                                                $primaryFieldName
     * @param string                                                                $requestFieldName
     * @param \BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport\CollectionFactory $collectionFactory
     * @param array                                                                 $meta
     * @param array                                                                 $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }
}
