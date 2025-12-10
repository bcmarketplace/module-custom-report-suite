<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Test\Unit\Model\Service;

use BCMarketplace\CustomReportSuite\Model\Service\SqlQueryValidator;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Comprehensive security test cases for SQL Query Validator
 * Tests various bypass attempts and edge cases
 */
class SqlQueryValidatorSecurityTest extends TestCase
{
    /**
     * @var SqlQueryValidator
     */
    private $validator;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->validator = new SqlQueryValidator($this->loggerMock);
    }

    /**
     * Test case variation bypass attempts
     */
    public function testCaseVariationBypassAttempts(): void
    {
        $maliciousQueries = [
            'select * from sales_order; alter table sales_order add column test varchar(255);',
            'SeLeCt * FrOm SaLeS_oRdEr; AlTeR tAbLe SaLeS_oRdEr AdD cOlUmN tEsT vArChAr(255);',
            'SELECT * FROM sales_order; ALTER TABLE sales_order ADD COLUMN test VARCHAR(255);',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test comment-based bypass attempts
     */
    public function testCommentBasedBypassAttempts(): void
    {
        $maliciousQueries = [
            'SELECT * FROM sales_order; /* ALTER TABLE sales_order */ DROP TABLE sales_order;',
            'SELECT * FROM sales_order; -- ALTER TABLE sales_order ADD COLUMN test VARCHAR(255)',
            'SELECT * FROM sales_order; /* comment */ ALTER /* comment */ TABLE /* comment */ sales_order;',
            'SELECT * FROM sales_order WHERE 1=1; -- hidden: ALTER TABLE sales_order',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test whitespace manipulation bypass attempts
     */
    public function testWhitespaceBypassAttempts(): void
    {
        $maliciousQueries = [
            'SELECT * FROM sales_order; ALTER    TABLE sales_order ADD COLUMN test VARCHAR(255);',
            "SELECT * FROM sales_order; ALTER\nTABLE sales_order ADD COLUMN test VARCHAR(255);",
            "SELECT * FROM sales_order; ALTER\r\nTABLE sales_order ADD COLUMN test VARCHAR(255);",
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test multiple statement injection
     */
    public function testMultipleStatementInjection(): void
    {
        $maliciousQueries = [
            'SELECT * FROM sales_order; DELETE FROM sales_order WHERE 1=1;',
            'SELECT * FROM sales_order; UPDATE sales_order SET status = \'deleted\';',
            'SELECT * FROM sales_order; INSERT INTO sales_order (status) VALUES (\'test\');',
            'SELECT * FROM sales_order; TRUNCATE TABLE sales_order;',
            'SELECT * FROM sales_order; DROP TABLE IF EXISTS sales_order;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test ALTER TABLE variations
     */
    public function testAlterTableVariations(): void
    {
        $maliciousQueries = [
            'ALTER TABLE sales_order ADD COLUMN test VARCHAR(255);',
            'ALTER TABLE sales_order DROP COLUMN status;',
            'ALTER TABLE sales_order MODIFY COLUMN status VARCHAR(100);',
            'ALTER TABLE sales_order RENAME COLUMN status TO new_status;',
            'ALTER TABLE sales_order ADD INDEX idx_status (status);',
            'ALTER TABLE sales_order DROP INDEX idx_status;',
            'ALTER TABLE sales_order ENGINE=InnoDB;',
            'ALTER TABLE sales_order CHARACTER SET utf8mb4;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test DROP operations
     */
    public function testDropOperations(): void
    {
        $maliciousQueries = [
            'DROP TABLE sales_order;',
            'DROP TABLE IF EXISTS sales_order;',
            'DROP DATABASE magento;',
            'DROP INDEX idx_status ON sales_order;',
            'DROP VIEW sales_view;',
            'DROP FUNCTION test_function;',
            'DROP PROCEDURE test_procedure;',
            'DROP TRIGGER test_trigger;',
            'DROP USER \'test\'@\'localhost\';',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test CREATE operations
     */
    public function testCreateOperations(): void
    {
        $maliciousQueries = [
            'CREATE TABLE test_table (id INT PRIMARY KEY);',
            'CREATE DATABASE test_db;',
            'CREATE INDEX idx_test ON sales_order (status);',
            'CREATE VIEW test_view AS SELECT * FROM sales_order;',
            'CREATE FUNCTION test() RETURNS INT BEGIN RETURN 1; END;',
            'CREATE PROCEDURE test() BEGIN SELECT 1; END;',
            'CREATE TRIGGER test_trigger BEFORE INSERT ON sales_order FOR EACH ROW BEGIN END;',
            'CREATE USER \'test\'@\'localhost\' IDENTIFIED BY \'password\';',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test UPDATE/DELETE/INSERT operations
     */
    public function testDataModificationOperations(): void
    {
        $maliciousQueries = [
            'UPDATE sales_order SET status = \'complete\' WHERE 1=1;',
            'DELETE FROM sales_order WHERE 1=1;',
            'INSERT INTO sales_order (status) VALUES (\'test\');',
            'REPLACE INTO sales_order (status) VALUES (\'test\');',
            'INSERT IGNORE INTO sales_order (status) VALUES (\'test\');',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test TRUNCATE operations
     */
    public function testTruncateOperations(): void
    {
        $maliciousQueries = [
            'TRUNCATE TABLE sales_order;',
            'TRUNCATE sales_order;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test stored procedure and function execution
     */
    public function testStoredProcedureExecution(): void
    {
        $maliciousQueries = [
            'CALL test_procedure();',
            'EXEC test_procedure;',
            'EXECUTE test_procedure();',
            'SELECT test_function();',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test SET variable operations
     */
    public function testSetVariableOperations(): void
    {
        $maliciousQueries = [
            'SET @var = \'test\';',
            'SET GLOBAL max_connections = 200;',
            'SET SESSION sql_mode = \'STRICT_TRANS_TABLES\';',
            'SET PASSWORD FOR \'user\'@\'localhost\' = PASSWORD(\'newpass\');',
            'SET autocommit = 0;',
            'SET FOREIGN_KEY_CHECKS = 0;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test cross-database access
     */
    public function testCrossDatabaseAccess(): void
    {
        $maliciousQueries = [
            'SELECT * FROM other_database.sales_order;',
            'SELECT * FROM mysql.user;',
            'SELECT * FROM information_schema.tables;',
            'SELECT * FROM performance_schema.events_statements_summary_by_digest;',
            'SELECT * FROM sys.schema_table_statistics;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test INFORMATION_SCHEMA access
     */
    public function testInformationSchemaAccess(): void
    {
        $maliciousQueries = [
            'SELECT * FROM INFORMATION_SCHEMA.TABLES;',
            'SELECT * FROM INFORMATION_SCHEMA.COLUMNS;',
            'SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE;',
            'SELECT * FROM INFORMATION_SCHEMA.TABLE_PRIVILEGES;',
            'SELECT * FROM INFORMATION_SCHEMA.USER_PRIVILEGES;',
            'SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test file operations
     */
    public function testFileOperations(): void
    {
        $maliciousQueries = [
            'SELECT * FROM sales_order INTO OUTFILE \'/tmp/export.csv\';',
            'SELECT * FROM sales_order INTO DUMPFILE \'/tmp/export.bin\';',
            'LOAD DATA INFILE \'/tmp/data.csv\' INTO TABLE sales_order;',
            'LOAD FILE \'/tmp/data.csv\';',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test transaction control
     */
    public function testTransactionControl(): void
    {
        $maliciousQueries = [
            'COMMIT;',
            'ROLLBACK;',
            'START TRANSACTION;',
            'BEGIN;',
            'LOCK TABLE sales_order WRITE;',
            'UNLOCK TABLES;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test permission operations
     */
    public function testPermissionOperations(): void
    {
        $maliciousQueries = [
            'GRANT SELECT ON magento.* TO \'user\'@\'localhost\';',
            'REVOKE SELECT ON magento.* FROM \'user\'@\'localhost\';',
            'FLUSH PRIVILEGES;',
            'FLUSH TABLES;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test system operations
     */
    public function testSystemOperations(): void
    {
        $maliciousQueries = [
            'SHOW PROCESSLIST;',
            'KILL 123;',
            'SHOW VARIABLES;',
            'SHOW STATUS;',
            'SHOW TABLES FROM other_database;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test ALTER DATABASE operations
     */
    public function testAlterDatabaseOperations(): void
    {
        $maliciousQueries = [
            'ALTER DATABASE magento CHARACTER SET utf8mb4;',
            'ALTER DATABASE magento COLLATE utf8mb4_unicode_ci;',
            'CREATE DATABASE test_db;',
            'DROP DATABASE test_db;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test ALTER VIEW operations
     */
    public function testAlterViewOperations(): void
    {
        $maliciousQueries = [
            'ALTER VIEW test_view AS SELECT * FROM sales_order;',
            'CREATE VIEW test_view AS SELECT * FROM sales_order;',
            'DROP VIEW test_view;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test ALTER FUNCTION/PROCEDURE/TRIGGER operations
     */
    public function testAlterRoutineOperations(): void
    {
        $maliciousQueries = [
            'ALTER FUNCTION test() RETURNS INT BEGIN RETURN 1; END;',
            'ALTER PROCEDURE test() BEGIN SELECT 1; END;',
            'ALTER TRIGGER test_trigger BEFORE INSERT ON sales_order FOR EACH ROW BEGIN END;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test RENAME operations
     */
    public function testRenameOperations(): void
    {
        $maliciousQueries = [
            'RENAME TABLE sales_order TO old_orders;',
            'ALTER TABLE sales_order RENAME TO new_orders;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test nested/subquery bypass attempts
     */
    public function testNestedSubqueryBypassAttempts(): void
    {
        $maliciousQueries = [
            'SELECT * FROM (SELECT * FROM sales_order) AS t; ALTER TABLE sales_order;',
            'SELECT * FROM sales_order WHERE id IN (SELECT id FROM orders); DROP TABLE orders;',
        ];

        foreach ($maliciousQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test valid SELECT queries should pass
     */
    public function testValidSelectQueriesPass(): void
    {
        $validQueries = [
            'SELECT * FROM sales_order',
            'SELECT o.*, c.email FROM sales_order o JOIN customer_entity c ON c.entity_id = o.customer_id',
            'SELECT COUNT(*) FROM sales_order WHERE status = \'complete\'',
            'SELECT * FROM sales_order ORDER BY created_at DESC LIMIT 100',
            'SELECT o.increment_id, o.grand_total, c.firstname, c.lastname FROM sales_order o JOIN sales_order_address c ON c.parent_id = o.entity_id WHERE o.status = \'complete\'',
            'WITH RECURSIVE cte AS (SELECT 1 AS n UNION ALL SELECT n + 1 FROM cte WHERE n < 10) SELECT * FROM cte',
            'SELECT * FROM sales_order WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
        ];

        foreach ($validQueries as $query) {
            try {
                $result = $this->validator->validate($query);
                $this->assertTrue($result, "Valid query should pass: $query");
            } catch (LocalizedException $e) {
                $this->fail("Valid query was incorrectly blocked: $query - " . $e->getMessage());
            }
        }
    }

    /**
     * Test queries that don't start with SELECT or WITH are blocked
     */
    public function testQueriesMustStartWithSelectOrWith(): void
    {
        $invalidQueries = [
            'SHOW TABLES',
            'DESCRIBE sales_order',
            'EXPLAIN SELECT * FROM sales_order',
        ];

        foreach ($invalidQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test empty queries are blocked
     */
    public function testEmptyQueriesAreBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('cannot be empty');
        $this->validator->validate('');
    }

    /**
     * Test whitespace-only queries are blocked
     */
    public function testWhitespaceOnlyQueriesAreBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('cannot be empty');
        $this->validator->validate('   ');
    }
}
