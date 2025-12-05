<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model\Service;

use BCMarketplace\CustomReportSuite\Api\SqlQueryValidatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * SQL Query Validator Service
 *
 * Validates SQL queries to prevent dangerous operations that could:
 * - Alter database structure
 * - Delete or modify data
 * - Create functions, procedures, or triggers
 * - Perform unauthorized operations
 */
class SqlQueryValidator implements SqlQueryValidatorInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Dangerous SQL keywords that should be blocked
     *
     * @var array
     */
    private array $dangerousKeywords = [
        // Data Definition Language (DDL)
        'ALTER TABLE',
        'ALTER DATABASE',
        'ALTER VIEW',
        'ALTER FUNCTION',
        'ALTER PROCEDURE',
        'ALTER TRIGGER',
        'CREATE TABLE',
        'CREATE DATABASE',
        'CREATE INDEX',
        'CREATE VIEW',
        'CREATE FUNCTION',
        'CREATE PROCEDURE',
        'CREATE TRIGGER',
        'CREATE USER',
        'DROP TABLE',
        'DROP DATABASE',
        'DROP INDEX',
        'DROP VIEW',
        'DROP FUNCTION',
        'DROP PROCEDURE',
        'DROP TRIGGER',
        'DROP USER',
        'TRUNCATE TABLE',
        'TRUNCATE',
        'RENAME TABLE',
        
        // Data Manipulation Language (DML) - destructive operations
        'DELETE FROM',
        'UPDATE',
        'INSERT INTO',
        'REPLACE INTO',
        
        // Security and permissions
        'GRANT',
        'REVOKE',
        'FLUSH',
        
        // Transaction control (should not be allowed in reports)
        'COMMIT',
        'ROLLBACK',
        'LOCK TABLE',
        'UNLOCK TABLE',
        
        // System operations
        'SHOW PROCESSLIST',
        'KILL',
        'EXEC',
        'EXECUTE',
        'CALL',
        
        // File operations
        'LOAD DATA',
        'LOAD FILE',
        'INTO OUTFILE',
        'INTO DUMPFILE',
        
        // Other dangerous operations
        'SET PASSWORD',
        'SET GLOBAL',
        'SET SESSION',
    ];

    /**
     * SQL keywords that indicate SELECT-only queries (allowed)
     *
     * @var array
     */
    private array $allowedKeywords = [
        'SELECT',
        'WITH', // Common Table Expressions (CTE)
    ];

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Validate SQL query for security and safety
     *
     * @param string $sqlQuery
     * @return bool
     * @throws LocalizedException
     */
    public function validate(string $sqlQuery): bool
    {
        if (empty(trim($sqlQuery))) {
            throw new LocalizedException(__('SQL query cannot be empty.'));
        }

        $normalizedQuery = $this->normalizeQuery($sqlQuery);

        // Check for dangerous keywords
        $this->checkDangerousKeywords($normalizedQuery);

        // Ensure query starts with allowed keyword (SELECT or WITH)
        $this->checkQueryStartsWithAllowedKeyword($normalizedQuery);

        // Check for suspicious patterns
        $this->checkSuspiciousPatterns($normalizedQuery);

        // Check for multiple statements (prevent SQL injection via ;)
        $this->checkMultipleStatements($sqlQuery);

        // Check for comments that might hide malicious code
        $this->checkMaliciousComments($normalizedQuery);

        return true;
    }

    /**
     * Normalize SQL query for analysis
     *
     * @param string $sqlQuery
     * @return string
     */
    private function normalizeQuery(string $sqlQuery): string
    {
        // Remove comments
        $query = preg_replace('/--.*$/m', '', $sqlQuery); // Single-line comments
        $query = preg_replace('/\/\*.*?\*\//s', '', $query); // Multi-line comments
        
        // Normalize whitespace
        $query = preg_replace('/\s+/', ' ', $query);
        
        // Convert to uppercase for keyword matching
        $query = strtoupper(trim($query));
        
        return $query;
    }

    /**
     * Check for dangerous SQL keywords
     *
     * @param string $normalizedQuery
     * @return void
     * @throws LocalizedException
     */
    private function checkDangerousKeywords(string $normalizedQuery): void
    {
        foreach ($this->dangerousKeywords as $keyword) {
            $upperKeyword = strtoupper($keyword);
            
            // Use word boundaries to prevent false positives
            // e.g., "SELECT" should not match "SELECTED"
            $pattern = '/\b' . preg_quote($upperKeyword, '/') . '\b/i';
            
            if (preg_match($pattern, $normalizedQuery)) {
                $this->logger->warning(
                    'Blocked dangerous SQL keyword detected',
                    [
                        'keyword' => $keyword,
                        'query_preview' => substr($normalizedQuery, 0, 100)
                    ]
                );
                
                throw new LocalizedException(
                    __('SQL query contains prohibited operation: %1. Only SELECT queries are allowed.', $keyword)
                );
            }
        }
    }

    /**
     * Ensure query starts with allowed keyword
     *
     * @param string $normalizedQuery
     * @return void
     * @throws LocalizedException
     */
    private function checkQueryStartsWithAllowedKeyword(string $normalizedQuery): void
    {
        $startsWithAllowed = false;
        
        foreach ($this->allowedKeywords as $keyword) {
            $upperKeyword = strtoupper($keyword);
            if (strpos($normalizedQuery, $upperKeyword) === 0) {
                $startsWithAllowed = true;
                break;
            }
        }
        
        if (!$startsWithAllowed) {
            $this->logger->warning(
                'SQL query does not start with allowed keyword',
                ['query_preview' => substr($normalizedQuery, 0, 100)]
            );
            
            throw new LocalizedException(
                __('SQL query must start with SELECT or WITH. Only read-only queries are allowed.')
            );
        }
    }

    /**
     * Check for suspicious patterns that might indicate SQL injection attempts
     *
     * @param string $normalizedQuery
     * @return void
     * @throws LocalizedException
     */
    private function checkSuspiciousPatterns(string $normalizedQuery): void
    {
        $suspiciousPatterns = [
            // SQL injection patterns - UNION SELECT can be legitimate in some cases, but we'll block it for safety
            // Note: This is conservative - legitimate UNION queries will be blocked
            // '/UNION\s+SELECT/i' => 'UNION SELECT statements are not allowed',
            
            // Multiple statements after semicolon
            '/;\s*(DROP|DELETE|UPDATE|INSERT|ALTER|CREATE|TRUNCATE)/i' => 'Multiple statements detected',
            
            // Attempts to bypass WHERE clauses
            '/WHERE\s+1\s*=\s*1/i' => 'Suspicious WHERE clause detected',
            '/WHERE\s+\'1\'\s*=\s*\'1\'/i' => 'Suspicious WHERE clause detected',
            '/OR\s+1\s*=\s*1/i' => 'SQL injection pattern detected',
            
            // Information schema access (might be used for reconnaissance)
            '/INFORMATION_SCHEMA/i' => 'Access to INFORMATION_SCHEMA is not allowed',
            
            // System tables
            '/FROM\s+MYSQL\./i' => 'Access to MySQL system tables is not allowed',
            '/FROM\s+PERFORMANCE_SCHEMA\./i' => 'Access to performance schema is not allowed',
        ];

        foreach ($suspiciousPatterns as $pattern => $message) {
            if (preg_match($pattern, $normalizedQuery)) {
                $this->logger->warning(
                    'Suspicious SQL pattern detected',
                    [
                        'pattern' => $pattern,
                        'message' => $message,
                        'query_preview' => substr($normalizedQuery, 0, 100)
                    ]
                );
                
                throw new LocalizedException(__('SQL query contains suspicious pattern: %1', $message));
            }
        }
    }

    /**
     * Check for multiple SQL statements (prevent batch execution)
     *
     * @param string $sqlQuery
     * @return void
     * @throws LocalizedException
     */
    private function checkMultipleStatements(string $sqlQuery): void
    {
        // Count semicolons (excluding those in strings)
        $inString = false;
        $stringChar = '';
        $semicolonCount = 0;
        
        for ($i = 0; $i < strlen($sqlQuery); $i++) {
            $char = $sqlQuery[$i];
            
            if (!$inString && ($char === '"' || $char === "'" || $char === '`')) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && $sqlQuery[$i - 1] !== '\\') {
                $inString = false;
            } elseif (!$inString && $char === ';') {
                $semicolonCount++;
            }
        }
        
        // Allow one semicolon at the end (will be trimmed anyway)
        if ($semicolonCount > 1) {
            $this->logger->warning(
                'Multiple SQL statements detected',
                ['semicolon_count' => $semicolonCount]
            );
            
            throw new LocalizedException(
                __('Multiple SQL statements detected. Only single SELECT queries are allowed.')
            );
        }
    }

    /**
     * Check for malicious comments that might hide code
     *
     * @param string $normalizedQuery
     * @return void
     * @throws LocalizedException
     */
    private function checkMaliciousComments(string $normalizedQuery): void
    {
        // After normalization, comments should be removed
        // If we still find comment patterns, it might indicate an attempt to bypass
        if (preg_match('/\/\*/', $normalizedQuery) || preg_match('/--/', $normalizedQuery)) {
            $this->logger->warning(
                'Malicious comment pattern detected in SQL query'
            );
            
            throw new LocalizedException(
                __('SQL query contains prohibited comment patterns.')
            );
        }
    }
}

