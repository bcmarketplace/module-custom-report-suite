<?php

namespace BCMarketplace\CustomReportSuite\Test\Unit\Model;

use BCMarketplace\CustomReportSuite\Model\AutomatedExportFactory;
use BCMarketplace\CustomReportSuite\Model\AutomatedExportRepository;
use BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport;
use BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport\CollectionFactory;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

class AutomatedExportRepositoryTest extends TestCase
{
    /**
     * @var AutomatedExportRepository
     */
    protected $automatedExportRepository;

    /**
     * @var AutomatedExportFactory|Mock
     */
    protected $automatedExportFactory;

    /**
     * @var CollectionFactory|Mock
     */
    protected $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory|Mock
     */
    protected $searchResultsFactory;

    /**
     * @var AutomatedExport|Mock
     */
    protected $automatedExportResource;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->automatedExportFactory = $this->createMock(AutomatedExportFactory::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactory = $this->createMock(SearchResultsInterfaceFactory::class);
        $this->automatedExportResource = $this->createMock(AutomatedExport::class);
        $this->automatedExportRepository = new AutomatedExportRepository($this->automatedExportFactory, $this->collectionFactory, $this->searchResultsFactory, $this->automatedExportResource);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->automatedExportRepository);
        unset($this->automatedExportFactory);
        unset($this->collectionFactory);
        unset($this->searchResultsFactory);
        unset($this->automatedExportResource);
    }

    public function testSave(): void
    {
        $model = $this->createMock(\BCMarketplace\CustomReportSuite\Model\AutomatedExport::class);

        $this->automatedExportRepository->save($model);
    }

    public function testDeleteById(): void
    {
        $model = $this->createMock(\BCMarketplace\CustomReportSuite\Model\AutomatedExport::class);
        $this->automatedExportFactory->method('create')->willReturn($model);
        $model->method('getId')->willReturn(1);

        $this->automatedExportRepository->deleteById(1);
    }


    public function testGetList(): void
    {
        $criteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteriaInterface::class);

        $searchMock = $this->createMock(\Magento\Framework\Api\SearchResultsInterface::class);
        $this->searchResultsFactory->method('create')->willReturn($searchMock);

        $filterGroup = $this->createMock(\Magento\Framework\Api\Search\FilterGroup::class);
        $criteriaMock->method('getFilterGroups')->willReturn([$filterGroup]);

        $filter = $this->createMock(\Magento\Framework\Api\Filter::class);
        $filterGroup->method('getFilters')->willReturn([$filter]);

        $collectionMock = $this->createMock(\BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport\Collection
                                            ::class);
        $this->collectionFactory->method('create')->willReturn($collectionMock);

        $sortOrdersMock = $this->createMock(\Magento\Framework\Api\SortOrder::class);
        $criteriaMock->method('getSortOrders')->willReturn([$sortOrdersMock]);

        $collectionMock->method('getIterator')
            ->willReturn(new \ArrayObject([$this->createMock(DataObject::class)]));

        $this->automatedExportRepository->getList($criteriaMock);
    }
}
