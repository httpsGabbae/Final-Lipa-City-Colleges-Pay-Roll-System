# LCC Payroll System

## Overview

The **LCC Payroll System** is a web-based employee and payroll management application designed for **Lipa City Colleges (LCC)**.

The system brings employee records, salary information, payroll processing, reporting, and printable records into one centralized application. It is designed to make common payroll and personnel tasks easier to manage while keeping the interface simple enough for day-to-day administrative use.

The project is built with **PHP and MySQL/MariaDB**, with **FPDF** used for print-ready payroll and employee reports.

---

## Purpose of the System

The main purpose of the system is to provide a centralized platform for managing employee information and payroll records.

Instead of maintaining employee information and payroll data separately, the system connects employee records with their corresponding payroll records. This makes it easier to view employee details, prepare payroll entries, monitor payroll status, and generate reports for a selected period.

The system also provides printable documents so that important employee and payroll information can be presented as formal records.

---

## System Modules

### 1. Login and Authentication

The system starts with an administrator login page. Authentication protects the administrative pages and prevents unauthorized users from accessing employee and payroll information.

The authentication area handles:

- Administrator login
- Session-based access control
- Logout
- Password-related functions
- Database connection

---

### 2. Dashboard

The dashboard provides a quick overview of the organization's workforce rather than displaying detailed payroll amounts.

It includes information such as:

- Total number of employees
- Number of departments
- Regular employees
- Other employment statuses
- Employees grouped by department
- Employment status distribution
- Quick access to major system modules

The dashboard is intended to provide an administrative overview at a glance while keeping detailed financial information inside the Payroll and Reports sections.

---

### 3. Employee Management

The Employee Management module serves as the main employee directory.

Administrators can manage employee information including:

- Employee number
- Full name
- Gender
- Birth date
- Contact information
- Email address
- Civil status
- Nationality
- Address information
- Government reference numbers
- Department
- Position
- Employment status
- Basic salary
- Date hired
- Emergency contact
- Dependent information
- Educational background
- Character references
- Employee photo

The employee list is focused on personnel information. Salary information is handled separately so that the main employee directory remains cleaner and easier to navigate.

---

### 4. Employee Record / Dossier

Each employee can be opened as an individual record containing organized personnel information.

The employee record is presented as a dossier-style document with sections for employment, personal information, addresses, and government references.

Individual employee records can also be printed as formal documents using FPDF.

The printed employee record uses a clean document header containing:

- LCC Payroll System
- Employee Record
- Printed date and time
- Official employee record label

Unnecessary screen controls such as print buttons and close buttons are excluded from the actual printed document.

---

### 5. Salary Information

Salary information is separated from the main employee table to keep employee browsing organized.

The salary section displays the employee's relevant employment and compensation information, including the employee's basic salary.

Payroll-related earnings and deductions are recorded through the Payroll module rather than directly modifying historical payroll records.

---

### 6. Payroll Management

The Payroll module is used to create and manage payroll records for employees.

A payroll record can contain:

- Employee
- Payroll period start
- Payroll period end
- Basic salary
- Allowances
- Other earnings
- Deductions
- Gross pay
- Net pay
- Payroll status
- Notes

The system calculates the payroll totals from the recorded earnings and deductions, allowing the resulting gross and net amounts to be displayed consistently throughout the system.

Payroll records also have a status that allows them to be tracked through different stages:

- **Draft** – payroll information is still being prepared
- **Approved** – payroll has been reviewed or approved
- **Paid** – payroll has been released or marked as paid

---

## Payroll Calculation Concept

The system separates the major components of payroll so that the calculation can be understood clearly.

**Gross Pay** represents the employee's earnings before deductions.

**Net Pay** represents the remaining amount after deductions are applied.

The payroll record stores the resulting gross and net amounts together with the individual earning and deduction components.

---

## 7. Reports

The Reports module provides a more detailed view of payroll information for a selected month.

The monthly report includes summary information such as:

- Number of payroll records
- Total gross payroll
- Total deductions
- Total net payroll
- Number of paid records
- Payroll records included in the selected period
- Department payroll breakdown

The report is intended for administrative review and provides a more complete financial view than the main dashboard.

---

## Six-Month Payroll Overview

The Reports section includes a compact **six-month Gross vs Net Payroll bar graph**.

The graph compares the following values for each month:

- Gross payroll
- Net payroll

The selected report month is included together with the five preceding months. This gives administrators a quick way to see how payroll totals have changed over time without taking up a large amount of space on the report page.

The same concept is also included in the printable monthly FPDF report so that the visual summary is preserved when the report is printed.

---

## 8. Printable Reports

Printing is handled separately from the normal web interface using FPDF.

The system provides print-ready documents for:

- Individual employee records
- Employee lists
- Individual payroll records
- All payroll records
- Monthly payroll reports

The monthly payroll printout includes the selected reporting period, payroll information, totals, department information, and the compact six-month payroll graph.

Printed documents also record the date and time when the report was generated.

The goal of the print system is to produce documents that look like formal administrative records rather than simply printing the application's web interface.

---

## User Interface

The application uses a responsive administrative interface intended for both desktop and mobile screens.

The interface includes:

- Sidebar navigation
- Responsive layouts
- Employee management screens
- Payroll forms
- Report views
- Modal-based employee information
- Print-friendly document views
- Dark mode support
- Mobile-friendly controls

