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

## Partly done
- (none)

## Not done
- M2 Hall structure and seats, M3 House tutors, M4 Complaints, M5 Notices, M6 Clubs, M7 Dashboard, seeders, README.

## Assumptions
- Session is stored with a plain hyphen (`2023-24`); an en dash typed by the user is normalised.
- Phone must be an 11-digit Bangladeshi mobile number starting `013`–`019`.
- Departments come from a fixed list so the student list filter stays consistent.
- A rejected applicant keeps their row (registration number stays taken); staff can see rejected applications and the reason.
- Self-service account deletion (Breeze default) is removed because it would delete allocation and complaint history (BR-04).
- Students may change their email only within `@student.du.test`.
