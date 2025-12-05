<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model;

use BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Scheduled Export Model
 *
 * @method int getId()
 * @method string getScheduleName()
 * @method string getScheduleCode()
 * @method string getCronSchedule()
 * @method string|array getOutputFormats()
 * @method string|array getFileFormats()
 * @method string getFileNamingTemplate()
 * @method string getStoragePath()
 * @method bool getIsEnabled()
 * @method int getExecutionCount()
 * @method string getLastRunAt()
 * @method string getNextRunAt()
 * @method AutomatedExport setScheduleName(string $name)
 * @method AutomatedExport setScheduleCode(string $code)
 * @method AutomatedExport setCronSchedule(string $schedule)
 * @method AutomatedExport setOutputFormats(string|array $formats)
 * @method AutomatedExport setFileFormats(string|array $formats)
 * @method AutomatedExport setFileNamingTemplate(string $template)
 * @method AutomatedExport setStoragePath(string $path)
 * @method AutomatedExport setIsEnabled(bool $enabled)
 * @method AutomatedExport setExecutionCount(int $count)
 * @method AutomatedExport setLastRunAt(string $dateTime)
 * @method AutomatedExport setNextRunAt(string $dateTime)
 */
class AutomatedExport extends AbstractModel implements AutomatedExportInterface, IdentityInterface
{
    const CACHE_TAG = 'bcmarketplace_customreportsuite_schedule';

    /**
     * Get cache identities
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\AutomatedExport::class);
    }

    /**
     * Get title (alias for schedule_name for backward compatibility)
     *
     * @return string
     */
    public function getTitle(): string
    {
        return (string)$this->getScheduleName();
    }

    /**
     * Get cron expression (alias for cron_schedule for backward compatibility)
     *
     * @return string
     */
    public function getCronExpr(): string
    {
        return (string)$this->getCronSchedule();
    }

    /**
     * Get export types (alias for output_formats for backward compatibility)
     *
     * @return string|array
     */
    public function getExportTypes()
    {
        return $this->getOutputFormats();
    }

    /**
     * Get file types (alias for file_formats for backward compatibility)
     *
     * @return string|array
     */
    public function getFileTypes()
    {
        return $this->getFileFormats();
    }

    /**
     * Get filename pattern (alias for file_naming_template for backward compatibility)
     *
     * @return string
     */
    public function getFilenamePattern(): string
    {
        return (string)$this->getFileNamingTemplate();
    }

    /**
     * Get export location (alias for storage_path for backward compatibility)
     *
     * @return string
     */
    public function getExportLocation(): string
    {
        return (string)$this->getStoragePath();
    }

    /**
     * Increment execution count and update last run timestamp
     *
     * @return $this
     */
    public function incrementExecutionCount(): AutomatedExport
    {
        $this->setExecutionCount((int)$this->getExecutionCount() + 1);
        $this->setLastRunAt(date('Y-m-d H:i:s'));
        return $this;
    }
}
