# 🏥 MediCore — Hospital Management System

> **CSE 4508: Relational Database Management Systems**  
> Final Submission — Week 13  
> **Team As Sabr** | Presented: 12 March 2026

---

## 👥 Team Members

| Name | Student ID | Role |
|------|-----------|------|
| Rayan Idriss | 220041257 | Database Design & Backend Logic |
| Abdul Razak | 220041258 | Database Design & Backend Integration |
| Obaidullah | 220041261 | Planning, UI/UX Design & Frontend |
| Issa Soumaila | 220041267 | Database Design & Backend Logic |

---

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Database Schema](#database-schema)
- [Project Structure](#project-structure)
- [SQL Components](#sql-components)
- [Installation & Setup](#installation--setup)
- [Usage](#usage)
- [Screenshots](#screenshots)

---

## 📌 Project Overview

**MediCore** is a fully functional, web-based Hospital Management System built on a centralized relational database (MySQL). It digitizes and automates core hospital workflows — from patient registration and doctor scheduling through to billing, pharmacy stock, and lab reporting — eliminating the manual record-keeping, billing errors, and data loss that plague paper-based hospital operations.

The system was developed across two milestones:
- **Week 8** — Initial schema design, ER diagram, core SQL queries, and first trigger/function implementations.
- **Week 13 (Final)** — Full frontend + backend integration, additional stored functions, procedures, triggers, views, transaction demos, and a complete seed dataset.

---

## ✨ Features

| Feature | Status |
|---------|--------|
| Patient Registration (Inpatient & Outpatient) | ✅ Complete |
| Doctor Management | ✅ Complete |
| Appointment Scheduling | ✅ Complete |
| Prescription Management | ✅ Complete |
| Room & Bed Allocation | ✅ Complete |
| Invoice Generation | ✅ Complete |
| Payment Recording | ✅ Complete |
| Lab Orders & Results | ✅ Complete |
| Medicine / Pharmacy Stock | ✅ Complete |
| Staff Management | ✅ Complete |
| Audit Logging (via Triggers) | ✅ Complete |
| Patient History Tracking | ✅ Complete |
| Reports & Analytics Dashboard | ✅ Complete |

---

## 🛠 Tech Stack

### Frontend
- HTML5
- CSS3 (custom stylesheet)
- Vanilla JavaScript

### Backend
- PHP (PDO — prepared statements throughout)

### Database
- MySQL 8.x (`medicore_db`)
- Character set: `utf8mb4` / collation: `utf8mb4_unicode_ci`
- Timezone: `+06:00` (Bangladesh Standard Time)

### Environment
- XAMPP (Apache + MySQL)
- Tested on localhost

---

## 🗄 Database Schema

The database contains **17 entities**, all normalized to **3NF**.

### Entity Overview

| Entity | Primary Key | Description |
|--------|------------|-------------|
| `department` | `dept_id` | Hospital departments |
| `doctor` | `doctor_id` | Doctors and specializations |
| `patient` | `patient_id` | Patient demographics and contact info |
| `appointment` | `appt_id` | Doctor–patient scheduling |
| `encounter` | `encounter_id` | Clinical visit records |
| `prescription` | `rx_id` | Prescription headers |
| `prescription_detail` | `detail_id` | Per-medicine prescription lines |
| `medicine` | `medicine_id` | Pharmacy inventory |
| `lab_order` | `order_id` | Lab test requests |
| `lab_result` | `result_id` | Lab test outcomes |
| `invoice` | `invoice_id` | Patient billing |
| `payment` | `payment_id` | Payment transactions |
| `room` | `room_id` | Hospital rooms |
| `bed` | `bed_id` | Individual beds per room |
| `staff` | `staff_id` | Non-doctor hospital staff |
| `patient_history` | `history_id` | Audit trail of patient record changes |
| `audit_log` | `log_id` | System-wide audit trail (populated by triggers) |

### ER Diagram Summary

```
department  1───< doctor   >───< appointment >───< patient
department  1───< room     >───< bed
doctor      1───< prescription >───< prescription_detail >───< medicine
doctor      1───< encounter
encounter   1───< lab_order >───< lab_result
patient     1───< invoice  >───< payment
patient     1───< patient_history
ALL tables  ──────────────────────────────> audit_log  (via triggers)
department  1───< staff
```

### Normalization Notes

- All tables satisfy **1NF**, **2NF**, and **3NF**.
- `invoice.balance_due` is a `GENERATED ALWAYS` stored column (intentional denormalization) for financial reporting performance.
- `patient.registration_date` is retained separately from `created_at` for business-level tracking.

---

## 📁 Project Structure

```
medicore/
├── config/
│   └── database.php               # PDO singleton — DB credentials & helpers
├── includes/
│   ├── auth_check.php             # Session authentication guard
│   ├── header.php                 # Shared HTML header
│   ├── sidebar.php                # Navigation sidebar
│   └── footer.php                 # Shared HTML footer
├── assets/
│   ├── css/style.css              # Global stylesheet
│   └── js/main.js                 # Frontend interactions
├── sql/
│   ├── schemas/
│   │   ├── 01_create_database.sql      # Database creation
│   │   ├── 02_create_tables.sql        # All 17 table definitions
│   │   ├── 03_constraints_and_fk.sql   # Foreign keys & constraints
│   │   ├── 04_indexes.sql              # Performance indexes
│   │   ├── 05_views.sql                # 7 views
│   │   └── 06_roles_access.sql         # DB user roles
│   ├── routines/
│   │   ├── 01_functions.sql            # 6 stored functions
│   │   ├── 02_procedures.sql           # Stored procedures
│   │   └── 03_triggers.sql             # 5 triggers
│   ├── data/
│   │   ├── 01_seed_data.sql            # Full seed dataset
│   │   └── 02_extended_data.sql        # Extended sample data
│   ├── queries/
│   │   ├── 01_all_queries.sql          # 15 analytical SQL queries
│   │   └── 02_transactions_demo.sql    # Transaction demos (commit/rollback/savepoint)
│   ├── master.sql                      # Single-file full setup
│   └── MASTER_SETUP.sql                # Ordered setup runner
├── index.php                      # Dashboard
├── login.php                      # Authentication
├── logout.php                     # Session termination
├── setup.php                      # Admin DB initializer
├── patients.php                   # Patient CRUD
├── doctors.php                    # Doctor CRUD
├── appointments.php               # Appointment scheduling
├── encounters.php                 # Clinical encounters
├── prescriptions.php              # Prescriptions list
├── prescription_details.php       # Prescription line items
├── medicines.php                  # Pharmacy inventory
├── lab_orders.php                 # Lab order management
├── invoices.php                   # Invoice management
├── payments.php                   # Payment recording
├── rooms.php                      # Room & bed management
├── staff.php                      # Staff management
├── departments.php                # Department management
├── patient_history.php            # Patient audit history
├── audit_log.php                  # System audit log
└── reports.php                    # Analytics & reports
```

---

## 🔧 SQL Components

### Views (7 total — minimum required: 2)

| View | Purpose |
|------|---------|
| `vw_upcoming_appointments` | Upcoming scheduled/confirmed appointments with patient age |
| `vw_revenue_summary` | Financial revenue aggregation |
| + 5 additional views | Covering doctors, patients, lab orders, prescriptions, and stock |

### Stored Functions (6 total — minimum required: 3)

| Function | Added | Description |
|----------|-------|-------------|
| `fn_patient_age(dob)` | Week 8 | Returns patient age in whole years |
| `fn_invoice_balance(invoice_id)` | Week 13 | Returns outstanding balance on an invoice |
| `fn_bed_availability(room_id)` | Week 13 | Returns count of available beds in a room |
| `fn_doctor_schedule(doctor_id, date)` | Week 13 | Returns doctor's appointment count for a date |
| `fn_medicine_stock_status(medicine_id)` | Week 13 | Returns stock status label (OK / LOW / OUT) |
| `fn_patient_total_bill(patient_id)` | Week 13 | Returns total billed amount for a patient |

### Stored Procedures

| Procedure | Description |
|-----------|-------------|
| `sp_dispense_medicine(rx_id)` | Iterates prescription lines, checks stock, deducts quantities, and rolls back on any shortage — wrapped in a full transaction |

### Triggers (5 total — minimum required: 3)

| Trigger | Type | Added | Description |
|---------|------|-------|-------------|
| `trg_before_appointment_overlap` | BEFORE INSERT | Week 8 | Prevents double-booking; validates time ordering and rejects past appointments |
| `trg_after_appointment_status` | AFTER UPDATE | Week 13 | Writes to `audit_log` whenever appointment status changes |
| `trg_before_medicine_stock` | BEFORE UPDATE | Week 13 | Blocks stock from going below zero |
| `trg_after_patient_update` | AFTER UPDATE | Week 13 | Records patient record changes in `patient_history` |
| `trg_after_payment_insert` | AFTER INSERT | Week 13 | Multi-row/complex trigger that updates invoice payment status after a payment is recorded |

**Coverage:** ✅ BEFORE triggers ✅ AFTER triggers ✅ Multi-row/complex trigger ✅ Audit table updated via triggers

### SQL Queries (15 total — minimum required: 10)

| # | Query | Techniques Used |
|---|-------|----------------|
| Q01 | Appointment trends by day of week | Date functions, conditional aggregation |
| Q02 | Top performing doctors (monthly ranking) | Window functions (`RANK`, `DENSE_RANK`), derived table |
| Q03 | Appointment efficiency metrics | `UNION ALL` across time ranges |
| Q04 | Department revenue breakdown | Multi-table JOIN, GROUP BY |
| Q05 | Patient demographics report | Aggregation, CASE |
| Q06 | Patients with no appointments (subquery) | Nested subquery |
| Q07 | Full patient–doctor–prescription report | Multi-table JOIN |
| Q08 | Room occupancy report | Multi-table JOIN |
| Q09 | Doctors above average patient count | Correlated subquery |
| Q10 | Medicines below reorder level | Subquery |
| Q11 | Monthly revenue trend | Window functions |
| Q12 | Doctor workload distribution | Window functions |
| Q13 | Revenue by department + grand total | `ROLLUP` |
| Q14 | Patients with pending invoices | Nested subquery |
| Q15 | Top diagnoses by frequency | Subquery |

### Transactions (Week 13)

`02_transactions_demo.sql` demonstrates:
- ✅ **COMMIT** — successful multi-step transaction (register patient → schedule appointment → generate invoice)
- ✅ **ROLLBACK** — automatic rollback on constraint violation
- ✅ **SAVEPOINT** — partial rollback capability
- ✅ **Deadlock/race condition** — bonus demonstration

---

## ⚙️ Installation & Setup

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL) — or any PHP 8.x + MySQL 8.x environment
- A web browser

### Steps

1. **Clone / download** this repository into your XAMPP `htdocs` folder:

   ```bash
   git clone https://github.com/your-username/medicore-hms.git
   cd htdocs/medicore-hms
   ```

2. **Start XAMPP** — enable Apache and MySQL.

3. **Initialize the database** — two options:

   **Option A (recommended): Web Setup**
   - Open your browser and go to `http://localhost/medicore/setup.php`
   - Log in as admin and click **Run Setup**. The setup runner will execute all SQL files in order.

   **Option B: Manual via phpMyAdmin / MySQL CLI**
   ```sql
   SOURCE /path/to/medicore/sql/master.sql;
   ```
   Or run the numbered files in order:
   ```
   schemas/01_create_database.sql
   schemas/02_create_tables.sql
   schemas/03_constraints_and_fk.sql
   schemas/04_indexes.sql
   schemas/05_views.sql
   schemas/06_roles_access.sql
   routines/01_functions.sql
   routines/02_procedures.sql
   routines/03_triggers.sql
   data/01_seed_data.sql
   data/02_extended_data.sql
   ```

4. **Configure the database connection** in `config/database.php`:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');          // your MySQL root password
   define('DB_NAME', 'medicore_db');
   ```

5. **Open the app** at `http://localhost/medicore/`

6. **Log in** with the admin credentials seeded in `01_seed_data.sql`.

---

## 🚀 Usage

After logging in, the sidebar gives access to all modules:

- **Dashboard** — live KPIs: total patients, active doctors, today's appointments, available beds, open invoices, pending labs, low-stock medicines, and active staff.
- **Patients** — register, search, view, and update patient records.
- **Doctors** — manage doctor profiles, specializations, and departments.
- **Appointments** — schedule, confirm, cancel, or mark appointments complete. The `trg_before_appointment_overlap` trigger automatically prevents double-booking.
- **Encounters** — record clinical visits linked to appointments.
- **Prescriptions** — create prescriptions; the `sp_dispense_medicine` procedure manages stock deduction atomically.
- **Lab Orders** — request and record lab tests and results.
- **Invoices & Payments** — generate bills, record payments. The `trg_after_payment_insert` trigger auto-updates invoice status.
- **Rooms & Beds** — view availability and manage allocations.
- **Medicines** — monitor pharmacy inventory with automatic low-stock alerts.
- **Staff** — manage non-doctor hospital employees.
- **Reports** — analytical dashboards powered by the 15 SQL queries (appointment trends, doctor rankings, revenue summaries, etc.).
- **Audit Log** — full system audit trail maintained automatically by triggers.

---

## 📄 License

This project was developed as an academic submission for **CSE 4508: RDBMS** at the university level. All rights reserved by the team members.

---

*MediCore HMS — As Sabr Team, 2026*
