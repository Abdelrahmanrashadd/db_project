-- Email List Subscription Project (Final edited schema, MariaDB/MySQL compatible)
-- Save as: email_subscription_schema.sql
-- Notes:
--  * Triggers use EXISTS checks (no DECLARE) for MariaDB compatibility.
--  * Departments created first (without head), employees next, then departments altered to add head fk.
--  * All FKs use ON DELETE SET NULL where appropriate to avoid accidental cascade deletes on important data.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS email_subscription_audit;
DROP TABLE IF EXISTS email_subscriptions;
DROP TABLE IF EXISTS email_imports;
DROP TABLE IF EXISTS department_supervisors;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS departments;

-- Also drop triggers if they exist (safe to run).
DROP TRIGGER IF EXISTS trg_check_head_employee_insert;
DROP TRIGGER IF EXISTS trg_check_head_employee_update;
DROP TRIGGER IF EXISTS trg_email_subscriptions_insert;
DROP TRIGGER IF EXISTS trg_email_subscriptions_update;
DROP TRIGGER IF EXISTS trg_email_subscriptions_delete;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------
-- 1) Departments table (initial, without FK to employees yet)
-- -----------------------
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------
-- 2) Employees table (references departments)
-- -----------------------
CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    job_title VARCHAR(100) NOT NULL,
    department_id INT NOT NULL,
    is_supervisor TINYINT(1) DEFAULT 0,
    hire_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_emp_dept FOREIGN KEY (department_id) REFERENCES departments(department_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------
-- 3) Department supervisors mapping table
-- -----------------------
CREATE TABLE department_supervisors (
    department_id INT NOT NULL,
    employee_id INT NOT NULL,
    appointed_at DATE DEFAULT CURRENT_DATE,
    PRIMARY KEY (department_id, employee_id),
    CONSTRAINT fk_ds_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ds_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------
-- 4) Add head_employee_id to departments and FK -> employees (single source of truth for department head)
-- -----------------------
ALTER TABLE departments
  ADD COLUMN head_employee_id INT NULL AFTER description,
  ADD INDEX idx_head_employee (head_employee_id);

