<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model;

use Exception;
use Magento\Framework\Api\ExtensionAttribute\JoinDataInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Sql\Expression;
use Psr\Log\LoggerInterface as Logger;

class GenericReportCollection extends AbstractDb
{
    /**
     * GenericReportCollection constructor.
     *
     * @param EntityFactoryInterface $entityFactory
     * @param Logger $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ResourceConnection $resourceConnection
     * @param AdapterInterface|null $connection
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        Logger $logger,
        FetchStrategyInterface $fetchStrategy,
        ResourceConnection $resourceConnection,
        AdapterInterface $connection = null
    ) {
        // Use default connection for read operations
        // Note: For production, consider using a read-only connection if available
        $connection = $connection ?? $resourceConnection->getConnection();

        parent::__construct($entityFactory, $logger, $fetchStrategy, $connection);
    }

    /**
     * Intentionally left empty since this is a generic resource.
     *
     * @noinspection PhpMissingReturnTypeInspection*/
    public function getResource()
    {
    }

    /**
     * @param JoinDataInterface      $join
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     *
     * @return $this
     * @throws \Exception
     */
    public function joinExtensionAttribute(
        JoinDataInterface $join,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ): GenericReportCollection {
        throw new Exception('joinExtensionAttribute is not allowed in GenericReportCollection');
    }

    /**
     * Add order to collection
     * Override to properly quote column names with spaces or special characters
     *
     * @param string|array $field
     * @param string $direction
     * @return $this
     */
    public function addOrder($field, $direction = self::SORT_ORDER_DESC): GenericReportCollection
    {
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $this->addOrder($key, $value);
            }
            return $this;
        }

        $fieldString = (string)$field;
        $quotedField = $this->quoteColumnIdentifier($fieldString);
        
        // Use Expression to ensure the quoted identifier is preserved
        // This prevents the select object from stripping quotes
        $orderExpression = new Expression($quotedField . ' ' . $direction);
        $this->_select->order($orderExpression);
        
        return $this;
    }

    /**
     * Set order for collection
     * Override to properly quote column names with spaces or special characters
     *
     * @param string|array $field
     * @param string $direction
     * @return $this
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC): GenericReportCollection
    {
        // Clear existing orders
        $this->_select->reset(\Zend_Db_Select::ORDER);
        
        // Add the new order with proper quoting
        return $this->addOrder($field, $direction);
    }

    /**
     * Quote column identifier for use in ORDER BY clause
     * Handles column aliases with spaces, special characters, etc.
     *
     * @param string $identifier
     * @return string
     */
    private function quoteColumnIdentifier(string $identifier): string
    {
        // Remove any existing backticks
        $identifier = trim($identifier, '`');
        
        // Always quote identifiers to handle spaces and special characters
        // This is safe because we're quoting column aliases from user-defined SQL
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
