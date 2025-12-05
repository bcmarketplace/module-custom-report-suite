<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\ResourceModel;

use BCMarketplace\CustomReportSuite\Api\AutomatedExportLinkRepositoryInterface;
use BCMarketplace\CustomReportSuite\Api\CreateDynamicCronInterface;
use BCMarketplace\CustomReportSuite\Model\AutomatedExportLinkFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class AutomatedExport extends AbstractDb
{
    private $automatedExportLinkFactory;
    private $automatedExportLinkRepository;
    private $searchCriteriaBuilder;
    private $setDynamicCronService;

    public function __construct(
        Context $context,
        AutomatedExportLinkFactory $automatedExportLinkFactory,
        AutomatedExportLinkRepositoryInterface $automatedExportLinkRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CreateDynamicCronInterface $setDynamicCronService,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->automatedExportLinkFactory = $automatedExportLinkFactory;
        $this->automatedExportLinkRepository = $automatedExportLinkRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->setDynamicCronService = $setDynamicCronService;
    }

    protected function _construct()
    {
        $this->_init('bcmarketplace_scheduled_exports', 'schedule_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel|\BCMarketplace\CustomReportSuite\Model\AutomatedExport $object
     *
     * @return \BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport
     */
    protected function _beforeSave(AbstractModel $object): AutomatedExport
    {
        // Handle output_formats (export types)
        $outputFormats = $object->getOutputFormats();
        if (is_array($outputFormats)) {
            $object->setData('output_formats', implode(',', $outputFormats));
        } elseif ($object->getExportTypes()) {
            // Backward compatibility: convert export_types to output_formats
            $exportTypes = $object->getExportTypes();
            if (is_array($exportTypes)) {
                $object->setData('output_formats', implode(',', $exportTypes));
            }
        }

        // Handle file_formats (file types)
        $fileFormats = $object->getFileFormats();
        if (is_array($fileFormats)) {
            $object->setData('file_formats', implode(',', $fileFormats));
        } elseif ($object->getFileTypes()) {
            // Backward compatibility: convert file_types to file_formats
            $fileTypes = $object->getFileTypes();
            if (is_array($fileTypes)) {
                $object->setData('file_formats', implode(',', $fileTypes));
            }
        }

        // Handle file_naming_template (filename pattern)
        if ($object->getFilenamePattern() && !$object->getFileNamingTemplate()) {
            $object->setData('file_naming_template', $object->getFilenamePattern());
        }

        // Handle storage_path (export location)
        if ($object->getExportLocation() && !$object->getStoragePath()) {
            $object->setData('storage_path', $object->getExportLocation());
        }

        // Handle cron_schedule (cron expression)
        if ($object->getCronExpr() && !$object->getCronSchedule()) {
            $object->setData('cron_schedule', $object->getCronExpr());
        }

        // Handle schedule_name (title)
        if ($object->getTitle() && !$object->getScheduleName()) {
            $object->setData('schedule_name', $object->getTitle());
        }

        return parent::_beforeSave($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel|\BCMarketplace\CustomReportSuite\Model\AutomatedExport $object
     *
     * @return \BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function _afterSave(AbstractModel $object): AutomatedExport
    {
        $this->saveAutomatedExportLinks($object);
        $this->setDynamicCron($object);

        return parent::_afterSave($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel|\BCMarketplace\CustomReportSuite\Model\AutomatedExport $object
     *
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function saveAutomatedExportLinks(AbstractModel $object): void
    {
        /** @var $automatedExportLink \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface */

        $reportIds = $object->getCustomreportIds();
        if (is_array($reportIds)) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('schedule_id', $object->getId())
                ->create();

            $mappings = $this->automatedExportLinkRepository->getList($searchCriteria);
            foreach ($mappings->getItems() as $mapping) {
                $this->automatedExportLinkRepository->delete($mapping);
            }

            $order = 0;
            foreach ($reportIds as $reportId) {
                $mapping = $this->automatedExportLinkFactory->create();
                $mapping->setReportId($reportId);
                $mapping->setScheduleId($object->getId());
                $mapping->setExecutionOrder($order++);
                $this->automatedExportLinkRepository->save($mapping);
            }
        }
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel|\BCMarketplace\CustomReportSuite\Model\AutomatedExport $object
     */
    private function setDynamicCron(AbstractModel $object)
    {
        return $this->setDynamicCronService->execute($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel|\BCMarketplace\CustomReportSuite\Model\AutomatedExport $object
     *
     * @return \BCMarketplace\CustomReportSuite\Model\ResourceModel\AutomatedExport
     */
    protected function _afterLoad(AbstractModel $object)
    {
        /** @var $mapping \BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface */

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('schedule_id', $object->getId())
            ->addOrder('execution_order', 'ASC')
            ->create();
        $mappings = $this->automatedExportLinkRepository->getList($searchCriteria);
        if ($mappings->getTotalCount()) {
            $reportIds = [];
            foreach ($mappings->getItems() as $mapping) {
                $reportIds[] = $mapping->getReportId();
            }
            $object->setCustomreportIds($reportIds);
        }

        // Convert output_formats to array for backward compatibility
        $outputFormats = $object->getData('output_formats');
        if ($outputFormats && !is_array($outputFormats)) {
            $object->setData('output_formats', explode(',', $outputFormats));
        }

        // Convert file_formats to array for backward compatibility
        $fileFormats = $object->getData('file_formats');
        if ($fileFormats && !is_array($fileFormats)) {
            $object->setData('file_formats', explode(',', $fileFormats));
        }

        return parent::_afterLoad($object);
    }
}
