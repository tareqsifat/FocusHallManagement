# Progress

## Done
- **Scaffold**: Laravel 12, Breeze (Blade + Tailwind), Pest, MySQL (`kjh_hall`), timezone Asia/Dhaka.
- **M1 Authentication and registration**
  - `roles` / `role_user` (many-to-many); role rows ship with the migration because they mirror the `RoleName` enum.
  - `users.status` (`UserStatus` enum: pending/active/rejected/inactive) plus `rejection_reason`. Neither is mass assignable.
  - Student self-registration: full name, 10-digit registration no (unique), department (fixed list in `config/hall.php`), session (`2023-24`, consecutive years), BD phone `01[3-9]XXXXXXXX`, `@student.du.test` email (unique), password.
  - New students are Pending and are not logged in after registering. Login refuses pending/rejected/inactive accounts with a clear message; the `active` middleware also ends the session if an account is deactivated mid-session.
  - Staff (Office Staff, Provost, Super Admin) list registrations by status and approve or reject with a reason. Guarded by `UserPolicy`; anyone else gets 403.
  - Tests: registration validation, pending login refusal, approval/rejection, 403 for students.

- **M2 Students, rooms and seat allocation**
  - `blocks` → `rooms` (unique room no per block, floor, capacity 1–6) → `seats` (labels A–F).
  - `seat_allocations` history: allocated/vacated dates, reason, allocated_by, vacated_by. Rows are never deleted (BR-04).
  - `SeatAllocationService` locks the student and seat rows in a transaction and checks BR-01, BR-02 and BR-03. The DB backs this up with unique stored generated columns (`active_seat_id`, `active_student_profile_id`) that are only non-null while an allocation is active.
  - `RoomService` keeps seats in step with capacity: lowering capacity retires free seats (BR-05 refuses going below occupied), raising it restores retired seats first.
  - Office Staff and Super Admin manage blocks/rooms; Provost can view (gates `view-hall` / `manage-hall`). Allocate/vacate: Office Staff, Provost, Super Admin.
  - Student list: search by name or registration no, filter by block, department, session, residency status, paginated. Profile shows details, current seat, history, and allocate/vacate forms for staff. Students can open only their own profile.
  - Tests: BR-01, BR-02 (service and DB constraint), BR-03, BR-04, BR-05, 403s, list filters.

## Partly done
- (none)

## Not done
- M3 House tutors, M4 Complaints, M5 Notices, M6 Clubs, M7 Dashboard, seeders, README.

## Assumptions
- Seats are never deleted, because they carry history. A room's capacity equals its active seats; retired seats are kept but cannot be allocated.
- Allocation date is the day the staff member allocates. The vacate date can be back-dated but not before the allocation date or in the future.
- The student list shows approved (active) students only; pending ones are on the Registrations page.
- Session is stored with a plain hyphen (`2023-24`); an en dash typed by the user is normalised.
- Phone must be an 11-digit Bangladeshi mobile number starting `013`–`019`.
- Departments come from a fixed list so the student list filter stays consistent.
- A rejected applicant keeps their row (registration number stays taken); staff can see rejected applications and the reason.
- Self-service account deletion (Breeze default) is removed because it would delete allocation and complaint history (BR-04).
- Students may change their email only within `@student.du.test`.
