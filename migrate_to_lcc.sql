-- LCC PAYROLL — migrate existing database from corporate seed to LCC colleges
-- Run once on an existing database (phpMyAdmin SQL tab or mysql CLI).
-- Fresh installs do NOT need this: supabase_schema.sql already seeds LCC.
-- Reverse of migrate_to_corporate.sql.
--
-- What it does (idempotent, safe to run twice):
-- 1. Renames the 6 corporate departments to LCC colleges by id.
-- 2. Rebuilds positions to the academic title list (66 rows:
--    CCTE 13, CON 12, CBA 11, CCJE/CELA/CITM 10 each).
-- 3. Remaps employees.department free-text values to LCC colleges.
-- 4. Sets app_settings.company_name to 'Lipa City Colleges'.

-- 1. Departments by id (corporate -> LCC colleges)
UPDATE departments SET department_name='College of Computing Technology and Engineering', department_code='CCTE', description='College of Computing Technology and Engineering', status='Active' WHERE department_id=1;
UPDATE departments SET department_name='College of Nursing', department_code='CON', description='College of Nursing', status='Active' WHERE department_id=2;
UPDATE departments SET department_name='College of Internal and Tourism Management', department_code='CITM', description='College of Internal and Tourism Management', status='Active' WHERE department_id=3;
UPDATE departments SET department_name='College of Criminal Justice Education', department_code='CCJE', description='College of Criminal Justice Education', status='Active' WHERE department_id=4;
UPDATE departments SET department_name='College of Business Accountancy', department_code='CBA', description='College of Business Accountancy', status='Active' WHERE department_id=5;
UPDATE departments SET department_name='College of Education and Liberal Arts', department_code='CELA', description='College of Education and Liberal Arts', status='Active' WHERE department_id=6;

-- Any leftover corporate-named rows (installed without ids) get renamed by name too
UPDATE departments SET department_name='College of Computing Technology and Engineering', department_code='CCTE' WHERE department_name='Information Technology';
UPDATE departments SET department_name='College of Nursing', department_code='CON' WHERE department_name='Customer Support';
UPDATE departments SET department_name='College of Internal and Tourism Management', department_code='CITM' WHERE department_name='Sales & Marketing';
UPDATE departments SET department_name='College of Criminal Justice Education', department_code='CCJE' WHERE department_name='Operations';
UPDATE departments SET department_name='College of Business Accountancy', department_code='CBA' WHERE department_name='Finance & Accounting';
UPDATE departments SET department_name='College of Education and Liberal Arts', department_code='CELA' WHERE department_name='Human Resources';

-- 2. Positions: rebuild academic list (employees store free text, so this is safe)
DELETE FROM positions;
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

-- 3. Employees: department free-text remap (corporate -> LCC)
UPDATE employees SET department='College of Computing Technology and Engineering' WHERE department='Information Technology';
UPDATE employees SET department='College of Nursing' WHERE department='Customer Support';
UPDATE employees SET department='College of Internal and Tourism Management' WHERE department='Sales & Marketing';
UPDATE employees SET department='College of Criminal Justice Education' WHERE department='Operations';
UPDATE employees SET department='College of Business Accountancy' WHERE department='Finance & Accounting';
UPDATE employees SET department='College of Education and Liberal Arts' WHERE department='Human Resources';

-- 4. Branding
UPDATE app_settings SET setting_value='Lipa City Colleges' WHERE setting_key='company_name' AND setting_value='Paywise';
