# Security Test Cases for BCMarketplace Custom Report Suite

This document contains comprehensive test cases to ensure the SQL query validator properly blocks malicious queries that could:
- Alter database structure (ALTER TABLE, DROP, CREATE, etc.)
- Modify data (UPDATE, DELETE, INSERT)
- Execute stored procedures or functions
- Access other databases
- Set variables or system settings
- Execute triggers

## Test Categories

### 1. Case Variation Bypass Attempts

These queries attempt to bypass keyword detection using case variations:

```sql
-- Should be BLOCKED
select * from sales_order; alter table sales_order add column test varchar(255);
SeLeCt * FrOm SaLeS_oRdEr; AlTeR tAbLe SaLeS_oRdEr AdD cOlUmN tEsT vArChAr(255);
SELECT * FROM sales_order; ALTER TABLE sales_order ADD COLUMN test VARCHAR(255);
```

### 2. Comment-Based Bypass Attempts

These queries use comments to hide malicious keywords:

```sql
-- Should be BLOCKED
SELECT * FROM sales_order; /* ALTER TABLE sales_order */ DROP TABLE sales_order;
SELECT * FROM sales_order; -- ALTER TABLE sales_order ADD COLUMN test VARCHAR(255)
SELECT * FROM sales_order; /* comment */ ALTER /* comment */ TABLE /* comment */ sales_order;
SELECT * FROM sales_order WHERE 1=1; -- hidden: ALTER TABLE sales_order
SELECT * FROM sales_order /*! ALTER TABLE sales_order ADD COLUMN test VARCHAR(255) */;
```

### 3. Whitespace and Encoding Tricks

```sql
-- Should be BLOCKED
SELECT * FROM sales_order; ALTER    TABLE sales_order ADD COLUMN test VARCHAR(255);
SELECT * FROM sales_order; ALTER
TABLE sales_order ADD COLUMN test VARCHAR(255);
SELECT * FROM sales_order; ALTER\tTABLE sales_order;
SELECT * FROM sales_order; ALTER\nTABLE sales_order;
SELECT * FROM sales_order; ALTER\r\nTABLE sales_order;
```

### 4. String Concatenation Bypass

```sql
-- Should be BLOCKED
SELECT * FROM sales_order; SET @sql = CONCAT('ALTER', ' ', 'TABLE', ' ', 'sales_order');
SELECT * FROM sales_order; PREPARE stmt FROM @sql; EXECUTE stmt;
SELECT * FROM sales_order; SET @cmd = 'ALTER TABLE sales_order'; PREPARE stmt FROM @cmd; EXECUTE stmt;
```

### 5. Nested/Subquery Bypass Attempts

```sql
-- Should be BLOCKED
SELECT * FROM (SELECT * FROM sales_order) AS t; ALTER TABLE sales_order;
SELECT * FROM sales_order WHERE id IN (SELECT id FROM orders); DROP TABLE orders;
```

### 6. Multiple Statement Injection

```sql
-- Should be BLOCKED
SELECT * FROM sales_order; DELETE FROM sales_order WHERE 1=1;
SELECT * FROM sales_order; UPDATE sales_order SET status = 'deleted';
SELECT * FROM sales_order; INSERT INTO sales_order (status) VALUES ('test');
SELECT * FROM sales_order; TRUNCATE TABLE sales_order;
SELECT * FROM sales_order; DROP TABLE IF EXISTS sales_order;
```

### 7. ALTER TABLE Variations

```sql
-- Should be BLOCKED
ALTER TABLE sales_order ADD COLUMN test VARCHAR(255);
ALTER TABLE sales_order DROP COLUMN status;
ALTER TABLE sales_order MODIFY COLUMN status VARCHAR(100);
ALTER TABLE sales_order RENAME COLUMN status TO new_status;
ALTER TABLE sales_order ADD INDEX idx_status (status);
ALTER TABLE sales_order DROP INDEX idx_status;
ALTER TABLE sales_order ENGINE=InnoDB;
ALTER TABLE sales_order CHARACTER SET utf8mb4;
```

### 8. DROP Operations

```sql
-- Should be BLOCKED
DROP TABLE sales_order;
DROP TABLE IF EXISTS sales_order;
DROP DATABASE magento;
DROP INDEX idx_status ON sales_order;
DROP VIEW sales_view;
DROP FUNCTION test_function;
DROP PROCEDURE test_procedure;
DROP TRIGGER test_trigger;
DROP USER 'test'@'localhost';
```

### 9. CREATE Operations

```sql
-- Should be BLOCKED
CREATE TABLE test_table (id INT PRIMARY KEY);
CREATE DATABASE test_db;
CREATE INDEX idx_test ON sales_order (status);
CREATE VIEW test_view AS SELECT * FROM sales_order;
CREATE FUNCTION test() RETURNS INT BEGIN RETURN 1; END;
CREATE PROCEDURE test() BEGIN SELECT 1; END;
CREATE TRIGGER test_trigger BEFORE INSERT ON sales_order FOR EACH ROW BEGIN END;
CREATE USER 'test'@'localhost' IDENTIFIED BY 'password';
```