The web interface and the printable FPDF documents are intentionally treated as separate experiences. The web interface contains interactive controls, while the printed documents contain only the information needed for the official record.

---

## Technology Stack

| Technology | Purpose |
|---|---|
| **PHP** | Server-side application logic |
| **MySQL / MariaDB** | Database management |
| **HTML** | Page structure |
| **CSS** | Interface styling and responsive layout |
| **JavaScript** | Client-side interactions and interface behavior |
| **FPDF** | PDF and print-ready document generation |
| **Composer** | PHP dependency management |
|
---

## Database Structure

The database is organized around several main areas of the system.

### Administrators

Stores administrator accounts used to access the system.

### Employees

Stores the main personnel information for every employee. This includes personal information, employment information, contact details, salary information, and other employee references.

### Payroll Records

Stores individual payroll transactions and connects each payroll record to an employee.

Payroll records contain the payroll period, earnings, deductions, gross pay, net pay, status, and notes.

### Application Settings

Stores configurable system values such as employee-number formatting settings.

The database also uses relationships between employees and payroll records so that payroll entries remain associated with the correct employee.

---

## Employee Number Format

The system uses a human-readable employee number separate from the internal database ID.

The default employee-number format uses:

- Prefix: **25**
- Four numeric digits

Examples:

- **25-0001**
- **25-0002**
- **25-0003**

The internal database ID remains separate from the employee-facing number.

---

## Project Organization

The project is separated into functional areas to make the system easier to maintain.

### Main application pages

- `login.php` – administrator login page
- `admin_dashboard.php` – workforce dashboard
- `employees.php` – employee directory
- `personalinfo.php` – employee information management
- `salary_info.php` – salary information
- `payroll.php` – payroll management
- `reports.php` – monthly payroll reports
- `employee_preview.php` – employee record preview
- `payroll_preview.php` – payroll record preview

### Authentication

The `auth` directory contains login, logout, password, and database connection functions.

### Shared components

The `includes` directory contains reusable authentication, layout, and FPDF-related helpers.

### Printing

The `print` directory contains the FPDF documents generated by the system.

### Assets

The `assets` directory contains the application's CSS and JavaScript resources.

### Database

The `database` directory contains the SQL schema used to create the application's database structure.

### Uploads

The `uploads` directory contains the application logo and employee photos.

---

## Typical System Workflow

The normal administrative workflow is:

**Login → Dashboard → Employee Management → Payroll → Reports → Print**

1. The administrator signs into the system.
2. The dashboard provides an overview of the workforce.
3. Employee information is added or maintained in Employee Management.
4. Salary and employment information can be reviewed separately.
5. Payroll records are prepared for the appropriate employee and pay period.
6. Payroll status can be updated as the record progresses.
7. Reports are reviewed by month.
8. The monthly report can be printed as an official FPDF document.

---

## Design Goals

The system was developed around several practical goals:

- Keep employee and payroll information organized.
- Reduce unnecessary duplication of information.
- Make payroll records easier to review.
- Keep financial information out of the general workforce dashboard.
- Provide useful monthly payroll summaries.
- Make reports suitable for printing.
- Keep the interface usable on both desktop and mobile devices.
- Separate interactive web controls from official printed documents.
- Keep the project structure understandable for future development and maintenance.

---

## Current System Highlights

The current version includes the following major improvements:

- Workforce-focused administrative dashboard
- Separate salary information view
- Employee dossier-style records
- Responsive employee management interface
- Payroll record management
- Payroll status tracking
- Monthly payroll reporting
- Department payroll breakdown
- Six-month Gross vs Net payroll bar graph
- Six-month graph included in the printable monthly report
- FPDF employee records
- FPDF payroll reports
- Print timestamps
- Clean print-specific layouts
- Mobile-responsive interface
- Dark mode support
- Organized PHP, CSS, JavaScript, database, and print directories

---

## Project Status

This project is an actively developed payroll and personnel management system. Features and interface details may continue to change as the system is improved and tested.

The current focus is on maintaining a clean administrative workflow, reliable payroll records, useful reporting, responsive design, and professional print output.

---

## Notes

This repository contains the application source code and database structure for the LCC Payroll System. Before deploying the system to an actual production environment, database credentials, administrator accounts, file permissions, uploaded files, and other deployment-specific settings should be reviewed and secured appropriately.

---

**LCC Payroll System**  
Employee & Payroll Management System for Lipa City Colleges

## Project Structure

All PHP application pages are grouped inside `pages/`. The project root is kept minimal, with only the entry point and project-level configuration/documentation. Supporting code is separated by responsibility.

```text
LCC-Payroll-System/
├── pages/
│   ├── login.php
│   ├── admin_dashboard.php
│   ├── employees.php
│   ├── personalinfo.php
│   ├── employee_preview.php
│   ├── salary_info.php
│   ├── payroll.php
│   ├── payroll_preview.php
│   └── reports.php
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── change_password.php
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   ├── layout.php
│   └── pdf.php
├── print/
│   ├── employee.php
│   ├── employee_profiles.php
│   ├── employees.php
│   ├── monthly_payroll.php
│   ├── payroll.php
│   └── payroll_all.php
├── assets/
│   ├── css/
│   └── js/
├── uploads/
│   ├── employee_photos/
│   └── logos
├── database/
│   └── schema.sql
├── index.php
├── composer.json
├── composer.lock
└── README.md
```
