-- CRUD Operations for Email List Subscription Project
-- This file contains example CRUD operations for all tables

-- ============================================
-- DEPARTMENTS TABLE CRUD OPERATIONS
-- ============================================

-- CREATE: Insert a new department
INSERT INTO departments (department_name, description) 
VALUES ('Operations', 'Handles daily operations and logistics');

-- READ: Select all departments
SELECT * FROM departments;

-- READ: Select a specific department by ID
SELECT * FROM departments WHERE department_id = 1;

-- READ: Select departments with employee count
SELECT d.*, COUNT(e.employee_id) as employee_count
FROM departments d
LEFT JOIN employees e ON d.department_id = e.department_id
GROUP BY d.department_id;

-- UPDATE: Update department information
UPDATE departments 
SET department_name = 'Marketing & Communications', 
    description = 'Handles all marketing, advertising, and communication campaigns'
WHERE department_id = 1;

-- DELETE: Delete a department (only if no employees assigned)
DELETE FROM departments WHERE department_id = 5;

-- ============================================
-- EMPLOYEES TABLE CRUD OPERATIONS
-- ============================================

-- CREATE: Insert a new employee
INSERT INTO employees (first_name, last_name, email, phone, position, department_id, is_supervisor, is_head_of_department, hire_date)
VALUES ('New', 'Employee', 'new.employee@company.com', '555-9999', 'Junior Developer', 3, FALSE, FALSE, '2024-01-15');

-- READ: Select all employees
SELECT * FROM employees;

-- READ: Select employees with department information
SELECT e.*, d.department_name
FROM employees e
JOIN departments d ON e.department_id = d.department_id
ORDER BY e.last_name;

-- READ: Select all supervisors
SELECT * FROM employees WHERE is_supervisor = TRUE;

-- READ: Select heads of departments
SELECT e.*, d.department_name
FROM employees e
JOIN departments d ON e.department_id = d.department_id
WHERE e.is_head_of_department = TRUE;

-- READ: Select employees by department
SELECT e.*, d.department_name
FROM employees e
JOIN departments d ON e.department_id = d.department_id
WHERE d.department_id = 1;

-- UPDATE: Update employee information
UPDATE employees 
SET first_name = 'Updated', 
    phone = '555-8888',
    position = 'Senior Developer'
WHERE employee_id = 9;

-- UPDATE: Promote employee to supervisor
UPDATE employees 
SET is_supervisor = TRUE,
    position = 'Senior Marketing Specialist'
WHERE employee_id = 3;

-- DELETE: Delete an employee
DELETE FROM employees WHERE employee_id = 15;

-- ============================================
-- EMAIL SUBSCRIPTIONS TABLE CRUD OPERATIONS
-- ============================================

-- CREATE: Insert a new email subscription
INSERT INTO email_subscriptions (email, first_name, last_name, status, source, ip_address)
VALUES ('new.subscriber@example.com', 'New', 'Subscriber', 'active', 'website', '192.168.1.1');

-- READ: Select all active subscriptions
SELECT * FROM email_subscriptions WHERE status = 'active';

-- READ: Select all subscriptions (with pagination)
SELECT * FROM email_subscriptions 
ORDER BY subscribed_at DESC 
LIMIT 10 OFFSET 0;

-- READ: Select subscriptions by status
SELECT * FROM email_subscriptions WHERE status = 'unsubscribed';

-- READ: Count subscriptions by status
SELECT status, COUNT(*) as count 
FROM email_subscriptions 
GROUP BY status;

-- READ: Get recent subscriptions
SELECT * FROM email_subscriptions 
WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY subscribed_at DESC;

-- UPDATE: Update subscription status
UPDATE email_subscriptions 
SET status = 'unsubscribed',
    updated_at = CURRENT_TIMESTAMP
WHERE email = 'john.doe@example.com';

-- UPDATE: Update subscriber information
UPDATE email_subscriptions 
SET first_name = 'Updated',
    last_name = 'Name',
    notes = 'Updated information'
WHERE subscription_id = 1;

-- DELETE: Delete a subscription (hard delete)
DELETE FROM email_subscriptions WHERE subscription_id = 1;

-- DELETE: Soft delete (unsubscribe)
UPDATE email_subscriptions 
SET status = 'unsubscribed' 
WHERE email = 'jane.smith@example.com';

-- ============================================
-- ADVANCED QUERIES
-- ============================================

-- Get department statistics
SELECT 
    d.department_name,
    COUNT(DISTINCT e.employee_id) as total_employees,
    COUNT(DISTINCT CASE WHEN e.is_supervisor = TRUE THEN e.employee_id END) as supervisors,
    COUNT(DISTINCT CASE WHEN e.is_head_of_department = TRUE THEN e.employee_id END) as heads
FROM departments d
LEFT JOIN employees e ON d.department_id = e.department_id
GROUP BY d.department_id, d.department_name;

-- Get subscription statistics by source
SELECT 
    source,
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
    COUNT(CASE WHEN status = 'unsubscribed' THEN 1 END) as unsubscribed,
    COUNT(CASE WHEN status = 'bounced' THEN 1 END) as bounced
FROM email_subscriptions
GROUP BY source;

-- Get subscriptions per month
SELECT 
    DATE_FORMAT(subscribed_at, '%Y-%m') as month,
    COUNT(*) as subscriptions
FROM email_subscriptions
GROUP BY DATE_FORMAT(subscribed_at, '%Y-%m')
ORDER BY month DESC;

