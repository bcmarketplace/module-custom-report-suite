# BCMarketplace Custom Report Suite

**Version:** 2.1.0  
**Compatibility:** Adobe Commerce 2.4.7-p6+ / Magento 2.4.7-p6+  
**PHP:** 8.2+ / 8.3+  
**Author:** Raphael Baako (rbaako@baakoconsultingllc.com)  
**Company:** Baako Consulting LLC

## Overview

BCMarketplace Custom Report Suite is an enterprise-grade module that enables administrators to create custom SQL-based reports directly from the Adobe Commerce admin panel. This module provides a powerful, flexible reporting solution with advanced features including automated exports, scheduled cron jobs, and comprehensive data visualization.

## Features

### Core Functionality
- **Custom SQL Report Builder**: Create reports using custom SQL queries with full admin grid integration
- **Admin Grid Display**: Native Magento admin grid with sorting, filtering, and pagination
- **CSV Export**: Export report data to CSV format
- **Excel XML Export**: Export report data to Excel-compatible XML format
- **Automated Exports**: Schedule automated report exports via cron jobs
- **Dynamic Cron Management**: Automatically create and manage cron jobs for scheduled exports
- **Filename Patterns**: Configurable filename patterns with date/time variables
- **Multi-Report Linking**: Link multiple custom reports to a single automated export

### Technical Features
- **Service Contracts**: Full API implementation following Magento best practices
- **Repository Pattern**: Proper data access layer with repository interfaces
- **Dependency Injection**: Modern DI configuration without ObjectManager anti-patterns
- **Type Safety**: PHP 8.2+ strict typing throughout
- **Performance Optimized**: Efficient collection loading and query optimization
- **Adobe Commerce 2.4.7-p6 Compatible**: Uses only supported APIs and patterns

## Installation

### Via Composer (Recommended)

```bash
composer require bcmarketplace/module-custom-report-suite
bin/magento module:enable BCMarketplace_CustomReportSuite
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual Installation

1. Copy the module to `app/code/BCMarketplace/CustomReportSuite`
2. Run the following commands:

```bash
bin/magento module:enable BCMarketplace_CustomReportSuite
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

## Usage

### Creating a Custom Report

1. Navigate to **Reports > Custom Reports > Custom Reports** in the admin panel
2. Click **Add New Report**
3. Enter a **Report Name**
4. Enter your **SQL Query** (see SQL Query Guidelines below)
5. Click **Save**
6. Click **View Report** to see the results

### SQL Query Guidelines

- **SELECT statements only**: Only SELECT queries are supported
- **No DML operations**: INSERT, UPDATE, DELETE are not allowed
- **Column aliases**: Use meaningful column aliases as they become grid column headers
- **Performance**: Optimize queries for large datasets
- **Security**: Always use parameterized queries when possible (future enhancement)

**Example Query:**
```sql
SELECT 
    o.increment_id AS 'Order Number',
    o.created_at AS 'Order Date',
    o.grand_total AS 'Total',
    CONCAT(c.firstname, ' ', c.lastname) AS 'Customer Name'
FROM sales_order o
INNER JOIN sales_order_address c ON o.entity_id = c.parent_id
WHERE o.status = 'complete'
ORDER BY o.created_at DESC
```

### Automated Exports

1. Navigate to **Reports > Custom Reports > Automated Exports**
2. Click **Add New Automated Export**
3. Configure:
   - **Title**: Name for the automated export
   - **Cron Expression**: Schedule (e.g., `0 2 * * *` for daily at 2 AM)
   - **Export Types**: Select export methods
   - **File Types**: Select file formats (CSV, XML)
   - **Filename Pattern**: Use variables like `%Y%`, `%m%`, `%d%`, `%reportname%`
   - **Export Location**: Directory path for exported files
   - **Custom Reports**: Select reports to include
4. Click **Save**

### Filename Pattern Variables

- `%d%` - Day (01-31)
- `%m%` - Month (01-12)
- `%y%` - Year (2 digits)
- `%Y%` - Year (4 digits)
- `%h%` - Hour (00-23)
- `%i%` - Minute (00-59)
- `%s%` - Second (00-59)
- `%W%` - Week number (01-53)
- `%reportname%` - Report name (lowercase, underscores)

