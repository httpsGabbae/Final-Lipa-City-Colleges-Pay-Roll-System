-- LCC PAYROLL SYSTEM
-- Fresh / clean database schema
-- MariaDB 10.4+ / MySQL 8+
--
-- IMPORTANT:
-- 1. Create a NEW empty database (recommended), e.g. lcc_payroll_new.
-- 2. Select that database in phpMyAdmin.
-- 3. Import this file.
-- 4. Do NOT import this into the old database unless you intentionally want
--    to replace its existing tables and data.
--
-- This schema is based on the user's current database structure and adds
-- the employee/payroll tables required by the payroll system.

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
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO admins
    (admin_id, username, password_hash, full_name, created_at)
VALUES
    (1, 'admin',
     '$2y$10$cK4PFcHW5dXRAMOZuTb2GeBosLrZWt7WSfV7NACSSFcfZPo.PbWHK',
     'System Administrator',
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
('company_name', 'LCC Payroll System'),
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
(1, 'College of Computing Technology and Engineering', 'CCTE',
 'College of Computing Technology and Engineering', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(2, 'College of Nursing', 'CON',
 'College of Nursing', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(3, 'College of Internal and Tourism Management', 'CITM',
 'College of Internal and Tourism Management', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(4, 'College of Criminal Justice Education', 'CCJE',
 'College of Criminal Justice Education', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(5, 'College of Business Accountancy', 'CBA',
 'College of Business Accountancy', 'Active',
 '2026-09-08 14:56:08', '2026-09-09 08:16:01'),

(6, 'College of Education and Liberal Arts', 'CELA',
 'College of Education and Liberal Arts', 'Active',
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
(1,1,'Dean','2026-09-08 14:56:08'),
(2,1,'Department Head','2026-09-08 14:56:08'),
(3,1,'Coordinator','2026-09-08 14:56:08'),
(4,1,'Professor','2026-09-08 14:56:08'),
(5,1,'Associate Professor','2026-09-08 14:56:08'),
(6,1,'Assistant Professor','2026-09-08 14:56:08'),
(7,1,'Instructor','2026-09-08 14:56:08'),
(8,1,'Lecturer','2026-09-08 14:56:08'),
(9,1,'Staff','2026-09-08 14:56:08'),
(10,1,'Administrative Staff','2026-09-08 14:56:08'),
(11,1,'Laboratory Staff','2026-09-08 14:56:08'),
(12,1,'Technician','2026-09-08 14:56:08'),
(13,1,'IT Staff','2026-09-08 14:56:08'),

(14,2,'Dean','2026-09-08 14:56:08'),
(15,2,'Department Head','2026-09-08 14:56:08'),
(16,2,'Coordinator','2026-09-08 14:56:08'),
(17,2,'Professor','2026-09-08 14:56:08'),
(18,2,'Associate Professor','2026-09-08 14:56:08'),
(19,2,'Assistant Professor','2026-09-08 14:56:08'),
(20,2,'Instructor','2026-09-08 14:56:08'),
(21,2,'Lecturer','2026-09-08 14:56:08'),
(22,2,'Clinical Instructor','2026-09-08 14:56:08'),
(23,2,'Staff','2026-09-08 14:56:08'),
(24,2,'Administrative Staff','2026-09-08 14:56:08'),
(25,2,'Laboratory Staff','2026-09-08 14:56:08'),

(26,3,'Dean','2026-09-08 14:56:08'),
(27,3,'Department Head','2026-09-08 14:56:08'),
(28,3,'Coordinator','2026-09-08 14:56:08'),
(29,3,'Professor','2026-09-08 14:56:08'),
(30,3,'Associate Professor','2026-09-08 14:56:08'),
(31,3,'Assistant Professor','2026-09-08 14:56:08'),
(32,3,'Instructor','2026-09-08 14:56:08'),
(33,3,'Lecturer','2026-09-08 14:56:08'),
(34,3,'Staff','2026-09-08 14:56:08'),
(35,3,'Administrative Staff','2026-09-08 14:56:08'),

(36,4,'Dean','2026-09-08 14:56:08'),
(37,4,'Department Head','2026-09-08 14:56:08'),
(38,4,'Coordinator','2026-09-08 14:56:08'),
(39,4,'Professor','2026-09-08 14:56:08'),
(40,4,'Associate Professor','2026-09-08 14:56:08'),
(41,4,'Assistant Professor','2026-09-08 14:56:08'),
(42,4,'Instructor','2026-09-08 14:56:08'),
(43,4,'Lecturer','2026-09-08 14:56:08'),
(44,4,'Staff','2026-09-08 14:56:08'),
(45,4,'Administrative Staff','2026-09-08 14:56:08'),

(46,5,'Dean','2026-09-08 14:56:08'),
(47,5,'Department Head','2026-09-08 14:56:08'),
(48,5,'Coordinator','2026-09-08 14:56:08'),
(49,5,'Professor','2026-09-08 14:56:08'),
(50,5,'Associate Professor','2026-09-08 14:56:08'),
(51,5,'Assistant Professor','2026-09-08 14:56:08'),
(52,5,'Instructor','2026-09-08 14:56:08'),
(53,5,'Lecturer','2026-09-08 14:56:08'),
(54,5,'Staff','2026-09-08 14:56:08'),
(55,5,'Administrative Staff','2026-09-08 14:56:08'),
(56,5,'Accountant','2026-09-08 14:56:08'),

(57,6,'Dean','2026-09-08 14:56:08'),
(58,6,'Department Head','2026-09-08 14:56:08'),
(59,6,'Coordinator','2026-09-08 14:56:08'),
(60,6,'Professor','2026-09-08 14:56:08'),
(61,6,'Associate Professor','2026-09-08 14:56:08'),
(62,6,'Assistant Professor','2026-09-08 14:56:08'),
(63,6,'Instructor','2026-09-08 14:56:08'),
(64,6,'Lecturer','2026-09-08 14:56:08'),
(65,6,'Staff','2026-09-08 14:56:08'),
(66,6,'Administrative Staff','2026-09-08 14:56:08');

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
    ('25-0001', '$2y$12$RgvcOSwmrocDtSEbbT2zFO6V5mgTwm4Q12Vj7jg5TKIAPnJ0rjngG', 'Juan', 'D.', 'Dela Cruz', 'Male', 'juan.delacruz@example.com', 'College of Computing Technology and Engineering', 'Instructor', 'Regular', 25000.00, '2026-06-01')
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
