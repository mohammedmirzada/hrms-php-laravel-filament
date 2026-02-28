# HRMS

## Stack
- **Backend:** Laravel
- **Admin/UI:** Filament (multi-panel: HR, Payroll, Reporting)
- **Architecture:** Modular monolith (clean boundaries)
  - Core, HR, Attendance, Payroll, Reporting

## Core Requirements
- **Multi-branch:** unlimited branches; branch configs control rules & visibility
- **Multi-language:** Kurdish (Sorani), Arabic (RTL), English  
  - per-user language preference
  - language-based report generation
- **Security & Governance**
  - Role-based permissions (module + branch scopes + salary visibility)
  - Audit logs for critical actions
  - Secure private file storage (private disk)

## Multi-Branch Model
Each branch has its own:
- Working hours, grace period, holidays
- Payroll cycle, default currency
- Shift templates
- Managers only see assigned branch data

## Employee Lifecycle
### Employee Profile
- **Personal:** full name (3 languages), gender, DOB, ID/passport, address, phone, email, marital, emergency contact
- **Employment:** auto Employee ID, dept, position, manager, branch, type, hire date, probation, contract expiry, salary structure

### Status Engine (logged)
- Future Hired → Active → Suspended / Resigned / Terminated  
- All status changes are audited.

## Document Management
- Upload: contracts, IDs, CVs, certificates
- Secure storage + role-based access
- Expiry tracking + alerts

## Leave Management
- Types: annual, sick, maternity, unpaid, emergency, official + custom
- Rules: configurable accrual, balance tracking, hourly/half/full-day, auto-deduction, optional negative-balance prevention
- Workflow: Employee → Manager → HR → Final approval
- Calendar: branch/public holidays + team view

## Attendance Management
- **Biometric:** ZKTeco, Suprema (real-time or scheduled sync; device↔employee mapping)
- Late detection, overtime calc, missed punch detection
- HR override requires reason + audit log
- Branch rules: working hours, grace, overtime, shifts
- **Mobile:** GPS check-in, selfie verification, optional geofence

## Payroll Engine (Deterministic)
- Components: basic, allowances, overtime, bonuses, deductions, penalties, advances
- Multi-currency: IQD + USD, exchange rate table, convert at processing date, controlled rounding
- Social Security (KRG): configurable employee/employer %, base rule, caps, employee-type variations
- Flow: lock attendance → calculate → apply SS + penalties → approval → payslips PDF + email → lock payroll (immutable after approval)

## Roles & Permissions
- Roles: Super Admin, HR Admin, Manager, Accountant, Finance, Employee
- Permissions: module access + branch restriction + salary visibility control
- Audited actions: salary edits, attendance overrides, payroll approvals, status changes

## Reporting & Dashboards
- HR dashboard: headcount, active/inactive, leave balances, attendance today, late list, contract expiry alerts
- Payroll dashboard: payroll cost, SS totals, dept cost, currency breakdown
- Reports (Export: Excel/PDF): employee lists, leave history/balances, attendance/overtime/absence, payroll register, deductions, SS report, bank transfer file

## Migration
Import: employees (with status), leave balances, optional attendance & payroll history, document mapping  
Must validate required fields, map branch/dept, and log import errors.
