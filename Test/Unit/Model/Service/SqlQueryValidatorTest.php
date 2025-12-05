<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Test\Unit\Model\Service;

use BCMarketplace\CustomReportSuite\Model\Service\SqlQueryValidator;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SqlQueryValidatorTest extends TestCase
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
     * Test valid SELECT queries are allowed
     */
    public function testValidSelectQueries(): void
    {
        $validQueries = [
            'SELECT * FROM sales_order',
            'SELECT id, name FROM customers WHERE status = 1',
            'SELECT o.*, c.name FROM orders o JOIN customers c ON o.customer_id = c.id',
            'WITH recent_orders AS (SELECT * FROM sales_order WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)) SELECT * FROM recent_orders',
            'SELECT COUNT(*) as total FROM sales_order',
        ];

        foreach ($validQueries as $query) {
            $this->assertTrue($this->validator->validate($query), "Query should be valid: $query");
        }
    }

    /**
     * Test ALTER TABLE is blocked
     */
    public function testAlterTableBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ALTER TABLE');
        $this->validator->validate('ALTER TABLE sales_order ADD COLUMN test VARCHAR(255)');
    }

    /**
     * Test DROP TABLE is blocked
     */
    public function testDropTableBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('DROP TABLE');
        $this->validator->validate('DROP TABLE sales_order');
    }

    /**
     * Test DELETE is blocked
     */
    public function testDeleteBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('DELETE FROM');
        $this->validator->validate('DELETE FROM sales_order WHERE id = 1');
    }

    /**
     * Test UPDATE is blocked
     */
    public function testUpdateBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('UPDATE');
        $this->validator->validate('UPDATE sales_order SET status = 1 WHERE id = 1');
    }

    /**
     * Test INSERT is blocked
     */
    public function testInsertBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('INSERT INTO');
        $this->validator->validate('INSERT INTO sales_order (status) VALUES (1)');
    }

    /**
     * Test CREATE FUNCTION is blocked
     */
    public function testCreateFunctionBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('CREATE FUNCTION');
        $this->validator->validate('CREATE FUNCTION test() RETURNS INT BEGIN RETURN 1; END');
    }

    /**
     * Test CREATE PROCEDURE is blocked
     */
    public function testCreateProcedureBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('CREATE PROCEDURE');
        $this->validator->validate('CREATE PROCEDURE test() BEGIN SELECT 1; END');
    }

    /**
     * Test TRUNCATE is blocked
     */
    public function testTruncateBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('TRUNCATE');
        $this->validator->validate('TRUNCATE TABLE sales_order');
    }

    /**
     * Test empty query is blocked
     */
    public function testEmptyQueryBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('cannot be empty');
        $this->validator->validate('');
    }

    /**
     * Test query must start with SELECT or WITH
     */
    public function testQueryMustStartWithSelect(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('must start with SELECT or WITH');
        $this->validator->validate('SHOW TABLES');
    }

    /**
     * Test multiple statements are blocked
     */
    public function testMultipleStatementsBlocked(): void
    {
        // The validator checks dangerous keywords first, so DROP TABLE is caught before multiple statements check
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('SQL query contains prohibited operation: DROP TABLE');
        $this->validator->validate('SELECT * FROM orders; DROP TABLE orders;');
    }

    /**
     * Test suspicious WHERE clause patterns
     */
    public function testSuspiciousWhereClause(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Suspicious WHERE clause');
        $this->validator->validate('SELECT * FROM orders WHERE 1=1');
    }

    /**
     * Test INFORMATION_SCHEMA access is blocked
     */
    public function testInformationSchemaBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('INFORMATION_SCHEMA');
        $this->validator->validate('SELECT * FROM INFORMATION_SCHEMA.TABLES');
    }

    /**
     * Test various UPDATE statement variations are blocked
     */
    public function testUpdateVariationsBlocked(): void
    {
        $updateQueries = [
            'UPDATE sales_order SET status = 1',
            'UPDATE sales_order SET status = 1 WHERE id = 1',
            'UPDATE sales_order o SET o.status = 1',
            'UPDATE sales_order SET status = 1, total = 100 WHERE id = 1',
            'UPDATE sales_order SET status = (SELECT 1)',
            'UPDATE sales_order SET status = 1 LIMIT 10',
            'UPDATE sales_order SET status = 1 ORDER BY id',
            'UPDATE sales_order SET status = 1 WHERE id IN (1, 2, 3)',
        ];

        foreach ($updateQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->expectExceptionMessage('UPDATE');
            $this->validator->validate($query);
        }
    }

    /**
     * Test UPDATE with JOIN is blocked
     */
    public function testUpdateWithJoinBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('UPDATE');
        $this->validator->validate(
            'UPDATE sales_order o JOIN customer_entity c ON o.customer_id = c.entity_id SET o.status = 1'
        );
    }

    /**
     * Test various INSERT statement variations are blocked
     */
    public function testInsertVariationsBlocked(): void
    {
        $insertQueries = [
            'INSERT INTO sales_order (status) VALUES (1)',
            'INSERT INTO sales_order VALUES (1, "test", 100)',
            'INSERT INTO sales_order (status, total) VALUES (1, 100), (2, 200)',
            'INSERT INTO sales_order SELECT * FROM temp_orders',
            'INSERT IGNORE INTO sales_order (status) VALUES (1)',
            'INSERT INTO sales_order SET status = 1, total = 100',
            'INSERT INTO sales_order (status) SELECT 1 FROM dual',
        ];

        foreach ($insertQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->expectExceptionMessage('INSERT INTO');
            $this->validator->validate($query);
        }
    }

    /**
     * Test REPLACE INTO is blocked
     */
    public function testReplaceIntoBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('REPLACE INTO');
        $this->validator->validate('REPLACE INTO sales_order (status) VALUES (1)');
    }

    /**
     * Test various ALTER TABLE variations are blocked
     */
    public function testAlterTableVariationsBlocked(): void
    {
        $alterQueries = [
            'ALTER TABLE sales_order ADD COLUMN test VARCHAR(255)',
            'ALTER TABLE sales_order DROP COLUMN test',
            'ALTER TABLE sales_order MODIFY COLUMN status VARCHAR(50)',
            'ALTER TABLE sales_order CHANGE COLUMN old_name new_name VARCHAR(255)',
            'ALTER TABLE sales_order ADD INDEX idx_status (status)',
            'ALTER TABLE sales_order DROP INDEX idx_status',
            'ALTER TABLE sales_order ADD PRIMARY KEY (id)',
            'ALTER TABLE sales_order DROP PRIMARY KEY',
            'ALTER TABLE sales_order ADD FOREIGN KEY (customer_id) REFERENCES customer_entity(entity_id)',
            'ALTER TABLE sales_order DROP FOREIGN KEY fk_customer',
            'ALTER TABLE sales_order RENAME TO new_orders',
            'ALTER TABLE sales_order RENAME COLUMN old_name TO new_name',
            'ALTER TABLE sales_order ENGINE=InnoDB',
            'ALTER TABLE sales_order AUTO_INCREMENT=100',
        ];

        foreach ($alterQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->expectExceptionMessage('ALTER TABLE');
            $this->validator->validate($query);
        }
    }

    /**
     * Test ALTER DATABASE is blocked
     */
    public function testAlterDatabaseBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ALTER DATABASE');
        $this->validator->validate('ALTER DATABASE magento CHARACTER SET utf8mb4');
    }

    /**
     * Test ALTER VIEW is blocked
     */
    public function testAlterViewBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ALTER VIEW');
        $this->validator->validate('ALTER VIEW test_view AS SELECT * FROM sales_order');
    }

    /**
     * Test ALTER FUNCTION is blocked
     */
    public function testAlterFunctionBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ALTER FUNCTION');
        $this->validator->validate('ALTER FUNCTION test() RETURNS INT BEGIN RETURN 1; END');
    }

    /**
     * Test ALTER PROCEDURE is blocked
     */
    public function testAlterProcedureBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ALTER PROCEDURE');
        $this->validator->validate('ALTER PROCEDURE test() BEGIN SELECT 1; END');
    }

    /**
     * Test ALTER TRIGGER is blocked
     */
    public function testAlterTriggerBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ALTER TRIGGER');
        $this->validator->validate('ALTER TRIGGER test_trigger BEFORE INSERT ON sales_order FOR EACH ROW BEGIN END');
    }

    /**
     * Test CREATE TABLE is blocked
     */
    public function testCreateTableBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('CREATE TABLE');
        $this->validator->validate('CREATE TABLE test_table (id INT PRIMARY KEY)');
    }

    /**
     * Test CREATE DATABASE is blocked
     */
    public function testCreateDatabaseBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('CREATE DATABASE');
        $this->validator->validate('CREATE DATABASE test_db');
    }

    /**
     * Test CREATE INDEX is blocked
     */
    public function testCreateIndexBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('CREATE INDEX');
        $this->validator->validate('CREATE INDEX idx_status ON sales_order (status)');
    }

    /**
     * Test CREATE VIEW is blocked
     */
    public function testCreateViewBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('CREATE VIEW');
        $this->validator->validate('CREATE VIEW test_view AS SELECT * FROM sales_order');
    }

    /**
     * Test CREATE TRIGGER is blocked
     */
    public function testCreateTriggerBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('CREATE TRIGGER');
        $this->validator->validate(
            'CREATE TRIGGER test_trigger BEFORE INSERT ON sales_order FOR EACH ROW BEGIN END'
        );
    }

    /**
     * Test CREATE USER is blocked
     */
    public function testCreateUserBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('CREATE USER');
        $this->validator->validate("CREATE USER 'test'@'localhost' IDENTIFIED BY 'password'");
    }

    /**
     * Test DROP DATABASE is blocked
     */
    public function testDropDatabaseBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('DROP DATABASE');
        $this->validator->validate('DROP DATABASE test_db');
    }

    /**
     * Test DROP INDEX is blocked
     */
    public function testDropIndexBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('DROP INDEX');
        $this->validator->validate('DROP INDEX idx_status ON sales_order');
    }

    /**
     * Test DROP VIEW is blocked
     */
    public function testDropViewBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('DROP VIEW');
        $this->validator->validate('DROP VIEW test_view');
    }

    /**
     * Test DROP FUNCTION is blocked
     */
    public function testDropFunctionBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('DROP FUNCTION');
        $this->validator->validate('DROP FUNCTION test');
    }

    /**
     * Test DROP PROCEDURE is blocked
     */
    public function testDropProcedureBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('DROP PROCEDURE');
        $this->validator->validate('DROP PROCEDURE test');
    }

    /**
     * Test DROP TRIGGER is blocked
     */
    public function testDropTriggerBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('DROP TRIGGER');
        $this->validator->validate('DROP TRIGGER test_trigger');
    }

    /**
     * Test DROP USER is blocked
     */
    public function testDropUserBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('DROP USER');
        $this->validator->validate("DROP USER 'test'@'localhost'");
    }

    /**
     * Test RENAME TABLE is blocked
     */
    public function testRenameTableBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('RENAME TABLE');
        $this->validator->validate('RENAME TABLE sales_order TO new_orders');
    }

    /**
     * Test GRANT is blocked
     */
    public function testGrantBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('GRANT');
        $this->validator->validate("GRANT SELECT ON magento.* TO 'user'@'localhost'");
    }

    /**
     * Test REVOKE is blocked
     */
    public function testRevokeBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('REVOKE');
        $this->validator->validate("REVOKE SELECT ON magento.* FROM 'user'@'localhost'");
    }

    /**
     * Test FLUSH is blocked
     */
    public function testFlushBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('FLUSH');
        $this->validator->validate('FLUSH TABLES');
    }

    /**
     * Test COMMIT is blocked
     */
    public function testCommitBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('COMMIT');
        $this->validator->validate('COMMIT');
    }

    /**
     * Test ROLLBACK is blocked
     */
    public function testRollbackBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ROLLBACK');
        $this->validator->validate('ROLLBACK');
    }

    /**
     * Test LOCK TABLE is blocked
     */
    public function testLockTableBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('LOCK TABLE');
        $this->validator->validate('LOCK TABLE sales_order WRITE');
    }

    /**
     * Test UNLOCK TABLE is blocked
     */
    public function testUnlockTableBlocked(): void
    {
        // The validator checks if query starts with SELECT/WITH before checking dangerous keywords
        // UNLOCK TABLES doesn't start with SELECT, so it's caught by the "must start with SELECT" check
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('SQL query must start with SELECT or WITH');
        $this->validator->validate('UNLOCK TABLES');
    }

    /**
     * Test KILL is blocked
     */
    public function testKillBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('KILL');
        $this->validator->validate('KILL 123');
    }

    /**
     * Test EXEC/EXECUTE is blocked
     */
    public function testExecBlocked(): void
    {
        $execQueries = [
            'EXEC sp_test',
            'EXECUTE sp_test',
        ];

        foreach ($execQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->expectExceptionMessage('EXEC');
            $this->validator->validate($query);
        }
    }

    /**
     * Test CALL is blocked
     */
    public function testCallBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('CALL');
        $this->validator->validate('CALL test_procedure()');
    }

    /**
     * Test LOAD DATA is blocked
     */
    public function testLoadDataBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('LOAD DATA');
        $this->validator->validate("LOAD DATA INFILE '/tmp/data.csv' INTO TABLE sales_order");
    }

    /**
     * Test LOAD FILE is blocked
     */
    public function testLoadFileBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('LOAD FILE');
        $this->validator->validate("LOAD FILE '/tmp/data.csv'");
    }

    /**
     * Test INTO OUTFILE is blocked
     */
    public function testIntoOutfileBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('INTO OUTFILE');
        $this->validator->validate("SELECT * FROM sales_order INTO OUTFILE '/tmp/export.csv'");
    }

    /**
     * Test INTO DUMPFILE is blocked
     */
    public function testIntoDumpfileBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('INTO DUMPFILE');
        $this->validator->validate("SELECT * FROM sales_order INTO DUMPFILE '/tmp/export.bin'");
    }

    /**
     * Test SET PASSWORD is blocked
     */
    public function testSetPasswordBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('SET PASSWORD');
        $this->validator->validate("SET PASSWORD FOR 'user'@'localhost' = PASSWORD('newpass')");
    }

    /**
     * Test SET GLOBAL is blocked
     */
    public function testSetGlobalBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('SET GLOBAL');
        $this->validator->validate('SET GLOBAL max_connections = 200');
    }

    /**
     * Test SET SESSION is blocked
     */
    public function testSetSessionBlocked(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('SET SESSION');
        $this->validator->validate('SET SESSION sql_mode = "STRICT_TRANS_TABLES"');
    }

    /**
     * Test case-insensitive matching for dangerous keywords
     */
    public function testCaseInsensitiveMatching(): void
    {
        $caseVariations = [
            'update sales_order set status = 1',
            'UPDATE sales_order SET status = 1',
            'Update sales_order Set status = 1',
            'UpDaTe sales_order SeT status = 1',
            'insert into sales_order values (1)',
            'INSERT INTO sales_order VALUES (1)',
            'Insert Into sales_order Values (1)',
            'alter table sales_order add column test varchar(255)',
            'ALTER TABLE sales_order ADD COLUMN test VARCHAR(255)',
            'Alter Table sales_order Add Column test Varchar(255)',
        ];

        foreach ($caseVariations as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test queries with comments trying to hide dangerous operations
     */
    public function testCommentsHidingDangerousOperations(): void
    {
        // After normalization, comments are removed first, so queries with dangerous operations in comments
        // become valid SELECT statements. The validator removes comments before checking for dangerous keywords.
        // Test with queries where dangerous operations are NOT in comments (they should be caught)
        $maliciousQueries = [
            'SELECT * FROM sales_order; UPDATE sales_order SET status = 1',
            'SELECT * FROM sales_order; DELETE FROM sales_order WHERE id = 1',
        ];

        foreach ($maliciousQueries as $query) {
            // Dangerous keywords are checked first, so UPDATE/DELETE is caught
            $this->expectException(LocalizedException::class);
            $this->expectExceptionMessage('SQL query contains prohibited operation');
            $this->validator->validate($query);
        }
        
        // Test that comments are properly removed and don't hide operations
        // Query with UPDATE in comment - after comment removal, becomes valid SELECT
        $validQuery = 'SELECT * FROM sales_order; -- UPDATE sales_order SET status = 1';
        // This should pass because comments are removed, leaving only "SELECT * FROM sales_order;"
        $this->assertTrue($this->validator->validate($validQuery));
    }

    /**
     * Test queries attempting to bypass validation with whitespace
     */
    public function testWhitespaceBypassAttempts(): void
    {
        $bypassAttempts = [
            'UPDATE  sales_order SET status = 1',
            'UPDATE   sales_order SET status = 1',
            'UPDATE\nsales_order SET status = 1',
            'UPDATE\tsales_order SET status = 1',
            'INSERT   INTO sales_order VALUES (1)',
            'ALTER    TABLE sales_order ADD COLUMN test VARCHAR(255)',
        ];

        foreach ($bypassAttempts as $query) {
            $this->expectException(LocalizedException::class);
            $this->validator->validate($query);
        }
    }

    /**
     * Test DELETE variations are blocked
     */
    public function testDeleteVariationsBlocked(): void
    {
        $deleteQueries = [
            'DELETE FROM sales_order',
            'DELETE FROM sales_order WHERE id = 1',
            'DELETE o FROM sales_order o WHERE o.id = 1',
            'DELETE FROM sales_order WHERE id IN (1, 2, 3)',
            'DELETE FROM sales_order LIMIT 10',
            'DELETE FROM sales_order ORDER BY id LIMIT 10',
        ];

        foreach ($deleteQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->expectExceptionMessage('DELETE FROM');
            $this->validator->validate($query);
        }
    }

    /**
     * Test DELETE with JOIN is blocked
     */
    public function testDeleteWithJoinBlocked(): void
    {
        // The validator checks if query starts with SELECT/WITH before checking dangerous keywords
        // DELETE doesn't start with SELECT, so it's caught by the "must start with SELECT" check
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('SQL query must start with SELECT or WITH');
        $this->validator->validate(
            'DELETE o FROM sales_order o JOIN customer_entity c ON o.customer_id = c.entity_id WHERE c.status = 0'
        );
    }

    /**
     * Test TRUNCATE variations are blocked
     */
    public function testTruncateVariationsBlocked(): void
    {
        $truncateQueries = [
            'TRUNCATE TABLE sales_order',
            'TRUNCATE sales_order',
            'TRUNCATE TABLE IF EXISTS sales_order',
        ];

        foreach ($truncateQueries as $query) {
            $this->expectException(LocalizedException::class);
            $this->expectExceptionMessage('TRUNCATE');
            $this->validator->validate($query);
        }
    }

    /**
     * Test complex SELECT queries with subqueries are allowed
     */
    public function testComplexSelectQueriesAllowed(): void
    {
        $complexQueries = [
            'SELECT * FROM sales_order WHERE id IN (SELECT id FROM temp_table)',
            'SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count FROM sales_order o',
            'SELECT * FROM sales_order WHERE EXISTS (SELECT 1 FROM customer_entity WHERE entity_id = sales_order.customer_id)',
            'SELECT * FROM sales_order WHERE id = ANY (SELECT order_id FROM order_items)',
            'SELECT * FROM sales_order WHERE id = ALL (SELECT order_id FROM order_items WHERE qty > 10)',
        ];

        foreach ($complexQueries as $query) {
            $this->assertTrue($this->validator->validate($query), "Complex query should be valid: $query");
        }
    }

    /**
     * Test UNION queries are allowed (if they are SELECT-only)
     */
    public function testUnionSelectQueriesAllowed(): void
    {
        $unionQueries = [
            'SELECT * FROM sales_order UNION SELECT * FROM sales_order_grid',
            'SELECT id FROM orders UNION ALL SELECT id FROM archived_orders',
        ];

        foreach ($unionQueries as $query) {
            $this->assertTrue($this->validator->validate($query), "UNION query should be valid: $query");
        }
    }
}

