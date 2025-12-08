# SQL Test Queries for BCMarketplace Custom Report Suite

This document contains SQL queries for testing the Custom Report Suite module. All queries are SELECT-only and have been validated to work with the SQL validator.

**Important**: Column aliases use underscores (e.g., `order_number`) instead of spaces (e.g., `'Order Number'`) to ensure proper sorting functionality. The Grid will automatically format these for display.

## Table of Contents
1. [Simple Queries](#simple-queries)
2. [Intermediate Queries](#intermediate-queries)
3. [Complex Queries](#complex-queries)
4. [Performance Test Queries](#performance-test-queries)
5. [Edge Case Queries](#edge-case-queries)

---

## Simple Queries

### 1. Basic Order Count
```sql
SELECT COUNT(*) AS total_orders
FROM sales_order
WHERE status = 'complete'
```

### 2. Recent Orders
```sql
SELECT 
    increment_id AS order_number,
    created_at AS order_date,
    grand_total AS total,
    status AS status
FROM sales_order
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY created_at DESC
LIMIT 100
```

### 3. Customer List
```sql
SELECT 
    entity_id AS customer_id,
    email AS email,
    firstname AS first_name,
    lastname AS last_name,
    created_at AS created_date
FROM customer_entity
ORDER BY created_at DESC
```

### 4. Product Count by Status
```sql
SELECT 
    status AS product_status,
    COUNT(*) AS count
FROM catalog_product_entity
GROUP BY status
ORDER BY COUNT(*) DESC
```

### 5. Sales by Status
```sql
SELECT 
    status AS order_status,
    COUNT(*) AS order_count,
    SUM(grand_total) AS total_revenue
FROM sales_order
GROUP BY status
ORDER BY COUNT(*) DESC
```

---

## Intermediate Queries

### 6. Orders with Customer Information
```sql
SELECT 
    o.increment_id AS order_number,
    o.created_at AS order_date,
    o.grand_total AS total,
    o.status AS status,
    CONCAT(c.firstname, ' ', c.lastname) AS customer_name,
    c.email AS customer_email
FROM sales_order o
INNER JOIN customer_entity c ON o.customer_id = c.entity_id
WHERE o.status = 'complete'
ORDER BY o.created_at DESC
LIMIT 500
```

### 7. Top Selling Products
```sql
SELECT 
    p.sku AS sku,
    p.name AS product_name,
    SUM(oi.qty_ordered) AS total_quantity_sold,
    SUM(oi.row_total) AS total_revenue
FROM sales_order_item oi
INNER JOIN catalog_product_entity_varchar p ON oi.product_id = p.entity_id 
    AND p.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)
WHERE oi.parent_item_id IS NULL
GROUP BY p.sku, p.name
ORDER BY SUM(oi.qty_ordered) DESC
LIMIT 50
```

### 8. Monthly Sales Summary
```sql
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') AS month,
    COUNT(*) AS order_count,
    SUM(grand_total) AS total_revenue,
    AVG(grand_total) AS average_order_value
FROM sales_order
WHERE status = 'complete'
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY DATE_FORMAT(created_at, '%Y-%m') DESC
LIMIT 12
```

### 9. Customer Lifetime Value
```sql
SELECT 
    c.entity_id AS customer_id,
    c.email AS email,
    CONCAT(c.firstname, ' ', c.lastname) AS customer_name,
    COUNT(o.entity_id) AS total_orders,
    SUM(o.grand_total) AS lifetime_value,
    MAX(o.created_at) AS last_order_date
FROM customer_entity c
LEFT JOIN sales_order o ON c.entity_id = o.customer_id AND o.status = 'complete'
GROUP BY c.entity_id, c.email, c.firstname, c.lastname
HAVING COUNT(o.entity_id) > 0
ORDER BY SUM(o.grand_total) DESC
LIMIT 100
```

### 10. Products with Low Stock
```sql
SELECT 
    p.sku AS sku,
    p.name AS product_name,
    s.qty AS current_stock,
    s.min_qty AS minimum_qty,
    CASE 
        WHEN s.qty <= s.min_qty THEN 'Low Stock'
        WHEN s.qty <= (s.min_qty * 2) THEN 'Warning'
        ELSE 'In Stock'
    END AS stock_status
FROM cataloginventory_stock_item s
INNER JOIN catalog_product_entity_varchar p ON s.product_id = p.entity_id 
    AND p.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)
WHERE s.qty <= (s.min_qty * 2)
ORDER BY s.qty ASC
LIMIT 100
```

---

## Complex Queries

### 11. Sales Report with Multiple Joins and Aggregations
```sql
SELECT 
    DATE_FORMAT(o.created_at, '%Y-%m') AS month,
    cg.customer_group_code AS customer_group,
    COUNT(DISTINCT o.entity_id) AS order_count,
    COUNT(DISTINCT o.customer_id) AS unique_customers,
    SUM(o.grand_total) AS total_revenue,
    AVG(o.grand_total) AS average_order_value,
    SUM(o.subtotal) AS subtotal,
    SUM(o.tax_amount) AS tax,
    SUM(o.shipping_amount) AS shipping,
    SUM(o.discount_amount) AS discounts
FROM sales_order o
INNER JOIN customer_group cg ON o.customer_group_id = cg.customer_group_id
WHERE o.status = 'complete'
    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(o.created_at, '%Y-%m'), cg.customer_group_code
ORDER BY DATE_FORMAT(o.created_at, '%Y-%m') DESC, SUM(o.grand_total) DESC
```

### 12. Product Performance with Categories
```sql
SELECT 
    p.sku AS sku,
    p.name AS product_name,
    c.name AS category,
    COUNT(DISTINCT oi.order_id) AS times_ordered,
    SUM(oi.qty_ordered) AS total_quantity,
    SUM(oi.row_total) AS total_revenue,
    AVG(oi.price) AS average_price,
    MIN(oi.price) AS min_price,
    MAX(oi.price) AS max_price
FROM sales_order_item oi
INNER JOIN catalog_product_entity_varchar p ON oi.product_id = p.entity_id 
    AND p.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)
LEFT JOIN catalog_category_product ccp ON oi.product_id = ccp.product_id
LEFT JOIN catalog_category_entity_varchar c ON ccp.category_id = c.entity_id 
    AND c.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 3)
WHERE oi.parent_item_id IS NULL
GROUP BY p.sku, p.name, c.name
HAVING COUNT(DISTINCT oi.order_id) > 5
ORDER BY SUM(oi.row_total) DESC
LIMIT 100
```

### 13. Customer Segmentation Analysis
```sql
SELECT 
    CASE 
        WHEN total_spent >= 10000 THEN 'VIP'
        WHEN total_spent >= 5000 THEN 'Premium'
        WHEN total_spent >= 1000 THEN 'Regular'
        ELSE 'Basic'
    END AS customer_segment,
    COUNT(*) AS customer_count,
    AVG(total_spent) AS average_spent,
    AVG(order_count) AS average_orders,
    SUM(total_spent) AS segment_revenue
FROM (
    SELECT 
        c.entity_id,
        COUNT(o.entity_id) AS order_count,
        COALESCE(SUM(o.grand_total), 0) AS total_spent
    FROM customer_entity c
    LEFT JOIN sales_order o ON c.entity_id = o.customer_id AND o.status = 'complete'
    GROUP BY c.entity_id
) AS customer_stats
GROUP BY 
    CASE 
        WHEN total_spent >= 10000 THEN 'VIP'
        WHEN total_spent >= 5000 THEN 'Premium'
        WHEN total_spent >= 1000 THEN 'Regular'
        ELSE 'Basic'
    END
ORDER BY AVG(total_spent) DESC
```

### 14. Sales by Region and Payment Method
```sql
SELECT 
    oa.region AS region,
    oa.country_id AS country,
    op.method AS payment_method,
    COUNT(DISTINCT o.entity_id) AS order_count,
    SUM(o.grand_total) AS total_revenue,
    AVG(o.grand_total) AS average_order_value
FROM sales_order o
INNER JOIN sales_order_address oa ON o.entity_id = oa.parent_id AND oa.address_type = 'shipping'
INNER JOIN sales_order_payment op ON o.entity_id = op.parent_id
WHERE o.status = 'complete'
    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY oa.region, oa.country_id, op.method
HAVING COUNT(DISTINCT o.entity_id) > 10
ORDER BY SUM(o.grand_total) DESC
LIMIT 100
```

### 15. Abandoned Cart Analysis
```sql
SELECT 
    q.customer_id AS customer_id,
    c.email AS email,
    q.items_count AS items_in_cart,
    q.items_qty AS total_quantity,
    q.subtotal AS cart_value,
    q.updated_at AS last_updated,
    DATEDIFF(NOW(), q.updated_at) AS days_since_update,
    CASE 
        WHEN DATEDIFF(NOW(), q.updated_at) > 30 THEN 'Very Old'
        WHEN DATEDIFF(NOW(), q.updated_at) > 14 THEN 'Old'
        WHEN DATEDIFF(NOW(), q.updated_at) > 7 THEN 'Recent'
        ELSE 'Active'
    END AS cart_status
FROM quote q
LEFT JOIN customer_entity c ON q.customer_id = c.entity_id
WHERE q.is_active = 1
    AND q.items_count > 0
    AND q.customer_id IS NOT NULL
    AND q.updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY q.subtotal DESC
LIMIT 200
```

### 16. Product Cross-Sell Analysis
```sql
SELECT 
    p1.sku AS product_sku,
    p1.name AS product_name,
    p2.sku AS cross_sell_sku,
    p2.name AS cross_sell_product,
    COUNT(*) AS times_purchased_together,
    SUM(o.grand_total) AS combined_revenue
FROM sales_order_item oi1
INNER JOIN sales_order_item oi2 ON oi1.order_id = oi2.order_id AND oi1.product_id != oi2.product_id
INNER JOIN sales_order o ON oi1.order_id = o.entity_id
INNER JOIN catalog_product_entity_varchar p1 ON oi1.product_id = p1.entity_id 
    AND p1.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)
INNER JOIN catalog_product_entity_varchar p2 ON oi2.product_id = p2.entity_id 
    AND p2.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)
WHERE o.status = 'complete'
    AND oi1.parent_item_id IS NULL
    AND oi2.parent_item_id IS NULL
GROUP BY p1.sku, p1.name, p2.sku, p2.name
HAVING COUNT(*) > 5
ORDER BY COUNT(*) DESC
LIMIT 50
```

### 17. Time-Based Sales Analysis with CTE
```sql
WITH daily_sales AS (
    SELECT 
        DATE(created_at) AS sale_date,
        COUNT(*) AS order_count,
        SUM(grand_total) AS daily_revenue,
        AVG(grand_total) AS avg_order_value
    FROM sales_order
    WHERE status = 'complete'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    GROUP BY DATE(created_at)
),
weekly_summary AS (
    SELECT 
        YEARWEEK(sale_date) AS week_number,
        MIN(sale_date) AS week_start,
        SUM(order_count) AS weekly_orders,
        SUM(daily_revenue) AS weekly_revenue,
        AVG(avg_order_value) AS weekly_avg_order_value
    FROM daily_sales
    GROUP BY YEARWEEK(sale_date)
)
SELECT 
    week_start AS week_start,
    weekly_orders AS orders,
    weekly_revenue AS revenue,
    weekly_avg_order_value AS avg_order_value,
    LAG(weekly_revenue) OVER (ORDER BY week_number) AS previous_week_revenue,
    weekly_revenue - LAG(weekly_revenue) OVER (ORDER BY week_number) AS week_over_week_change,
    ROUND(((weekly_revenue - LAG(weekly_revenue) OVER (ORDER BY week_number)) / LAG(weekly_revenue) OVER (ORDER BY week_number)) * 100, 2) AS wow_change_percent
FROM weekly_summary
ORDER BY week_number DESC
LIMIT 12
```

### 18. Customer Retention Analysis
```sql
SELECT 
    first_order_month AS cohort_month,
    COUNT(DISTINCT customer_id) AS new_customers,
    SUM(CASE WHEN months_since_first = 0 THEN 1 ELSE 0 END) AS month_0_orders,
    SUM(CASE WHEN months_since_first = 1 THEN 1 ELSE 0 END) AS month_1_orders,
    SUM(CASE WHEN months_since_first = 2 THEN 1 ELSE 0 END) AS month_2_orders,
    SUM(CASE WHEN months_since_first = 3 THEN 1 ELSE 0 END) AS month_3_orders,
    ROUND(SUM(CASE WHEN months_since_first = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(SUM(CASE WHEN months_since_first = 0 THEN 1 ELSE 0 END), 0), 2) AS month_1_retention_percent,
    ROUND(SUM(CASE WHEN months_since_first = 2 THEN 1 ELSE 0 END) * 100.0 / NULLIF(SUM(CASE WHEN months_since_first = 0 THEN 1 ELSE 0 END), 0), 2) AS month_2_retention_percent
FROM (
    SELECT 
        o.customer_id,
        DATE_FORMAT(MIN(o.created_at) OVER (PARTITION BY o.customer_id), '%Y-%m') AS first_order_month,
        TIMESTAMPDIFF(MONTH, MIN(o.created_at) OVER (PARTITION BY o.customer_id), o.created_at) AS months_since_first
    FROM sales_order o
    WHERE o.status = 'complete'
        AND o.customer_id IS NOT NULL
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
) AS retention_data
WHERE months_since_first <= 3
GROUP BY first_order_month
ORDER BY first_order_month DESC
LIMIT 12
```

### 19. Product Performance with Inventory Status
```sql
SELECT 
    p.sku AS sku,
    p.name AS product_name,
    CASE 
        WHEN p.type_id = 'simple' THEN 'Simple'
        WHEN p.type_id = 'configurable' THEN 'Configurable'
        WHEN p.type_id = 'bundle' THEN 'Bundle'
        WHEN p.type_id = 'grouped' THEN 'Grouped'
        ELSE p.type_id
    END AS product_type,
    s.qty AS stock_qty,
    s.is_in_stock AS in_stock,
    COUNT(DISTINCT oi.order_id) AS orders,
    SUM(oi.qty_ordered) AS units_sold,
    SUM(oi.row_total) AS revenue,
    AVG(oi.price) AS avg_price,
    CASE 
        WHEN s.qty = 0 AND s.is_in_stock = 0 THEN 'Out of Stock'
        WHEN s.qty <= s.min_qty THEN 'Low Stock'
        WHEN COUNT(DISTINCT oi.order_id) = 0 THEN 'No Sales'
        ELSE 'Active'
    END AS status
FROM catalog_product_entity p
LEFT JOIN catalog_product_entity_varchar pv ON p.entity_id = pv.entity_id 
    AND pv.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)
LEFT JOIN cataloginventory_stock_item s ON p.entity_id = s.product_id
LEFT JOIN sales_order_item oi ON p.entity_id = oi.product_id AND oi.parent_item_id IS NULL
LEFT JOIN sales_order o ON oi.order_id = o.entity_id AND o.status = 'complete'
GROUP BY p.sku, p.name, p.type_id, s.qty, s.is_in_stock
HAVING COUNT(DISTINCT oi.order_id) > 0 OR s.qty IS NOT NULL
ORDER BY SUM(oi.row_total) DESC
LIMIT 200
```

### 20. Multi-Table Join with Aggregations and Window Functions
```sql
SELECT 
    DATE_FORMAT(o.created_at, '%Y-%m') AS month,
    cg.customer_group_code AS customer_group,
    COUNT(DISTINCT o.entity_id) AS orders,
    COUNT(DISTINCT o.customer_id) AS customers,
    SUM(o.grand_total) AS revenue,
    AVG(o.grand_total) AS aov,
    SUM(o.grand_total) / COUNT(DISTINCT o.customer_id) AS revenue_per_customer,
    RANK() OVER (PARTITION BY DATE_FORMAT(o.created_at, '%Y-%m') ORDER BY SUM(o.grand_total) DESC) AS group_rank,
    SUM(o.grand_total) / SUM(SUM(o.grand_total)) OVER (PARTITION BY DATE_FORMAT(o.created_at, '%Y-%m')) * 100 AS market_share_percent
FROM sales_order o
INNER JOIN customer_group cg ON o.customer_group_id = cg.customer_group_id
WHERE o.status = 'complete'
    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(o.created_at, '%Y-%m'), cg.customer_group_code
ORDER BY DATE_FORMAT(o.created_at, '%Y-%m') DESC, SUM(o.grand_total) DESC
```

---

## Performance Test Queries

### 21. Large Dataset Test - All Orders with Details
```sql
SELECT 
    o.increment_id AS order_number,
    o.created_at AS date,
    CONCAT(c.firstname, ' ', c.lastname) AS customer,
    o.grand_total AS total,
    o.status AS status,
    oa.city AS city,
    oa.region AS state,
    op.method AS payment,
    COUNT(oi.item_id) AS items
FROM sales_order o
LEFT JOIN customer_entity c ON o.customer_id = c.entity_id
LEFT JOIN sales_order_address oa ON o.entity_id = oa.parent_id AND oa.address_type = 'shipping'
LEFT JOIN sales_order_payment op ON o.entity_id = op.parent_id
LEFT JOIN sales_order_item oi ON o.entity_id = oi.order_id
WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
GROUP BY o.increment_id, o.created_at, c.firstname, c.lastname, o.grand_total, o.status, oa.city, oa.region, op.method
ORDER BY o.created_at DESC
```

### 22. Complex Aggregation with Multiple Subqueries
```sql
SELECT 
    p.sku AS sku,
    p.name AS product_name,
    (SELECT COUNT(*) FROM sales_order_item WHERE product_id = p.entity_id AND parent_item_id IS NULL) AS times_ordered,
    (SELECT SUM(qty_ordered) FROM sales_order_item WHERE product_id = p.entity_id AND parent_item_id IS NULL) AS total_qty_sold,
    (SELECT SUM(row_total) FROM sales_order_item WHERE product_id = p.entity_id AND parent_item_id IS NULL) AS total_revenue,
    (SELECT AVG(price) FROM sales_order_item WHERE product_id = p.entity_id AND parent_item_id IS NULL) AS avg_price,
    s.qty AS current_stock
FROM catalog_product_entity p
INNER JOIN catalog_product_entity_varchar pv ON p.entity_id = pv.entity_id 
    AND pv.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)
LEFT JOIN cataloginventory_stock_item s ON p.entity_id = s.product_id
WHERE (SELECT COUNT(*) FROM sales_order_item WHERE product_id = p.entity_id) > 0
ORDER BY (SELECT SUM(row_total) FROM sales_order_item WHERE product_id = p.entity_id) DESC
LIMIT 100
```

---

## Edge Case Queries

### 23. Query with NULL Handling
```sql
SELECT 
    COALESCE(c.email, 'Guest') AS customer,
    COUNT(DISTINCT o.entity_id) AS order_count,
    COALESCE(SUM(o.grand_total), 0) AS total_spent,
    COALESCE(AVG(o.grand_total), 0) AS average_order
FROM sales_order o
LEFT JOIN customer_entity c ON o.customer_id = c.entity_id
WHERE o.status = 'complete'
GROUP BY COALESCE(c.email, 'Guest')
ORDER BY COUNT(DISTINCT o.entity_id) DESC
```

### 24. Query with Date Functions
```sql
SELECT 
    DATE(created_at) AS date,
    DAYNAME(created_at) AS day_of_week,
    HOUR(created_at) AS hour,
    COUNT(*) AS order_count,
    SUM(grand_total) AS revenue
FROM sales_order
WHERE status = 'complete'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), DAYNAME(created_at), HOUR(created_at)
ORDER BY DATE(created_at) DESC, HOUR(created_at) ASC
```

### 25. Query with String Functions
```sql
SELECT 
    SUBSTRING_INDEX(email, '@', 1) AS username,
    SUBSTRING_INDEX(email, '@', -1) AS domain,
    COUNT(*) AS customer_count
FROM customer_entity
WHERE email IS NOT NULL
GROUP BY SUBSTRING_INDEX(email, '@', -1)
ORDER BY COUNT(*) DESC
LIMIT 20
```

### 26. Query with CASE Statements
```sql
SELECT 
    CASE 
        WHEN grand_total >= 1000 THEN 'High Value'
        WHEN grand_total >= 500 THEN 'Medium Value'
        WHEN grand_total >= 100 THEN 'Standard Value'
        ELSE 'Low Value'
    END AS order_tier,
    COUNT(*) AS order_count,
    SUM(grand_total) AS total_revenue,
    AVG(grand_total) AS average_value
FROM sales_order
WHERE status = 'complete'
GROUP BY 
    CASE 
        WHEN grand_total >= 1000 THEN 'High Value'
        WHEN grand_total >= 500 THEN 'Medium Value'
        WHEN grand_total >= 100 THEN 'Standard Value'
        ELSE 'Low Value'
    END
ORDER BY SUM(grand_total) DESC
```

### 27. Query with UNION (if supported)
```sql
SELECT 
    'Completed' AS order_type,
    COUNT(*) AS count,
    SUM(grand_total) AS total
FROM sales_order
WHERE status = 'complete'
UNION ALL
SELECT 
    'Pending' AS order_type,
    COUNT(*) AS count,
    SUM(grand_total) AS total
FROM sales_order
WHERE status = 'pending'
UNION ALL
SELECT 
    'Processing' AS order_type,
    COUNT(*) AS count,
    SUM(grand_total) AS total
FROM sales_order
WHERE status = 'processing'
```

---

## Testing Checklist

When testing these queries, verify:

- [ ] Query executes without errors
- [ ] Results display correctly in the admin grid
- [ ] Column headers are properly formatted
- [ ] Sorting works on all columns
- [ ] Filtering works correctly
- [ ] Pagination works for large result sets
- [ ] CSV export works
- [ ] XML export works
- [ ] Query validation blocks dangerous operations
- [ ] Performance is acceptable for large datasets

---

## Notes

1. **All queries are SELECT-only** - They comply with the SQL validator requirements
2. **Column aliases use underscores** - This ensures proper sorting functionality (e.g., `order_number` instead of `'Order Number'`)
3. **Table names** - These queries use standard Magento table names. Adjust if your installation uses table prefixes
4. **Performance** - Complex queries may take longer to execute on large datasets
5. **Indexes** - Ensure proper indexes exist on frequently queried columns for optimal performance
6. **Testing** - Start with simple queries and gradually test more complex ones

---

## Common Magento Table References

- `sales_order` - Order main table
- `sales_order_item` - Order items
- `sales_order_address` - Order addresses
- `sales_order_payment` - Payment information
- `customer_entity` - Customer main table
- `catalog_product_entity` - Product main table
- `catalog_product_entity_varchar` - Product attributes (name, etc.)
- `cataloginventory_stock_item` - Inventory information
- `quote` - Shopping cart quotes
- `customer_group` - Customer groups

---

**Important**: Always test queries in a development environment first before using in production!