### 10. UPDATE/DELETE/INSERT Operations

```sql
-- Should be BLOCKED
UPDATE sales_order SET status = 'complete' WHERE 1=1;
DELETE FROM sales_order WHERE 1=1;
INSERT INTO sales_order (status) VALUES ('test');
REPLACE INTO sales_order (status) VALUES ('test');
INSERT IGNORE INTO sales_order (status) VALUES ('test');
```

### 11. TRUNCATE Operations

```sql
-- Should be BLOCKED
TRUNCATE TABLE sales_order;
TRUNCATE sales_order;
```

### 12. Stored Procedure and Function Execution

```sql
-- Should be BLOCKED
CALL test_procedure();
EXEC test_procedure;
EXECUTE test_procedure();
SELECT test_function();
```

### 13. SET Variable Operations

```sql
-- Should be BLOCKED
SET @var = 'test';
SET GLOBAL max_connections = 200;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES';
SET PASSWORD FOR 'user'@'localhost' = PASSWORD('newpass');
SET autocommit = 0;
SET FOREIGN_KEY_CHECKS = 0;
```

### 14. Cross-Database Access

```sql
-- Should be BLOCKED
SELECT * FROM other_database.sales_order;
SELECT * FROM mysql.user;
SELECT * FROM information_schema.tables;
SELECT * FROM performance_schema.events_statements_summary_by_digest;
SELECT * FROM sys.schema_table_statistics;
```

### 15. INFORMATION_SCHEMA Access

```sql
-- Should be BLOCKED
SELECT * FROM INFORMATION_SCHEMA.TABLES;
SELECT * FROM INFORMATION_SCHEMA.COLUMNS;
SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE;
SELECT * FROM INFORMATION_SCHEMA.TABLE_PRIVILEGES;
SELECT * FROM INFORMATION_SCHEMA.USER_PRIVILEGES;
SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST;
```

### 16. File Operations

```sql
-- Should be BLOCKED
SELECT * FROM sales_order INTO OUTFILE '/tmp/export.csv';
SELECT * FROM sales_order INTO DUMPFILE '/tmp/export.bin';
LOAD DATA INFILE '/tmp/data.csv' INTO TABLE sales_order;
LOAD FILE '/tmp/data.csv';
```

### 17. Transaction Control

```sql
-- Should be BLOCKED
COMMIT;
ROLLBACK;
START TRANSACTION;
BEGIN;
LOCK TABLE sales_order WRITE;
UNLOCK TABLES;
```

### 18. Permission Operations

```sql
-- Should be BLOCKED
GRANT SELECT ON magento.* TO 'user'@'localhost';
REVOKE SELECT ON magento.* FROM 'user'@'localhost';
FLUSH PRIVILEGES;
FLUSH TABLES;
```

### 19. System Operations

```sql
-- Should be BLOCKED
SHOW PROCESSLIST;
KILL 123;
SHOW VARIABLES;
SHOW STATUS;
SHOW TABLES FROM other_database;
```

### 20. UNION-Based Injection Attempts

```sql
-- Should be BLOCKED (if UNION is blocked)
SELECT * FROM sales_order UNION SELECT * FROM mysql.user;
SELECT * FROM sales_order UNION ALL SELECT user, password FROM mysql.user;
```

### 21. Hex/Unicode Encoding Bypass

```sql
-- Should be BLOCKED
SELECT * FROM sales_order; 0x414C544552205441424C45 (hex for "ALTER TABLE")
SELECT * FROM sales_order; CHAR(65,76,84,69,82) (ALTER in CHAR)
```

### 22. Prepared Statement Bypass

```sql
-- Should be BLOCKED
SET @sql = 'ALTER TABLE sales_order ADD COLUMN test VARCHAR(255)';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
```

### 23. Conditional Execution Bypass

```sql
-- Should be BLOCKED
SELECT * FROM sales_order; IF(1=1, ALTER TABLE sales_order, SELECT 1);
SELECT * FROM sales_order WHERE 1=1 AND (SELECT CASE WHEN 1=1 THEN ALTER TABLE sales_order END);
```

### 24. Trigger Execution Attempts

```sql
-- Should be BLOCKED
CREATE TRIGGER test_trigger BEFORE INSERT ON sales_order FOR EACH ROW BEGIN DELETE FROM sales_order; END;
ALTER TRIGGER test_trigger;
DROP TRIGGER test_trigger;
```

### 25. Event Scheduler Bypass

```sql
-- Should be BLOCKED
CREATE EVENT test_event ON SCHEDULE EVERY 1 DAY DO DELETE FROM sales_order;
ALTER EVENT test_event;
DROP EVENT test_event;
```

### 26. RENAME Operations

```sql
-- Should be BLOCKED
RENAME TABLE sales_order TO old_orders;
ALTER TABLE sales_order RENAME TO new_orders;
```

### 27. Partition Operations

```sql
-- Should be BLOCKED
ALTER TABLE sales_order PARTITION BY RANGE(id);
ALTER TABLE sales_order DROP PARTITION p0;
```