ALTER TABLE departments
  ADD CONSTRAINT fk_dept_head FOREIGN KEY (head_employee_id)
    REFERENCES employees(employee_id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- -----------------------
-- 5) Triggers to ensure head belongs to the same department (MariaDB-friendly)
-- -----------------------
DELIMITER $$
CREATE TRIGGER trg_check_head_employee_insert
BEFORE INSERT ON departments
FOR EACH ROW
BEGIN
  IF NEW.head_employee_id IS NOT NULL
     AND NOT EXISTS (
       SELECT 1 FROM employees
       WHERE employee_id = NEW.head_employee_id
         AND department_id = NEW.department_id
     ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Head employee must belong to the same department';
  END IF;
END$$

CREATE TRIGGER trg_check_head_employee_update
BEFORE UPDATE ON departments
FOR EACH ROW
BEGIN
  IF NEW.head_employee_id IS NOT NULL
     AND NOT EXISTS (
       SELECT 1 FROM employees
       WHERE employee_id = NEW.head_employee_id
         AND department_id = NEW.department_id
     ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Head employee must belong to the same department';
  END IF;
END$$
DELIMITER ;

-- -----------------------
-- 6) Email imports table (records bulk imports and who imported)
-- -----------------------
CREATE TABLE email_imports (
    import_id INT AUTO_INCREMENT PRIMARY KEY,
    imported_by_employee_id INT NULL,
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_name VARCHAR(255),
    row_count INT DEFAULT 0,
    success_count INT DEFAULT 0,
    fail_count INT DEFAULT 0,
    INDEX idx_imported_by (imported_by_employee_id),
    CONSTRAINT fk_imports_importer FOREIGN KEY (imported_by_employee_id) REFERENCES employees(employee_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------
-- 7) Email subscriptions (subscribers list) with audit/ownership fields
-- -----------------------
CREATE TABLE email_subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'unsubscribed', 'bounced') DEFAULT 'active',
    source VARCHAR(50) DEFAULT 'website',
    ip_address VARCHAR(45),
    user_agent TEXT,
    notes TEXT,
    -- ownership/audit fields (nullable)
    created_by_employee_id INT NULL,
    assigned_employee_id INT NULL,
    last_updated_by_employee_id INT NULL,
    import_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_subscribed_at (subscribed_at),
    INDEX idx_created_by (created_by_employee_id),
    INDEX idx_assigned (assigned_employee_id),
    INDEX idx_last_updated_by (last_updated_by_employee_id),
    INDEX idx_import_id (import_id),
    CONSTRAINT fk_sub_created_by FOREIGN KEY (created_by_employee_id) REFERENCES employees(employee_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sub_assigned FOREIGN KEY (assigned_employee_id) REFERENCES employees(employee_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sub_last_updated_by FOREIGN KEY (last_updated_by_employee_id) REFERENCES employees(employee_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sub_import FOREIGN KEY (import_id) REFERENCES email_imports(import_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------
-- 8) Optional: Audit table for subscription changes (INSERT/UPDATE/DELETE)
-- -----------------------
CREATE TABLE email_subscription_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NULL,
    action ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    changed_by_employee_id INT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    old_email VARCHAR(255),
    new_email VARCHAR(255),
    old_status ENUM('active','unsubscribed','bounced'),
    new_status ENUM('active','unsubscribed','bounced'),
    details TEXT,
    CONSTRAINT fk_audit_subscription FOREIGN KEY (subscription_id) REFERENCES email_subscriptions(subscription_id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_changed_by FOREIGN KEY (changed_by_employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------
-- 9) Triggers to populate audit table for subscription changes
-- -----------------------
DELIMITER $$
CREATE TRIGGER trg_email_subscriptions_insert
AFTER INSERT ON email_subscriptions
FOR EACH ROW
BEGIN
  INSERT INTO email_subscription_audit
    (subscription_id, action, changed_by_employee_id, old_email, new_email, old_status, new_status, details)
  VALUES
    (NEW.subscription_id, 'INSERT', NEW.created_by_employee_id, NULL, NEW.email, NULL, NEW.status, CONCAT('source=', COALESCE(NEW.source,'')));
END$$

CREATE TRIGGER trg_email_subscriptions_update
AFTER UPDATE ON email_subscriptions
FOR EACH ROW
BEGIN
  INSERT INTO email_subscription_audit
    (subscription_id, action, changed_by_employee_id, old_email, new_email, old_status, new_status, details)
  VALUES
    (NEW.subscription_id, 'UPDATE', NEW.last_updated_by_employee_id, OLD.email, NEW.email, OLD.status, NEW.status, CONCAT('updated_at=', NEW.updated_at));
END$$

CREATE TRIGGER trg_email_subscriptions_delete
AFTER DELETE ON email_subscriptions
FOR EACH ROW
BEGIN
  INSERT INTO email_subscription_audit
    (subscription_id, action, changed_by_employee_id, old_email, new_email, old_status, new_status, details)
  VALUES
    (OLD.subscription_id, 'DELETE', NULL, OLD.email, NULL, OLD.status, NULL, 'deleted');
END$$
DELIMITER ;

-- -----------------------
-- 10) Sample seed data
--    Departments -> Employees -> set heads -> supervisors mapping -> imports -> subscriptions
-- -----------------------

-- Departments (heads left null; will be set after employees inserted)
INSERT INTO departments (department_name, description) VALUES
('Marketing', 'Handles all marketing campaigns and brand promotion'),
('Sales', 'Manages customer relationships and sales processes'),
('IT', 'Information Technology department for technical support and development'),
('HR', 'Human Resources department for employee management');

-- Employees (must reference departments inserted above)
-- Marketing (dept 1)
INSERT INTO employees (first_name, last_name, email, phone, job_title, department_id, is_supervisor, hire_date) VALUES
('Sarah', 'Johnson', 'sarah.johnson@company.com', '555-0101', 'Head of Marketing', 1, 1, '2020-01-15'),
('Michael', 'Chen', 'michael.chen@company.com', '555-0102', 'Marketing Supervisor', 1, 1, '2021-03-20'),
('Emily', 'Davis', 'emily.davis@company.com', '555-0103', 'Marketing Specialist', 1, 0, '2022-06-10');

-- Sales (dept 2)
INSERT INTO employees (first_name, last_name, email, phone, job_title, department_id, is_supervisor, hire_date) VALUES
('David', 'Martinez', 'david.martinez@company.com', '555-0201', 'Head of Sales', 2, 1, '2019-05-10'),
('Jessica', 'Brown', 'jessica.brown@company.com', '555-0202', 'Sales Supervisor', 2, 1, '2020-08-15'),
('Robert', 'Taylor', 'robert.taylor@company.com', '555-0203', 'Sales Representative', 2, 0, '2023-01-05');

-- IT (dept 3)
INSERT INTO employees (first_name, last_name, email, phone, job_title, department_id, is_supervisor, hire_date) VALUES
('James', 'Wilson', 'james.wilson@company.com', '555-0301', 'Head of IT', 3, 1, '2018-11-01'),
('Amanda', 'Anderson', 'amanda.anderson@company.com', '555-0302', 'IT Supervisor', 3, 1, '2020-02-14'),
('Christopher', 'Thomas', 'christopher.thomas@company.com', '555-0303', 'Software Developer', 3, 0, '2022-09-12');

-- HR (dept 4)
INSERT INTO employees (first_name, last_name, email, phone, job_title, department_id, is_supervisor, hire_date) VALUES
('Lisa', 'Garcia', 'lisa.garcia@company.com', '555-0401', 'Head of HR', 4, 1, '2019-07-20'),
('Mark', 'Rodriguez', 'mark.rodriguez@company.com', '555-0402', 'HR Supervisor', 4, 1, '2021-04-25'),
('Nicole', 'Lewis', 'nicole.lewis@company.com', '555-0403', 'HR Coordinator', 4, 0, '2023-03-01');

-- Set departments' heads by looking up employee emails
UPDATE departments SET head_employee_id = (
  SELECT e.employee_id FROM employees e WHERE e.email = 'sarah.johnson@company.com'
) WHERE department_name = 'Marketing';

UPDATE departments SET head_employee_id = (
  SELECT e.employee_id FROM employees e WHERE e.email = 'david.martinez@company.com'
) WHERE department_name = 'Sales';

UPDATE departments SET head_employee_id = (
  SELECT e.employee_id FROM employees e WHERE e.email = 'james.wilson@company.com'
) WHERE department_name = 'IT';

UPDATE departments SET head_employee_id = (
  SELECT e.employee_id FROM employees e WHERE e.email = 'lisa.garcia@company.com'
) WHERE department_name = 'HR';

-- Populate department_supervisors from employees marked as supervisors
INSERT INTO department_supervisors (department_id, employee_id, appointed_at)
SELECT e.department_id, e.employee_id, CURDATE()
FROM employees e
WHERE e.is_supervisor = 1;

-- Insert a sample import record (example)
INSERT INTO email_imports (imported_by_employee_id, file_name, row_count, success_count, fail_count)
VALUES (
  (SELECT employee_id FROM employees WHERE email = 'michael.chen@company.com'),
  'leads-2025-12-05.csv', 120, 118, 2
);

-- Insert sample subscriptions (some linked to created_by/import)
INSERT INTO email_subscriptions (email, first_name, last_name, status, source, created_by_employee_id, import_id)
VALUES
('john.doe@example.com', 'John', 'Doe', 'active', 'website', NULL, NULL),
('jane.smith@example.com', 'Jane', 'Smith', 'active', 'website', NULL, NULL),
('alex.jones@example.com', 'Alex', 'Jones', 'active', 'newsletter', NULL, NULL),
('sarah.williams@example.com', 'Sarah', 'Williams', 'active', 'website', NULL, NULL),
('mike.johnson@example.com', 'Mike', 'Johnson', 'active', 'campaign',
  (SELECT employee_id FROM employees WHERE email = 'jessica.brown@company.com'),
  (SELECT import_id FROM email_imports LIMIT 1)
);

-- -----------------------
-- Helpful admin queries (examples)
-- -----------------------
-- Departments without a head:
-- SELECT * FROM departments WHERE head_employee_id IS NULL;

-- Departments without supervisors:
-- SELECT d.department_id, d.department_name
-- FROM departments d
-- LEFT JOIN department_supervisors ds ON ds.department_id = d.department_id
-- WHERE ds.employee_id IS NULL;

-- Count subscribers by status:
-- SELECT status, COUNT(*) FROM email_subscriptions GROUP BY status;
