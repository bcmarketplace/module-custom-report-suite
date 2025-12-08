# SQL Query Security Guardrails

## Overview

The BCMarketplace Custom Report Suite module includes comprehensive SQL query validation to prevent dangerous database operations. All SQL queries are validated both when saving reports and when executing them.

## Security Features

### 1. Query Validation Service

**Location:** `Model/Service/SqlQueryValidator.php`

The `SqlQueryValidator` service validates all SQL queries before execution to ensure:
- Only read-only operations (SELECT queries) are allowed
- No database structure modifications
- No data modifications
- No stored procedures or functions
- No security-related operations

### 2. Validation Points

#### On Save (ResourceModel)
- **Location:** `Model/ResourceModel/CustomReport.php::_beforeSave()`
- Validates SQL when saving a new or updated report
- Prevents dangerous SQL from being stored in the database

#### On Execution (Model)
- **Location:** `Model/CustomReport.php::getGenericReportCollection()`
- Validates SQL before executing the query
- Provides an additional layer of security even if validation was bypassed during save

### 3. Blocked Operations

#### Data Definition Language (DDL)
- `ALTER TABLE` - Modify table structure
- `ALTER DATABASE` - Modify database
- `ALTER VIEW` - Modify views
- `ALTER FUNCTION` - Modify functions
- `ALTER PROCEDURE` - Modify procedures
- `ALTER TRIGGER` - Modify triggers
- `CREATE TABLE` - Create new tables
- `CREATE DATABASE` - Create databases
- `CREATE INDEX` - Create indexes
- `CREATE VIEW` - Create views
- `CREATE FUNCTION` - Create functions
- `CREATE PROCEDURE` - Create stored procedures
- `CREATE TRIGGER` - Create triggers
- `CREATE USER` - Create database users
- `DROP TABLE` - Delete tables
- `DROP DATABASE` - Delete databases
- `DROP INDEX` - Delete indexes
- `DROP VIEW` - Delete views
- `DROP FUNCTION` - Delete functions
- `DROP PROCEDURE` - Delete procedures
- `DROP TRIGGER` - Delete triggers
- `DROP USER` - Delete users
- `TRUNCATE TABLE` - Clear table data
- `TRUNCATE` - Clear data
- `RENAME TABLE` - Rename tables

#### Data Manipulation Language (DML) - Destructive
- `DELETE FROM` - Delete data
- `UPDATE` - Modify data
- `INSERT INTO` - Insert data
- `REPLACE INTO` - Replace data

#### Security and Permissions
- `GRANT` - Grant permissions
- `REVOKE` - Revoke permissions
- `FLUSH` - Flush privileges/cache

#### Transaction Control
- `COMMIT` - Commit transactions
- `ROLLBACK` - Rollback transactions
- `LOCK TABLE` - Lock tables
- `UNLOCK TABLE` - Unlock tables

#### System Operations
- `SHOW PROCESSLIST` - Show running processes
- `KILL` - Kill processes
- `EXEC` / `EXECUTE` - Execute stored procedures
- `CALL` - Call stored procedures

#### File Operations
- `LOAD DATA` - Load data from files
- `LOAD FILE` - Load files
- `INTO OUTFILE` - Export to file
- `INTO DUMPFILE` - Dump to file

#### Other Dangerous Operations
- `SET PASSWORD` - Change passwords
- `SET GLOBAL` - Change global variables
- `SET SESSION` - Change session variables

### 4. Allowed Operations

Only the following operations are allowed:
- `SELECT` - Read-only queries
- `WITH` - Common Table Expressions (CTE) for complex SELECT queries

### 5. Additional Security Checks

#### Query Structure Validation
- Query must start with `SELECT` or `WITH`
- Only single statements allowed (no semicolon-separated multiple statements)
- Comments are stripped before validation

#### Suspicious Pattern Detection
- `WHERE 1=1` - Potential SQL injection pattern
- `OR 1=1` - SQL injection attempt
- `INFORMATION_SCHEMA` - System schema access
- `MYSQL.*` - MySQL system tables
- `PERFORMANCE_SCHEMA.*` - Performance schema access

#### Multiple Statement Prevention
- Detects and blocks multiple SQL statements separated by semicolons
- Prevents batch execution of dangerous operations

### 6. Logging

All blocked queries are logged with:
- The dangerous keyword detected
- A preview of the query (first 100 characters)
- Warning level logging for security monitoring

## Usage Example

```php
// Valid query - will pass validation
$query = "SELECT * FROM sales_order WHERE status = 'complete'";
$validator->validate($query); // Returns true

// Invalid query - will throw exception
$query = "DROP TABLE sales_order";
$validator->validate($query); // Throws LocalizedException
```

## Error Messages

When validation fails, users receive clear error messages:
- `"SQL query contains prohibited operation: [OPERATION]. Only SELECT queries are allowed."`
- `"SQL query must start with SELECT or WITH. Only read-only queries are allowed."`
- `"Multiple SQL statements detected. Only single SELECT queries are allowed."`
- `"SQL query contains suspicious pattern: [PATTERN]"`

## Testing

Comprehensive unit tests are available in:
- `Test/Unit/Model/Service/SqlQueryValidatorTest.php`

Tests cover:
- Valid SELECT queries
- Blocked DDL operations
- Blocked DML operations
- Suspicious patterns
- Multiple statements
- Empty queries

## Best Practices

1. **Always validate before execution** - Validation happens automatically in the model
2. **Monitor logs** - Review security logs for blocked queries
3. **User education** - Inform users that only SELECT queries are allowed
4. **Regular audits** - Review saved queries periodically
5. **Principle of least privilege** - Database user should have read-only access

## Limitations

1. **False Positives**: Some legitimate queries using certain patterns may be blocked
2. **Complex Queries**: Very complex queries with nested subqueries may need review
3. **Database-Specific**: Validation is MySQL/MariaDB focused
4. **Not a Replacement**: This is a guardrail, not a replacement for proper database user permissions

## Recommendations

1. **Database User Permissions**: Use a read-only database user for report execution
2. **Network Security**: Restrict database access to application servers only
3. **Regular Updates**: Keep the validator updated with new threat patterns
4. **Security Monitoring**: Set up alerts for blocked query attempts

## Future Enhancements

Potential improvements:
- Whitelist of allowed table names
- Query complexity limits
- Execution time limits
- Result set size limits
- Query result caching
- Query approval workflow

---

**Security Note**: While these guardrails provide strong protection, they should be used in conjunction with:
- Proper database user permissions (read-only)
- Network security
- Regular security audits
- User access controls (ACL)

