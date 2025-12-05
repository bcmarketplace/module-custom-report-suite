<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model;

use BCMarketplace\CustomReportSuite\Api\Data\AutomatedExportLinkInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Schedule to Report Mapping Model
 *
 * @method int getId()
 * @method int getScheduleId()
 * @method int getReportId()
 * @method int getExecutionOrder()
 * @method string getCreatedAt()
 * @method string getUpdatedAt()
 * @method AutomatedExportLink setScheduleId(int $scheduleId)
 * @method AutomatedExportLink setReportId(int $reportId)
 * @method AutomatedExportLink setExecutionOrder(int $order)
 * @method AutomatedExportLink setCreatedAt(string $createdAt)
 * @method AutomatedExportLink setUpdatedAt(string $updatedAt)
 */
class AutomatedExportLink extends AbstractModel implements AutomatedExportLinkInterface, IdentityInterface
{
    const CACHE_TAG = 'bcmarketplace_customreportsuite_mapping';

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
        $this->_init(ResourceModel\AutomatedExportLink::class);
    }

    /**
     * Get custom report ID (alias for report_id for backward compatibility)
     *
     * @return int
     */
    public function getCustomreportId(): int
    {
        return (int)$this->getReportId();
    }

    /**
     * Get automated export ID (alias for schedule_id for backward compatibility)
     *
     * @return int
     */
    public function getAutomatedexportId(): int
    {
        return (int)$this->getScheduleId();
    }

    /**
     * Set custom report ID (alias for report_id for backward compatibility)
     *
     * @param int $reportId
     * @return $this
     */
    public function setCustomreportId(int $reportId): AutomatedExportLink
    {
        return $this->setReportId($reportId);
    }

    /**
     * Set automated export ID (alias for schedule_id for backward compatibility)
     *
     * @param int $scheduleId
     * @return $this
     */
    public function setAutomatedexportId(int $scheduleId): AutomatedExportLink
    {
        return $this->setScheduleId($scheduleId);
    }
}
