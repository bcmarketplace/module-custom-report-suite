<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * SQL Query Validator Interface
 *
 * Validates SQL queries to ensure they are safe and only perform read operations
 */
interface SqlQueryValidatorInterface
{
    /**
     * Validate SQL query for security and safety
     *
     * @param string $sqlQuery
     * @return bool
     * @throws LocalizedException
     */
    public function validate(string $sqlQuery): bool;
}

