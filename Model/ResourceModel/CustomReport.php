<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\ResourceModel;

use BCMarketplace\CustomReportSuite\Api\SqlQueryValidatorInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Zend_Db_Statement_Exception;
use PDOException;

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
            
            // Validate SQL query syntax and security before saving
            $this->sqlQueryValidator->validate($normalizedQuery);
            
            // Test SQL query execution to catch runtime errors
            $this->validateSqlExecution($normalizedQuery);
            
            // Set the normalized query back to the object
            $object->setData('query_definition', $normalizedQuery);
        }

        return parent::_beforeSave($object);
    }

    /**
     * Validate SQL query execution by attempting to run it
     * This catches runtime errors like missing columns, tables, etc.
     *
     * @param string $query
     * @return void
     * @throws LocalizedException
     */
    private function validateSqlExecution(string $query): void
    {
        $connection = $this->getConnection();
        
        try {
            // Wrap the query in a subquery with LIMIT 1 to test execution
            // This prevents heavy queries from running during validation
            $testQuery = 'SELECT `t`.* FROM (' . trim($query, ';') . ') AS `t` LIMIT 1';
            
            // Attempt to execute the query
            $connection->query($testQuery);
        } catch (Zend_Db_Statement_Exception $e) {
            // Extract the error message from the exception
            $errorMessage = $this->extractSqlErrorMessage($e);
            throw new LocalizedException(__($errorMessage));
        } catch (PDOException $e) {
            // Extract the error message from PDO exception
            $errorMessage = $this->extractSqlErrorMessage($e);
            throw new LocalizedException(__($errorMessage));
        } catch (\Exception $e) {
            // Catch any other database-related exceptions
            $errorMessage = $this->extractSqlErrorMessage($e);
            throw new LocalizedException(__($errorMessage));
        }
    }

    /**
     * Extract and format SQL error message from exception
     *
     * @param \Exception $exception
     * @return string
     */
    private function extractSqlErrorMessage(\Exception $exception): string
    {
        $message = $exception->getMessage();
        
        // Try to extract the SQL error message
        // Format: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'p.sku' in 'SELECT', query was: SELECT ...
        // We want: Column not found: 1054 Unknown column 'p.sku' in 'SELECT', query was: SELECT ...
        
        // Pattern 1: Match SQLSTATE format with query
        if (preg_match('/SQLSTATE\[[^\]]+\]:\s*(.+?)(?:,\s*query was:\s*(.+))?$/s', $message, $matches)) {
            $errorMessage = trim($matches[1] ?? '');
            $query = isset($matches[2]) ? trim($matches[2]) : '';
            
            // If query is not in the exception message, try to get it from the previous exception
            if (empty($query) && $exception->getPrevious()) {
                $previousMessage = $exception->getPrevious()->getMessage();
                if (preg_match('/query was:\s*(.+)$/s', $previousMessage, $queryMatches)) {
                    $query = trim($queryMatches[1] ?? '');
                }
            }
            
            // Format the error message for display
            $formattedMessage = $errorMessage;
            
            // If query is present, append a truncated version
            if (!empty($query)) {
                // Truncate query to first 200 characters for readability
                $truncatedQuery = strlen($query) > 200 
                    ? substr($query, 0, 200) . '...' 
                    : $query;
                $formattedMessage .= ', query was: ' . $truncatedQuery;
            }
            
            return $formattedMessage;
        }
        
        // Pattern 2: Try to extract error code and message (e.g., "1054 Unknown column...")
        if (preg_match('/:\s*(\d+\s+.+?)(?:,\s*query was:\s*(.+))?$/s', $message, $matches)) {
            $errorMessage = trim($matches[1] ?? '');
            $query = isset($matches[2]) ? trim($matches[2]) : '';
            
            // Try to find the query part if not already extracted
            if (empty($query) && preg_match('/query was:\s*(.+)$/s', $message, $queryMatches)) {
                $query = trim($queryMatches[1] ?? '');
            }
            
            // Also check previous exception for query
            if (empty($query) && $exception->getPrevious()) {
                $previousMessage = $exception->getPrevious()->getMessage();
                if (preg_match('/query was:\s*(.+)$/s', $previousMessage, $queryMatches)) {
                    $query = trim($queryMatches[1] ?? '');
                }
            }
            
            $formattedMessage = $errorMessage;
            if (!empty($query)) {
                $truncatedQuery = strlen($query) > 200 
                    ? substr($query, 0, 200) . '...' 
                    : $query;
                $formattedMessage .= ', query was: ' . $truncatedQuery;
            }
            
            return $formattedMessage;
        }
        
        // Fallback: try to extract just the error message part before "query was"
        if (preg_match('/(.+?)(?:,\s*query was:.*)?$/s', $message, $matches)) {
            $errorMessage = trim($matches[1] ?? '');
            // Remove SQLSTATE prefix if present
            $errorMessage = preg_replace('/^SQLSTATE\[[^\]]+\]:\s*/', '', $errorMessage);
            return $errorMessage;
        }
        
        // Final fallback to original message if we can't parse it
        return $message;
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