### 28. View Operations

```sql
-- Should be BLOCKED
CREATE VIEW test_view AS SELECT * FROM sales_order;
ALTER VIEW test_view AS SELECT * FROM orders;
DROP VIEW test_view;
```

### 29. Database-Level Operations

```sql
-- Should be BLOCKED
ALTER DATABASE magento CHARACTER SET utf8mb4;
ALTER DATABASE magento COLLATE utf8mb4_unicode_ci;
CREATE DATABASE test_db;
DROP DATABASE test_db;
```

### 30. User Management Operations

```sql
-- Should be BLOCKED
CREATE USER 'test'@'localhost' IDENTIFIED BY 'password';
ALTER USER 'test'@'localhost' IDENTIFIED BY 'newpassword';
DROP USER 'test'@'localhost';
RENAME USER 'old'@'localhost' TO 'new'@'localhost';
```

### 31. Complex Nested Bypass Attempts

```sql
-- Should be BLOCKED
SELECT * FROM (SELECT * FROM sales_order) AS t; /* hidden */ ALTER TABLE sales_order;
SELECT * FROM sales_order WHERE id IN (SELECT id FROM orders); DROP TABLE orders; -- hidden
SELECT * FROM sales_order UNION SELECT 'ALTER', 'TABLE', 'sales_order';
```

### 32. Time-Based SQL Injection Patterns

```sql
-- Should be BLOCKED (if detected as suspicious)
SELECT * FROM sales_order WHERE 1=1 AND SLEEP(5);
SELECT * FROM sales_order WHERE 1=1 AND BENCHMARK(1000000, MD5('test'));
```

### 33. Boolean-Based SQL Injection Patterns

```sql
-- Should be BLOCKED (if detected as suspicious)
SELECT * FROM sales_order WHERE 1=1 OR 1=1;
SELECT * FROM sales_order WHERE '1'='1';
SELECT * FROM sales_order WHERE 1=1 AND 1=1;
```

### 34. Error-Based SQL Injection Patterns

```sql
-- Should be BLOCKED (if detected as suspicious)
SELECT * FROM sales_order WHERE 1=1 AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT version()), 0x7e));
SELECT * FROM sales_order WHERE 1=1 AND UPDATEXML(1, CONCAT(0x7e, (SELECT version()), 0x7e), 1);
```

### 35. Subquery with Dangerous Operations

```sql
-- Should be BLOCKED
SELECT * FROM sales_order WHERE id IN (SELECT id FROM orders); ALTER TABLE orders;
SELECT * FROM (SELECT * FROM sales_order) AS t; DROP TABLE sales_order;
```

## Valid Queries (Should PASS)

These queries should be allowed as they are legitimate SELECT queries:

```sql
-- Should PASS
SELECT * FROM sales_order;
SELECT o.*, c.email FROM sales_order o JOIN customer_entity c ON c.entity_id = o.customer_id;
SELECT COUNT(*) FROM sales_order WHERE status = 'complete';
SELECT * FROM sales_order ORDER BY created_at DESC LIMIT 100;
SELECT o.increment_id, o.grand_total, c.firstname, c.lastname 
FROM sales_order o 
JOIN sales_order_address c ON c.parent_id = o.entity_id 
WHERE o.status = 'complete';
WITH RECURSIVE cte AS (SELECT 1 AS n UNION ALL SELECT n + 1 FROM cte WHERE n < 10) SELECT * FROM cte;
SELECT * FROM sales_order WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
SELECT 
    p.sku,
    pev.value AS product_name,
    (SELECT COUNT(*) FROM sales_order_item soi WHERE soi.product_id = p.entity_id) AS order_count
FROM catalog_product_entity p
JOIN catalog_product_entity_varchar pev ON pev.row_id = p.row_id
WHERE pev.attribute_id = 71;
```

## Testing Recommendations

1. **Automated Testing**: Create unit tests that iterate through all test cases above
2. **Integration Testing**: Test queries through the admin UI to ensure validation happens at save time
3. **Performance Testing**: Ensure validation doesn't significantly slow down query saving
4. **Edge Case Testing**: Test with very long queries, queries with special characters, Unicode, etc.
5. **Regression Testing**: Re-run tests after any changes to the validator

## Implementation Notes

The `SqlQueryValidator` should:
- Normalize queries (remove comments, normalize whitespace, uppercase)
- Check for dangerous keywords using word boundaries
- Ensure queries start with SELECT or WITH
- Block multiple statements (semicolon detection)
- Block suspicious patterns (INFORMATION_SCHEMA, etc.)
- Validate before saving AND before execution

## Additional Security Considerations

1. **Database User Permissions**: The database user used by Magento should have minimal permissions (SELECT only)
2. **Query Timeout**: Implement query timeouts to prevent long-running queries
3. **Result Set Limits**: Enforce maximum result set sizes
4. **Audit Logging**: Log all query attempts (both valid and blocked)
5. **Rate Limiting**: Consider rate limiting query execution to prevent abuse
6. **Input Sanitization**: Ensure queries are properly escaped when stored/displayed
