# Hall Management System (Track A, Laravel)

Hiring assessment for Focus University Admission Coaching. The full brief is in `docs/ASSESSMENT.md` and is the source of truth. Read the relevant section before building any module.

**Deadline:** 23 Oct 2026, 11:59 PM (GMT+6). Must-haves come before anything else. Bonus work cannot make up for a missing must-have.

## Stack
- PHP 8.2+, Laravel 12, MySQL 8 (SQLite for tests)
- Breeze (Blade + Tailwind) for auth and UI, mobile-friendly at 375px
- Pest for feature tests, Pint for PSR-12

## Build order
1. Roles and auth (M1): roles/role_user, pending/active/rejected status, approval flow, middleware that blocks pending users
2. Hall structure and seats (M2): blocks → rooms → seats, allocation with history, student list and profile
3. Complaints (M4, highest priority): tracking ID, BR-06 state machine, assign, comments, overdue flag, private photo route
4. House tutors (M3): block assignment and scoping (BR-08)
5. Notices (M5), Clubs (M6), Dashboard (M7)
6. Seeders matching Section 10 exactly, including the six demo accounts
7. README per Section 11

## Architecture rules
- Thin controllers. Business rules go in `app/Services` or `app/Actions` (SeatAllocationService, ComplaintStatusService, TrackingIdGenerator).
- Validation in Form Requests only, never inline in controllers.
- Authorisation through Policies on every route that changes data. Unauthorised requests return 403. Hiding a button is not enough.
- Statuses are PHP backed enums.
- Use `DB::transaction()` plus `lockForUpdate()` for seat allocation (BR-01, BR-02) and tracking ID generation (BR-09, with a per-year counter row).
- Back up BR-01 and BR-02 with DB constraints where possible.
- Eager-load relationships and paginate every list. No N+1 queries.
- Uploads go through the Storage facade with generated filenames, type and size validated (BR-13). Complaint photos are served via an authorised route, not public storage.
- Never delete allocation history (BR-04).

## Tests (required minimum)
BR-01, BR-02, BR-06 (valid and invalid transitions), BR-08, and at least one 403 test. Write each test when its module is built, not at the end. `php artisan test` must pass.

## Working rules
- Commit after each working unit with a meaningful message. Do not squash.
- Fictional data only. No secrets in the repo; keep `.env.example` up to date.
- Keep `docs/PROGRESS.md` current: done / partly done / not done, plus assumptions. It feeds the README.
- Do not start bonus items (JSON API, Docker, deploy) until M1–M7 work and the tests pass.
