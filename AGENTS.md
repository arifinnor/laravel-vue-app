# AGENTS Domain Outline

## Project Overview
This platform streamlines academic operations across student services, teaching workflows, class logistics, and attendance monitoring. **Crucially, it features a robust Double-Entry Accounting System** that integrates financial operations (Tuition, Payroll, Expenses) with academic data, providing real-time financial health insights through standard accounting reports.

## Use Laravel Way
When scaffolding new application code, prefer the relevant `php artisan make:*` generators (e.g., `php artisan make:model Example -mfs` or `php artisan make:controller Example --resource`). Use **Inertia.js (Vue 3)** for the frontend and **Tailwind CSS** and **shadcn-vue** for styling.

## Module Map
- `Students`: Enrollment management, guardianship, academic standing.
- `Teachers`: Assignment, qualifications, availability, performance feedback.
- `Classes`: Scheduling, curriculum alignment, capacity management.
- `Attendance`: Session tracking, absence alerts, compliance reporting.
- **`Finance`: Chart of Accounts, Journal Entries, Transaction Configuration, Financial Reporting.** (Replaces legacy Manual Payments).

## Core Data Entities
### Academic
- `Student`: profile, contact info, guardians, enrollment history.
- `Teacher`: profile, certifications, subject specialties.
- `Class`: metadata, term, schedule, roster, primary teacher.
- `AttendanceRecord`: session, participant, status.

### Finance (New Core)
- `AccountCategory`: Reporting groups (Assets, Liabilities, Equity, Revenue, Expense) mapped to Balance Sheet or Income Statement.
- `ChartOfAccount`: Hierarchical account structure with flags (`is_cash`, `is_posting`, `normal_balance`).
- `TransactionType`: Hybrid definition of business events.
  - **System Types** (Hardcoded Logic): Tuition Billing, Payroll, Admission.
  - **Custom Types** (User Defined): Ad-hoc expenses, Asset purchases.
- `TransactionAccount` (Mapping): Links Transaction Types to specific COAs with strict roles (`debit_receivable`, `credit_revenue`) and directions.
- `JournalEntry` (Header): Immutable ledger record with `status` (POSTED, VOID - **No Delete**).
- `JournalEntryLine` (Detail): Double-entry lines linking amounts to COA.

## User Roles
- `Student`: View schedule, attendance, **tuition bills, and payment history**.
- `Teacher`: Manage class rosters, attendance.
- `Registrar/Admin`: Create classes, manage enrollments, audit academic data.
- **`Finance/Accountant`**:
  - Manage Chart of Accounts & Opening Balances.
  - Configure Transaction Mappings (System & Custom).
  - Record Daily Transactions (Journaling).
  - View Financial Reports (General Ledger, Trial Balance, Income Statement, Balance Sheet).

## Prioritized MVP Features

1. **Student Portal**
   - Secure login and profile view.
   - Class schedule & Attendance history.
   - **Financial Dashboard:** View outstanding tuition, download invoices, view payment history.

2. **Teacher Workspace**
   - Class roster & Attendance entry.
   - Alerts for attendance anomalies.

3. **Class Management**
   - CRUD for classes, schedules, and enrollments.

4. **Finance & Accounting Engine (Major Update)**
   - **Chart of Accounts (COA):** Hierarchical management with auto-code suggestion logic.
   - **Hybrid Transaction Configuration:**
     - *System Transactions:* Pre-defined logic for core flows (Tuition, Salary) with user-mappable accounts (Protected Structure).
     - *Custom Transactions:* Flexible user-defined journals (e.g., buying supplies) with dynamic split capabilities (Multi-line mappings).
   - **Smart Journal Entry Form:**
     - Auto-detection of "Fixed" vs "Dynamic" legs (e.g., hiding Cash dropdown if mapped to QRIS Gateway).
     - Strict Double-Entry validation (Debit must equal Credit).
     - **Immutable Ledger:** "Void only" policy (Soft Deletes removed/forbidden for audit integrity).
   - **Financial Reporting Suite:**
     - **General Ledger:** Detailed running balance per account.
     - **Trial Balance:** Summary of all account movements to ensure system balance.
     - **Income Statement:** Profit/Loss analysis grouped by categories.
     - **Balance Sheet:** Asset/Liability snapshot with automatic *Current Year Earnings* injection logic.

5. **Attendance Operations**
   - Session-based recording & Audit trail.
   - Compliance dashboard.