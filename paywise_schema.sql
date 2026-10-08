-- PAYWISE
-- Fresh / clean database schema (self-contained: creates the database too)
-- MariaDB 10.4+ / MySQL 8+
--
-- HOW TO USE (phpMyAdmin):
-- 1. Open phpMyAdmin. You do NOT need to create anything first.
-- 2. Click "Import", choose this file, click "Go".
-- 3. Done: a `paywise_payroll` database is created with all tables,
--    the default admin account, departments/positions, and the sample
--    employee portal login below.
--
-- IMPORTANT:
-- Do NOT import this into an existing database unless you intentionally want
-- to replace its existing tables and data (DROP TABLE statements below).
--
-- SAMPLE LOGINS after import:
--   Admin:            username `admin` (password set during original install)
--   Employee portal:  Employee ID `25-0001` / password `Employee@123`
--
-- This schema contains every table the payroll system needs:
-- admins, app_settings, departments, positions, employees (with portal
-- password + photo), attendance, employee_references, payroll_records.

CREATE DATABASE IF NOT EXISTS `paywise_payroll`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `paywise_payroll`;

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS payroll_records;
DROP TABLE IF EXISTS employee_references;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS app_settings;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- ADMINS
-- --------------------------------------------------------

CREATE TABLE admins (
    admin_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('superadmin','finance','hr') NOT NULL DEFAULT 'superadmin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO admins
    (admin_id, username, password_hash, full_name, role, is_active, created_at)
VALUES
    (1, 'admin',
     '$2y$10$cK4PFcHW5dXRAMOZuTb2GeBosLrZWt7WSfV7NACSSFcfZPo.PbWHK',
     'System Administrator',
     'superadmin', 1,
     '2026-08-13 05:25:14');

-- --------------------------------------------------------
-- APP SETTINGS
-- --------------------------------------------------------

CREATE TABLE app_settings (
    setting_key VARCHAR(80) NOT NULL,
    setting_value VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO app_settings (setting_key, setting_value) VALUES
('company_address', ''),
('company_name', 'Paywise'),
('employee_number_digits', '4'),
('employee_number_prefix', '25');

-- --------------------------------------------------------
-- DEPARTMENTS
-- --------------------------------------------------------

CREATE TABLE departments (
    department_id INT NOT NULL AUTO_INCREMENT,
    department_name VARCHAR(150) NOT NULL,
    department_code VARCHAR(30) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (department_id),
    UNIQUE KEY uq_department_name (department_name),
    UNIQUE KEY uq_department_code (department_code),
    KEY idx_department_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO departments
    (department_id, department_name, department_code, description, status, created_at, updated_at)
VALUES
(1, 'Operations', 'OPS',
 'Company operations and field teams', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(2, 'Finance & Accounting', 'FIN',
 'Finance, accounting and payroll', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(3, 'Human Resources', 'HR',
 'People, hiring and personnel records', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(4, 'Information Technology', 'IT',
 'Systems, software and IT support', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(5, 'Sales & Marketing', 'SALES',
 'Sales, marketing and client growth', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(6, 'Customer Support', 'SUPPORT',
 'Customer service and client support', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01');

-- --------------------------------------------------------
-- POSITIONS
-- --------------------------------------------------------

CREATE TABLE positions (
    position_id INT NOT NULL AUTO_INCREMENT,
    department_id INT NOT NULL,
    position_name VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (position_id),
    UNIQUE KEY uq_department_position (department_id, position_name),
    KEY idx_position_department (department_id),
    CONSTRAINT fk_position_department
        FOREIGN KEY (department_id)
        REFERENCES departments (department_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO positions (position_id, department_id, position_name, created_at) VALUES
(1,1,'Operations Manager','2026-09-08 14:56:08'),
(2,1,'Supervisor','2026-09-08 14:56:08'),
(3,1,'Team Lead','2026-09-08 14:56:08'),
(4,1,'Staff','2026-09-08 14:56:08'),
(5,1,'Assistant','2026-09-08 14:56:08'),

(6,2,'Finance Manager','2026-09-08 14:56:08'),
(7,2,'Accountant','2026-09-08 14:56:08'),
(8,2,'Payroll Officer','2026-09-08 14:56:08'),
(9,2,'Staff','2026-09-08 14:56:08'),

(10,3,'HR Manager','2026-09-08 14:56:08'),
(11,3,'HR Officer','2026-09-08 14:56:08'),
(12,3,'Recruiter','2026-09-08 14:56:08'),
(13,3,'Staff','2026-09-08 14:56:08'),

(14,4,'IT Manager','2026-09-08 14:56:08'),
(15,4,'Developer','2026-09-08 14:56:08'),
(16,4,'Support Specialist','2026-09-08 14:56:08'),
(17,4,'Staff','2026-09-08 14:56:08'),

(18,5,'Sales Manager','2026-09-08 14:56:08'),
(19,5,'Sales Officer','2026-09-08 14:56:08'),
(20,5,'Marketing Officer','2026-09-08 14:56:08'),
(21,5,'Staff','2026-09-08 14:56:08'),

(22,6,'Support Manager','2026-09-08 14:56:08'),
(23,6,'Support Agent','2026-09-08 14:56:08'),
(24,6,'Staff','2026-09-08 14:56:08');

-- --------------------------------------------------------
-- EMPLOYEES
-- --------------------------------------------------------

CREATE TABLE employees (
    employee_id INT NOT NULL AUTO_INCREMENT,
    employee_no VARCHAR(20) NOT NULL,
    portal_password VARCHAR(255) DEFAULT NULL, -- Employee Portal password hash
    photo_path VARCHAR(255) DEFAULT NULL,

    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,

    gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Other',
    birth_date DATE DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    civil_status VARCHAR(30) DEFAULT NULL,
    nationality VARCHAR(50) DEFAULT NULL,
    religion VARCHAR(50) DEFAULT NULL,

    permanent_address TEXT DEFAULT NULL,
    present_address TEXT DEFAULT NULL,

    sss_no VARCHAR(30) DEFAULT NULL,
    philhealth_no VARCHAR(30) DEFAULT NULL,
    pagibig_no VARCHAR(30) DEFAULT NULL,
    tin_no VARCHAR(30) DEFAULT NULL,
    atm_no VARCHAR(40) DEFAULT NULL,

    department VARCHAR(150) DEFAULT NULL,
    position VARCHAR(120) DEFAULT NULL,
    employment_status ENUM('Regular','Probationary','Contractual','Part-Time')
        NOT NULL DEFAULT 'Regular',

    basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    date_hired DATE DEFAULT NULL,

    emergency_contact_name VARCHAR(100) DEFAULT NULL,
    emergency_contact_phone VARCHAR(30) DEFAULT NULL,
    emergency_contact_address TEXT DEFAULT NULL,

    dependent_name VARCHAR(100) DEFAULT NULL,
    dependent_relationship VARCHAR(50) DEFAULT NULL,
    dependent_birth_date DATE DEFAULT NULL,

    education_background TEXT DEFAULT NULL,
    character_reference TEXT DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (employee_id),
    UNIQUE KEY uq_employee_no (employee_no),
    KEY idx_employee_department (department),
    KEY idx_employee_status (employment_status),
    KEY idx_employee_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- SAMPLE EMPLOYEE PORTAL ACCOUNT
-- Employee ID: 25-0001
-- Password: Employee@123
INSERT INTO employees
    (employee_no, portal_password, first_name, middle_name, last_name, gender, email, department, position, employment_status, basic_salary, date_hired)
VALUES
    ('25-0001', '$2y$12$RgvcOSwmrocDtSEbbT2zFO6V5mgTwm4Q12Vj7jg5TKIAPnJ0rjngG', 'Juan', 'D.', 'Dela Cruz', 'Male', 'juan.delacruz@example.com', 'Operations', 'Team Lead', 'Regular', 25000.00, '2026-06-01')
ON DUPLICATE KEY UPDATE
    portal_password = VALUES(portal_password);

-- --------------------------------------------------------
-- ATTENDANCE
-- --------------------------------------------------------

CREATE TABLE attendance (
    attendance_id INT NOT NULL AUTO_INCREMENT,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    time_in TIME DEFAULT NULL,
    time_out TIME DEFAULT NULL,
    status ENUM('Present','Late','Absent','On Leave','Half Day') NOT NULL DEFAULT 'Present',
    remarks VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (attendance_id),
    UNIQUE KEY uq_employee_attendance_date (employee_id, attendance_date),
    KEY idx_attendance_date (attendance_date),
    KEY idx_attendance_status (status),
    CONSTRAINT fk_attendance_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- EMPLOYEE REFERENCES
-- --------------------------------------------------------

CREATE TABLE employee_references (
    reference_id INT NOT NULL AUTO_INCREMENT,
    employee_id INT NOT NULL,
    reference_name VARCHAR(120) NOT NULL,
    reference_address TEXT DEFAULT NULL,
    reference_occupation VARCHAR(100) DEFAULT NULL,
    reference_contact_number VARCHAR(40) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (reference_id),
    KEY idx_reference_employee (employee_id),

    CONSTRAINT fk_reference_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees (employee_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- PAYROLL RECORDS
-- --------------------------------------------------------

CREATE TABLE payroll_records (
    payroll_id INT NOT NULL AUTO_INCREMENT,
    employee_id INT NOT NULL,

    period_start DATE NOT NULL,
    period_end DATE NOT NULL,

    basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    allowances DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    other_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deductions DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    gross_pay DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    net_pay DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    status ENUM('Draft','Approved','Paid') NOT NULL DEFAULT 'Draft',
    notes TEXT DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (payroll_id),
    KEY idx_payroll_employee (employee_id),
    KEY idx_payroll_period (period_start, period_end),
    KEY idx_payroll_status (status),

    CONSTRAINT fk_payroll_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees (employee_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- AUTO_INCREMENT STARTING VALUES
-- --------------------------------------------------------

ALTER TABLE admins AUTO_INCREMENT = 2;
ALTER TABLE departments AUTO_INCREMENT = 7;
ALTER TABLE positions AUTO_INCREMENT = 67;
ALTER TABLE employees AUTO_INCREMENT = 1;
ALTER TABLE employee_references AUTO_INCREMENT = 1;
ALTER TABLE payroll_records AUTO_INCREMENT = 1;

-- Done.
