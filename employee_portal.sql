-- LCC Payroll System - Employee Portal patch
-- Run this on an EXISTING lcc_payroll database.
-- The current schema already contains employees.portal_password.
-- This patch only makes sure the column exists for older databases.

SET @db = DATABASE();
SET @has_portal_password = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='employees' AND COLUMN_NAME='portal_password'
);
SET @sql = IF(@has_portal_password=0,
    'ALTER TABLE employees ADD COLUMN portal_password VARCHAR(255) DEFAULT NULL AFTER employee_no',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Employee portal accounts use employees.employee_no + employees.portal_password.
-- Passwords are stored as PHP password_hash() values by the admin employee form
-- and by the employee Change Password page.
