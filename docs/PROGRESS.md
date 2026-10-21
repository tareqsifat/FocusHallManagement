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

- **M4 Complaint management**
  - Students submit category, title, description, optional JPG/PNG photo (≤ 2 MB). A resident's room is attached automatically; an attached student must type a location.
  - Tracking ID `KJH-YYYY-NNNN` from a per-year `complaint_counters` row (BR-09). The row is created with an auto-committed `INSERT IGNORE`, then incremented under `lockForUpdate()` in a short transaction. Checked by hand with 40 parallel processes against MySQL starting from an empty table: 40 unique IDs, no errors. (The first version did the insert inside the locking transaction and deadlocked under load.)
  - `ComplaintStatus` enum owns the BR-06 map. `ComplaintPolicy::transition` decides who may make a move (403); `ComplaintStatusService` decides whether the move is valid and enforces BR-07 notes (422 with a clear message). Every change writes `complaint_status_logs` (from, to, note, who, when).
  - Office Staff / Provost / Super Admin assign to an active House Tutor or Office Staff member. Owner, assignee and hall admins can comment; comments are shown oldest first.
  - Days open and an Overdue badge (open and older than 7 days, BR-10); list filters by status, category, overdue, and search by tracking ID or title.
  - Photos are stored on the private `local` disk under a random name and streamed through `/complaints/{tracking_id}/photo`, which checks the view policy.
  - Tests: BR-06 valid and invalid transitions (datasets), BR-07, reopen window, BR-08 (tutor cannot open, update or comment on unassigned complaints), BR-09 year rollover, BR-13 upload validation and private serving, visibility scoping, overdue flag.

## Partly done
- (none)

## Not done
- M3 House tutors, M5 Notices, M6 Clubs, M7 Dashboard, seeders, README.

## Assumptions
- Complaint transitions by role: hall admins may make any valid move except reopening; an assigned House Tutor may move to Under Review, In Progress and Resolved but not Close or Reject (the brief gives close/reject to the Provost and Office Staff); only the student who filed it may reopen (Resolved → In Progress) and only within 7 days of resolution.
- Reopening clears `resolved_at`; resolution time is measured from submission to the latest resolution.
- Tracking numbers may skip a value if a submission fails after its number was issued. They are never duplicated.
- Complaints can be assigned to active House Tutors or Office Staff only.
- Seats are never deleted, because they carry history. A room's capacity equals its active seats; retired seats are kept but cannot be allocated.
- Allocation date is the day the staff member allocates. The vacate date can be back-dated but not before the allocation date or in the future.
- The student list shows approved (active) students only; pending ones are on the Registrations page.
- Session is stored with a plain hyphen (`2023-24`); an en dash typed by the user is normalised.
- Phone must be an 11-digit Bangladeshi mobile number starting `013`–`019`.
- Departments come from a fixed list so the student list filter stays consistent.
- A rejected applicant keeps their row (registration number stays taken); staff can see rejected applications and the reason.
- Self-service account deletion (Breeze default) is removed because it would delete allocation and complaint history (BR-04).
- Students may change their email only within `@student.du.test`.
