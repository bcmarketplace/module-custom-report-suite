<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model;

use BCMarketplace\CustomReportSuite\Api\Data\CustomReportInterface;
use BCMarketplace\CustomReportSuite\Api\SqlQueryValidatorInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\DB\Sql\Expression;

/**
 * Custom Report Model
 *
 * @method int getId()
 * @method string getReportTitle()
 * @method string getReportCode()
 * @method string getQueryDefinition()
 * @method string getDescription()
 * @method bool getIsActive()
 * @method int getExecutionCount()
 * @method string getLastExecutedAt()
 * @method CustomReport setReportTitle(string $title)
 * @method CustomReport setReportCode(string $code)
 * @method CustomReport setQueryDefinition(string $query)
 * @method CustomReport setDescription(string $description)
 * @method CustomReport setIsActive(bool $isActive)
 * @method CustomReport setExecutionCount(int $count)
 * @method CustomReport setLastExecutedAt(string $dateTime)
 */
class CustomReport extends AbstractModel implements CustomReportInterface, IdentityInterface
{
    const CACHE_TAG = 'bcmarketplace_customreportsuite_report';

    /**
     * @var GenericReportCollectionFactory
     */
    private GenericReportCollectionFactory $genericReportCollectionFactory;

    /**
     * @var SqlQueryValidatorInterface
     */
    private SqlQueryValidatorInterface $sqlQueryValidator;

    /**
     * CustomReport constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param GenericReportCollectionFactory $genericReportCollectionFactory
     * @param SqlQueryValidatorInterface $sqlQueryValidator
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        GenericReportCollectionFactory $genericReportCollectionFactory,
        SqlQueryValidatorInterface $sqlQueryValidator,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->genericReportCollectionFactory = $genericReportCollectionFactory;
        $this->sqlQueryValidator = $sqlQueryValidator;
    }

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
     * Get generic report collection with formatted SQL
     *
     * @return GenericReportCollection
     * @throws LocalizedException
     */
    public function getGenericReportCollection(): GenericReportCollection
    {
        $queryDefinition = $this->getData('query_definition');
        
        if (empty($queryDefinition)) {
            throw new LocalizedException(__('SQL query definition is empty.'));
        }

        // Validate SQL query before execution
        $this->sqlQueryValidator->validate($queryDefinition);

        $genericReportCollection = $this->genericReportCollectionFactory->create();
        $formattedSql = $this->formatSql($queryDefinition);
        
        // Use Expression to handle raw SQL properly
        // Expression treats the string as raw SQL and doesn't escape it
        // The SQL should be stored and retrieved as-is from the database
        // PDO will handle proper escaping when the query is executed
        $genericReportCollection->getSelect()->from(new Expression('(' . $formattedSql . ')'));

        return $genericReportCollection;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\CustomReport::class);
    }

    /**
     * Format SQL query by removing trailing semicolon
     * 
     * The SQL query is stored in the database and retrieved as-is.
     * When used in Expression, it's treated as raw SQL and executed directly.
     * PDO handles proper escaping when the query is executed.
     *
     * @param string $rawSql
     * @return string
     */
    protected function formatSql(string $rawSql): string
    {
        // Remove trailing semicolon if present
        // The SQL should be used as-is from the database
        // Expression will treat it as raw SQL without additional escaping
        return trim($rawSql, ';');
    }

    /**
     * Increment execution count and update last executed timestamp
     *
     * @return $this
     */
    public function incrementExecutionCount(): CustomReport
    {
        $this->setExecutionCount((int)$this->getExecutionCount() + 1);
        $this->setLastExecutedAt(date('Y-m-d H:i:s'));
        return $this;
    }

    /**
     * Get report name (alias for report_title for backward compatibility)
     *
     * @return string
     */
    public function getReportName(): string
    {
        return (string)$this->getReportTitle();
    }

    /**
     * Get report SQL (alias for query_definition for backward compatibility)
     *
     * @return string
     */
    public function getReportSql(): string
    {
        return (string)$this->getQueryDefinition();
    }
}
