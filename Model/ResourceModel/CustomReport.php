<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\ResourceModel;

use BCMarketplace\CustomReportSuite\Api\SqlQueryValidatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CustomReport extends AbstractDb
{
    /**
     * @var SqlQueryValidatorInterface
     */
    private SqlQueryValidatorInterface $sqlQueryValidator;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param SqlQueryValidatorInterface $sqlQueryValidator
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        SqlQueryValidatorInterface $sqlQueryValidator,
        string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->sqlQueryValidator = $sqlQueryValidator;
    }

    protected function _construct(): void
    {
        $this->_init('bcmarketplace_report_definitions', 'report_id');
    }

    /**
     * Validate SQL query before saving
     *
     * @param AbstractModel $object
     * @return AbstractDb
     * @throws LocalizedException
     */
    protected function _beforeSave(AbstractModel $object): AbstractDb
    {
        $queryDefinition = $object->getData('query_definition');
        
        if (!empty($queryDefinition)) {
            // Normalize the SQL query to handle quotes properly
            // This ensures quotes are stored correctly without double-escaping
            $normalizedQuery = $this->normalizeSqlQuery($queryDefinition);
            
            // Validate SQL query before saving
            $this->sqlQueryValidator->validate($normalizedQuery);
            
            // Set the normalized query back to the object
            $object->setData('query_definition', $normalizedQuery);
        }

        return parent::_beforeSave($object);
    }

    /**
     * Normalize SQL query to handle quotes properly
     * Prevents double-escaping issues when storing queries with quotes
     *
     * @param string $query
     * @return string
     */
    private function normalizeSqlQuery(string $query): string
    {
        // The query will be properly escaped by PDO when saved to the database
        // We just need to ensure it's in its original form here
        // PDO will handle proper escaping when binding/executing
        return $query;
    }
}
