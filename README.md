# HRMS — Lionsfort

A single-tenant HR system for Lionsfort. Business rules are **hardcoded in code**
(not dynamically configured), keeping the app simple and deterministic.

## Stack
- **Backend:** Laravel 12
- **Admin UI:** Filament (single admin panel)
- **Auth:** admin users only (`web` guard); employees do not log in

## What it manages
- **Employees** — profile (name in 3 languages, contact, emergency contacts), department, position, manager, branch, employment status, hire/probation/contract dates, and fixed daily **working hours** (`work_start_time` / `work_end_time`).
- **Organization** — branches, departments, positions, employment statuses, holidays (all editable in the admin).
- **Documents** — per-employee uploads with expiry tracking.
- **Attendance** — fingerprint punches from a single Hikvision device (below). Attendance reports compute late / missing / present against each employee's working hours and the company working days.
- **Leave** — a simple record per employee: date + hours + note (no balances, types, or approval workflow). Managed under **Employees → Leaves**.
- **Activity log** — audited changes across the app.

## Hardcoded rules
- `config/attendance.php` — company **working days** and the single fingerprint **device** (vendor, MAC, IP, port).
- Multi-language (English, Kurdish Sorani, Arabic) via per-record translatable fields; default language in **Settings → General**.

> Removed on this branch (were dynamic, now unused): payroll/salary, social security,
> exchange rates/currency, shift templates, the multi-step leave engine, and the
> employee self-service portal.

## Setup — Hikvision fingerprint device
1. Turn off DHCP.
2. Set a static IP outside the router range, e.g. `192.168.1.200`.
3. Log in via browser: `https://192.168.1.200/`
4. **System Maintenance → Network Service → HTTP Listening**, then set:
   - Event Alarm IP/Domain Name: `hrm.example.com`
   - URL: `/api/hikvision/events`
   - Port: `443`
   - Protocol: `HTTPS`

Punches are received at `POST /api/hikvision/events` and matched to the device by MAC address.