**Example Pattern:** `sales_report_%Y%_%m%_%d%` → `sales_report_2024_01_15.csv`

## Architecture

### Module Structure

```
BCMarketplace/CustomReportSuite/
├── Api/                          # Service contracts and interfaces
│   ├── Data/                      # Data transfer objects
│   ├── CustomReportRepositoryInterface.php
│   ├── AutomatedExportRepositoryInterface.php
│   └── ...
├── Block/                        # View layer blocks
│   └── Adminhtml/
│       └── Report/
├── Controller/                   # Admin controllers
│   └── Adminhtml/
├── Model/                        # Business logic and data models
│   ├── ResourceModel/            # Database access layer
│   ├── Service/                  # Service classes
│   └── ...
├── Registry/                     # Registry pattern implementation
├── Ui/                           # UI component providers
└── etc/                          # Configuration files
```

### Key Components

- **CustomReport**: Main entity model for custom reports
- **AutomatedExport**: Entity model for automated export configurations
- **GenericReportCollection**: Custom collection for dynamic SQL queries
- **CustomReportRepository**: Repository implementation following service contracts
- **CreateDynamicCron**: Service for dynamic cron job creation
- **Cron**: Cron job handler for automated exports

### Design Patterns

- **Repository Pattern**: All data access through repository interfaces
- **Service Contracts**: API-first design with interfaces
- **Factory Pattern**: Proper use of Magento factories
- **Dependency Injection**: Constructor injection throughout
- **Registry Pattern**: For passing context between controllers and blocks

## Security Considerations

⚠️ **IMPORTANT**: This module executes custom SQL queries. Follow these security best practices:

1. **Access Control**: Only grant access to trusted administrators
2. **SQL Injection**: Always validate and sanitize SQL queries
3. **Read-Only**: The module is designed for SELECT queries only
4. **Audit Logging**: Monitor report creation and execution
5. **Database Permissions**: Use database user with minimal required permissions

## Performance Optimization

- Collections use efficient query patterns
- Indexed database columns recommended for large datasets
- Pagination enabled by default
- Consider caching for frequently accessed reports (future enhancement)

## Troubleshooting

### Reports Not Displaying

1. Verify SQL query syntax is correct
2. Check database permissions
3. Review Magento logs: `var/log/system.log`
4. Ensure module is enabled: `bin/magento module:status`

### Cron Jobs Not Running

1. Verify cron is configured: `bin/magento cron:run`
2. Check cron schedule: `SELECT * FROM cron_schedule WHERE job_code LIKE 'automated_export_%'`
3. Review cron logs in admin panel

### Export Files Not Generated

1. Verify export directory permissions
2. Check disk space availability
3. Review file system logs

## Development

### Running Tests

```bash
vendor/bin/phpunit app/code/BCMarketplace/CustomReportSuite/Test/Unit
```

### Code Standards

This module follows:
- **PSR-12**: PHP coding standards
- **Magento Coding Standard**: Magento-specific guidelines
- **PHPStan Level 7+**: Static analysis compliance

### Contributing

Contributions are welcome! Please ensure:
- Code follows PSR-12 and Magento coding standards
- All tests pass
- PHPStan analysis passes
- Documentation is updated

## Changelog

### 2.1.0 (Current)
- Complete refactoring for Adobe Commerce 2.4.7-p6 compatibility
- Re-namespaced from DEG\CustomReports to BCMarketplace\CustomReportSuite
- Removed deprecated code (Zend_Db_Expr, ObjectManager)
- Added strict typing and PHP 8.2+ compatibility
- Performance optimizations
- Enhanced error handling and logging

### 2.0.0 (Original)
- Initial release by DEG Digital

## License

- OSL-3.0
- AFL-3.0

## Support

For issues, questions, or contributions:
- **Email**: rbaako@baakoconsultingllc.com
- **Company**: Baako Consulting LLC

## Acknowledgments

This module was inspired by the Magento 1 Custom Reports extension by Kalen Jordan and contributors. Thank you to the original developers and the Magento community.

---

**Disclaimer**: This module executes custom SQL queries. Use at your own risk and ensure proper security measures are in place. The authors are not responsible for any data loss or security breaches resulting from improper use of this module.
