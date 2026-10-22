# Hall Management System: Kabi Jasimuddin Hall

**Track A: Laravel.** A Laravel 12 application that covers all must-have modules (M1–M7) of the assessment brief, with server-side authorisation on every action and Pest feature tests for the business rules.

All data is fictional. The hall name sets the scene only.

---

## Contents

1. [Tech stack](#tech-stack)
2. [Setup](#setup)
3. [Demo accounts](#demo-accounts)
4. [Screenshots](#screenshots)
5. [Status: done, partly done, not done](#status-done-partly-done-not-done)
6. [Assumptions and decisions where the brief left a gap](#assumptions-and-decisions-where-the-brief-left-a-gap)
7. [Design decisions and trade-offs](#design-decisions-and-trade-offs)
8. [Data model and deviations from Section 7](#data-model-and-deviations-from-section-7)
9. [Business rules: where each is enforced and tested](#business-rules-where-each-is-enforced-and-tested)
10. [Time spent](#time-spent)
11. [AI tools used](#ai-tools-used)

---

## Tech stack

| Part | Version |
|---|---|
| PHP | 8.3+ (the brief asks for 8.2+; the locked dependencies, Symfony 7.4, Pest 4 and PHPUnit 12, need 8.3) |
| Laravel | 12 |
| Database | MySQL 8+ (developed on MySQL 9.6). Tests run on in-memory SQLite by default and also pass on MySQL. |
| Auth and UI | Laravel Breeze (Blade + Tailwind CSS), Alpine.js from Breeze for the mobile menu |
| Front-end build | Node 18+ and Vite |
| Tests | Pest 4 (feature tests) |
| Code style | Laravel Pint (PSR-12) |

No extra Composer or npm packages beyond the Laravel and Breeze defaults.

---

## Setup

You need PHP 8.3+, Composer, Node 18+ and a MySQL server.

```bash
# 1. Clone and install
git clone <repository-url> hall-management
cd hall-management
composer install
npm install
npm run build

# 2. Environment
cp .env.example .env
php artisan key:generate
# .env expects MySQL on 127.0.0.1:3306, database kjh_hall, user root, empty password.
# Change DB_USERNAME / DB_PASSWORD if yours differ.

# 3. Database
mysql -uroot -e "CREATE DATABASE kjh_hall CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate --seed          # about 3 seconds

# 4. Run
php artisan serve                   # http://localhost:8000

# 5. Test
php artisan test                    # in-memory SQLite, no setup needed
```

`/` opens the public notice board. Use **Log in** at the top right.

**Running the tests on MySQL as well (optional).** The tests use database row locks and generated columns, which behave differently on MySQL and SQLite, so they were run on both during development:

```bash
mysql -uroot -e "CREATE DATABASE kjh_hall_test"
DB_CONNECTION=mysql DB_DATABASE=kjh_hall_test php artisan test
```

**No `storage:link` step.** Uploaded files (complaint photos, notice PDFs, club logos) are stored on the private `local` disk and served by routes that check permissions.

**Lint:** `./vendor/bin/pint --test`

**Docker (alternative to the steps above).** Only Docker is needed:

```bash
docker compose up -d --build        # http://localhost:8000, demo data seeded on first start
```

To deploy on a server, see [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). On a fresh Ubuntu server, `deploy.sh` sets up Docker, MySQL and HTTPS (Caddy), and GitHub Actions deploys every push to `main` with no downtime.

---

## Demo accounts

Every account uses the password **`password`**.

| Role | Email | What to try |
|---|---|---|
| Super Admin | `admin@kjh.test` | Everything; update staff roles under **Users**; create a club and pick its president |
| Provost | `provost@kjh.test` | Dashboard, close or reject complaints, manage house tutors |
| Hall Office Staff | `office@kjh.test` | Approve the 5 pending registrations, allocate and vacate seats, assign complaints |
| House Tutor (North block) | `tutor@kjh.test` | Sees only North block students and complaints assigned to them. "Main gate left open after midnight" is anonymous: the tutor sees "Anonymous student", while the Provost sees the name. |
| Club Admin (also a student) | `clubadmin@student.du.test` | President of the Debating Club: approve requests, post a Club notice |
| Student (resident) | `student@student.du.test` | Lives in North block, has complaints in several states, one pending club request |

Other seeded logins, also with password `password`: `office2@kjh.test` and `tutor2@kjh.test` to `tutor5@kjh.test`. Seeded students log in as `s<registration no>@student.du.test`. For example, `s2020331055@student.du.test` is a pending student, so the login is refused, and `s2023331040@student.du.test` is an attached (non-resident) student.

**Seed data (Section 10):**
- 3 blocks (North, South, Extension) with 32 rooms of capacity 2–4, giving 107 seats.
- 61 students: 40 residents spread across the blocks, 15 attached (3 of them former residents with allocation history), 5 pending and 1 rejected.
- 5 house tutors covering all three blocks.
- 4 clubs, each with a president, members and pending requests.
- 24 complaints in every status. 6 are overdue, one was reopened by its student, and two are anonymous.
- 12 notices: 2 drafts, 2 pinned and 2 with a PDF.

A few names and one notice are in Bangla script to show Unicode support.

---

## Screenshots

Ten screenshots of the seeded demo data are in [`docs/screenshots/`](docs/screenshots). Files marked `375px` were taken at a 375 px wide phone viewport; the rest at 1280 px.

| # | Screen | Logged in as |
|---|---|---|
| 1 | [Public notice board](docs/screenshots/01-public-notice-board.png): pinned first, search and category filter | Guest |
| 2 | [Dashboard](docs/screenshots/02-dashboard-office-staff.png): seats, occupancy, pending registrations, open complaints by category, overdue, average resolution time | Office Staff |
| 3 | [Pending registrations](docs/screenshots/03-pending-registrations.png) with approve and reject-with-reason | Office Staff |
| 4 | [Student list](docs/screenshots/04-student-list-search-filters.png) with search, block, department, session and status filters | Office Staff |
| 5 | [Complaint detail](docs/screenshots/05-complaint-detail-timeline-overdue.png): `KJH-YYYY-NNNN` ID, overdue flag, allowed next statuses, assignment, comments and status history | Office Staff |
| 6 | [Room view](docs/screenshots/06-room-seats-occupancy.png): occupied and free seats | Office Staff |
| 7 | [Student profile](docs/screenshots/07-student-profile-allocation-history-375px.png): current seat and allocation history, at 375 px | Student |
| 8 | [Submit a complaint](docs/screenshots/08-submit-complaint-375px.png): room filled in automatically, optional photo, anonymous option, at 375 px | Student |
| 9 | [Assigned complaints](docs/screenshots/09-house-tutor-assigned-complaints-375px.png): only complaints assigned to this tutor; the anonymous one shows "Anonymous student" and the block only, at 375 px | House Tutor |
| 10 | [Club membership requests](docs/screenshots/10-club-admin-membership-requests.png): approve, reject and remove | Club Admin |

---

## Status: done, partly done, not done

### Done

| Module | What works |
|---|---|
| **M1 Auth and registration** | Login and logout for all roles. Student self-registration with every required field and its format check. New accounts are Pending and cannot log in until approved; rejected and deactivated accounts are refused with the reason. Staff approve, or reject with a reason. |
| **M2 Students, rooms, seats** | Blocks, rooms and seats with create, edit and list. Occupied and free seats per room. Allocate and vacate (with date and reason), keeping the full history. Student list with search, filters and pagination. Student profile with current room and history. |
| **M3 House tutors** | Provost and Super Admin create, edit, deactivate and reactivate tutors and assign them to one or more blocks. A tutor sees only students in their blocks and the complaints assigned to them. |
| **M4 Complaints** | Submit with an optional photo. Room is filled in for residents; attached students type a location. `KJH-YYYY-NNNN` tracking IDs that restart each year. BR-06 state machine with a log of who, when and note for every change. Assignment, comments in time order, days open and an overdue flag, filters. Photos served only to people allowed to see the complaint. |
| **M5 Notices** | Public board (no login) showing published notices only, pinned first then newest, with search, category filter and pagination. Detail page and PDF download. Management page including drafts. |
| **M6 Clubs** | Public directory with logo, admin and member count. Join requests, approve, reject and remove. Super Admin creates clubs and assigns admins. |
| **M7 Dashboard** | Seats, occupancy %, pending registrations, open complaints by category, overdue count and average resolution time (last 30 days). House tutors see complaint figures for their blocks. |
| **Users and roles** | Super Admin only: a **Users** screen listing staff accounts with an **Update role** button to set Super Admin, Provost and Hall Office Staff. Everyone else gets 403. |
| **Anonymous complaints (bonus)** | A student can tick "Submit anonymously". Only the Provost and Super Admin (and the student) see who filed it. Everyone else sees "Anonymous student" in the reporter field, comments and status log, and sees only the block, not the room number. |
| **Seeders** | Section 10 data and all six demo accounts with `php artisan migrate --seed`. |
| **Tests** | 174 Pest tests. They cover BR-01 to BR-13 (BR-06 with valid and invalid transition datasets) and 403 checks for each module. A seeder test verifies the Section 10 counts and that every demo account logs in. |
| **Mobile at 375 px** | Built mobile-first with Tailwind: stacked cards instead of wide tables, and a hamburger menu below 768 px. Checked in headless Chrome at a 375 px viewport: 32 pages across all six roles (lists, detail pages and forms), none with horizontal scrolling. |
| **Demo (Section 11, item 4)** | Ten screenshots in `docs/screenshots/`, listed under [Screenshots](#screenshots). |

### Partly done

Nothing in the must-have scope.

- **Docker, CI and deployment (bonus):** a Dockerfile (PHP 8.3.36), local `docker compose`, and a GitHub Actions pipeline (tests → image on GHCR → zero-downtime release on a DigitalOcean Droplet) are written but not yet verified end to end. See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

### Not done

- **Bonus items:** JSON API (Section 8), events, polls, email notifications, Bangla/English toggle, CSV export, activity log. All data fields already accept Bangla text.

---

## Assumptions and decisions where the brief left a gap

The brief asks candidates to make a reasonable assumption and write it down when something is unclear. These are the gaps found while building and how each was settled.

### Registration and accounts

| Gap | Decision |
|---|---|
| Format of "session (e.g. 2023–24)" | Stored as `2023-24`. An en dash typed by the user is converted to a hyphen. The two years must be consecutive, so `2023-25` is rejected. |
| "Bangladeshi format, 01XXXXXXXXX" | 11 digits starting `013` to `019` (the mobile operator prefixes). |
| Department: free text or fixed? | A fixed list in `config/hall.php`, so the student list's department filter stays consistent. |
| What happens to a rejected applicant? | The account and reason are kept, so staff can still see it. The registration number stays taken. The applicant sees the reason when trying to log in. |
| Breeze lets users delete their own account | Removed. Deleting a student would destroy seat and complaint history, which BR-04 says must be kept. |
| What can a student change under "Maintain profile"? | Name, phone and password. Registration number, department, session and email come from the university record, so they are shown read-only and the student update ignores them. Phone uses the same Bangladeshi format check as registration. The staff profile endpoint, which edits email, returns 403 for students. Staff can edit their own name and email. |
| "Manage users and roles" (Super Admin) | The roles are the six fixed ones in the brief; nobody creates new roles. Only the Super Admin can update a user's roles, on the **Users** screen. It sets Super Admin, Provost and Hall Office Staff. Student, House Tutor and Club Admin come with a record of their own, so they stay with registration, the house tutor screen and club admin assignment. Student accounts cannot be given staff roles. A Super Admin cannot remove their own Super Admin role, and every user keeps at least one role. |

### Rooms and seats

| Gap | Decision |
|---|---|
| How seats relate to capacity | A room has one seat per capacity slot, labelled A, B, C… Capacity can be 1 to 6. |
| What happens to seats when capacity is lowered (BR-05)? | Seats are never deleted because they carry history. Lowering capacity retires free seats, keeping occupied ones even if their letter is higher: going from 3 to 2 with A and C occupied retires B. Raising capacity brings retired seats back before adding new letters. |
| Allocation and vacate dates | Allocation is dated the day staff allocate. The vacate date can be back-dated, but not before the allocation date and not in the future. |
| Who appears in the student list | Approved students only. Pending applicants are on the Registrations page. |
| Provost and hall structure | The Provost can view blocks and rooms but not edit them ("View" in the matrix). The Provost can allocate and vacate seats. |

### House tutors

| Gap | Decision |
|---|---|
| "Deactivate" a house tutor | Sets `house_tutors.is_active = false` and the account status to `inactive` in one transaction. A deactivated tutor cannot log in, cannot be assigned complaints and sees no students. Existing assignments and history stay. Reactivating restores both. |
| Which students are "in" a tutor's block? | Students whose **current** seat is in one of the tutor's blocks. Attached students and former residents are not in any block. |
| Is a tutor's email restricted to a domain? | No. Staff and tutors use any unique email (the demo accounts use `@kjh.test`). |

### Complaints

| Gap | Decision |
|---|---|
| Which status changes can a house tutor make? | The matrix says "assigned only" but not which moves. A tutor assigned to the complaint may move it to Under Review, In Progress and Resolved. Closing and rejecting are left to Office Staff, Provost and Super Admin, as the brief's role descriptions give those decisions to the Provost and hall office. |
| Who may reopen (Resolved to In Progress)? | Only the student who filed it, and only within 7 days of resolution. Staff cannot reopen. |
| Unauthorised move vs invalid move | A move the user is not allowed to make returns **403**. A move the state machine does not allow (e.g. Submitted to Closed) returns a **422-style validation error** with a clear message, e.g. "A complaint cannot move from Submitted to Closed." |
| Resolution time after a reopen | Reopening clears the resolved date. Resolution time is measured from submission to the **latest** resolution. Closed complaints keep their resolved date and still count. |
| Who can be assigned a complaint? | Active Office Staff, or an active house tutor **of the complaint's block**, so the complaint also counts on that tutor's dashboard. A complaint with no room (filed by an attached student) can go to any active tutor. |
| Can a finished complaint still be changed? | Closed and Rejected are final: no status change, reassignment or comment (403). A Resolved complaint stays open to comments and assignment because it can still be closed or reopened. |
| "Open" for the dashboard and overdue | Submitted, Under Review and In Progress. Overdue means open and submitted more than 7 days ago (BR-10). |
| Days open for finished complaints | Counted from submission to resolution. Rejected complaints show their submission date instead. |
| Anonymous complaints: who sees the name? | Only the **Provost and Super Admin**, as Section 5.2 says, plus the student who filed it. Office Staff and the assigned house tutor see "Anonymous student" in the reporter field, the comments and the status log (submission and reopen entries). |
| Anonymous complaints: is the room hidden? | The **room number is hidden**. With 2–4 students per room it would come close to naming the student, so people who cannot see the reporter see only the block (e.g. "North block"). An attached student's typed location is still shown, because staff need it to fix the problem. |
| Gaps in tracking numbers | If a submission fails after its number was issued, that number is skipped. IDs are never duplicated, which is what BR-09 requires (see the design decisions below). |

### Notices

| Gap | Decision |
|---|---|
| Notice body format | Plain text with line breaks kept. No HTML, which rules out stored XSS. |
| What a draft looks like to the public | Draft pages and attachments return **404**, not 403, so the public cannot tell a draft exists. This applies to everyone, including the people who can edit the draft: drafts are seen only on the notice management page and its edit form (BR-11). |
| Which notices a club admin can manage | Only Club-category notices they wrote themselves. Notices are not linked to a specific club. |
| Can notices be deleted? | Yes. Unlike seat allocations and complaints, they are not history. |
| `published_at` | Set the first time a notice is published and kept on later edits, so the board order does not jump. |

### Clubs

| Gap | Decision |
|---|---|
| Who creates clubs and assigns admins? | **Only the Super Admin**, as the matrix and M6 say. The Provost has no club permissions. |
| Must a club have an admin when created? | No. The admin picker is on the create form but optional, so a club can be set up before its president is chosen. Until then the Super Admin can handle membership requests. |
| Who can be a club admin? | **A club's president (its admin) must be an approved student.** |
| How many admins per club? | **At most two** (the president plus one more). Configurable as `hall.club_max_admins`. |
| Can a student lead several clubs? | **No. A student can be admin of only one club.** Enforced by validation and by a unique index on `club_admins.user_id`. |
| Club Admin role | Follows the assignment: a student gets the Club Admin role when made admin and loses it when removed. |
| Can a rejected or removed student ask again? | Yes. BR-12 only forbids a second request while one is pending or active. |

### Dashboard

| Gap | Decision |
|---|---|
| "House tutor: limited to their blocks" | Complaints raised from rooms in their blocks. Complaints from attached students have no room, so they appear only on the hall-wide dashboard. |
| What students and club admins see at `/dashboard` | The matrix gives them no dashboard, so they get a personal home page instead: their seat, open complaints and clubs. It shows no hall figures. |

---

## Design decisions and trade-offs

**Thin controllers, rules in services.** Each business rule lives in one place under `app/Services`: `SeatAllocationService`, `RoomService`, `ComplaintService`, `ComplaintStatusService`, `TrackingIdGenerator`, `HouseTutorService`, `NoticeService`, `ClubService`, `ClubMembershipService` and `DashboardService`. Student registration is `app/Actions/RegisterStudent`. Controllers validate with a Form Request, authorise, call one service method and redirect.

**Authorisation on the server, on every route.** Policies cover users, students, allocations, complaints, house tutors, notices, clubs and memberships. Gates (`view-hall`, `manage-hall`) cover the hall structure, which no single user owns. Routes use `->can(...)`, so an unauthorised request gets 403 before the controller runs. Hiding buttons in Blade uses the same policy methods. Two rules are each written once and reused by both the list query and the policy check, so the list and the detail page can never disagree: `StudentProfile::scopeVisibleTo` and `Complaint::scopeVisibleTo`.

**"Who may" vs "is it valid".** For complaint status changes, `ComplaintPolicy::transition` answers *who may attempt this move* (403). `ComplaintStatusService` answers *whether the move is valid* (BR-06) and whether the required note is there (BR-07), returning a validation error. The transition map lives on the `ComplaintStatus` enum (`allowedTransitions()`), so the rules are in one readable place.

**Database constraints back up the important rules.** On top of the checks in the services:

- **BR-01 and BR-02:** `seat_allocations` has two stored generated columns, `active_seat_id` and `active_student_profile_id`. Each equals its foreign key only while the allocation is active and is `NULL` once vacated. Both have unique indexes, and NULLs never collide, so the database itself refuses a second active seat for a student or a second student in a seat. The same works on MySQL and SQLite.
- **BR-12:** `club_memberships` uses the same idea (`open_student_profile_id`, unique together with `club_id`).
- **Club rules:** a unique index on `club_admins.user_id` enforces "one club per admin".

**Locking.** Seat allocation locks the student and seat rows (`lockForUpdate()`) inside a transaction, so two staff members clicking at the same moment cannot both pass the checks. Room capacity changes and membership decisions also lock the rows they change.

**Tracking IDs (BR-09).** A per-year counter row in `complaint_counters` is incremented under a row lock. The first version created the row and locked it in the same transaction. Under a real concurrency test (20 parallel processes against MySQL) no ID was ever duplicated, but most requests failed with deadlocks: the shared lock taken by `INSERT IGNORE` blocked the exclusive lock of the other transactions. The final version creates the year's row with an auto-committed `INSERT IGNORE` and then increments it in a short transaction of its own. Re-tested twice with 40 parallel processes starting from an empty table, each run gave 40 unique IDs and no errors.

The trade-off: the ID is issued before the complaint's own transaction, so a submission that fails afterwards leaves a gap in the sequence. Gaps are allowed; duplicates are not. A new year gets its own row, so the sequence restarts automatically.

**Statuses are PHP backed enums:** `UserStatus`, `RoleName`, `ResidencyStatus`, `ComplaintStatus`, `ComplaintCategory`, `NoticeStatus`, `NoticeCategory` and `MembershipStatus`. Labels and badge colours live on the enums.

**Mass assignment.** Fields that only a service may change are left out of `$fillable`, for example `users.status`, `rooms.capacity`, `complaints.status` and `tracking_id`, and `house_tutors.is_active`. `SeatAllocation` and `ClubMembership` allow no mass assignment at all. Services pass explicit field lists to `create` and `fill` rather than relying on `$fillable` to drop extra keys. The seeders, which run with mass-assignment protection off, exposed the earlier reliance on `$fillable`, and it was fixed.

**Uploads (BR-13).** Each upload is checked by extension *and* by sniffed MIME type, with a size limit:

| Upload | Allowed type | Size limit |
|---|---|---|
| Complaint photos | JPG/PNG | 2 MB |
| Notice attachments | PDF | 5 MB |
| Club logos | JPG/PNG | 1 MB |

Files are stored with Laravel's random names on the private disk, never under the user's file name. They are streamed through routes that check permissions, with `X-Content-Type-Options: nosniff`. Notice PDFs download as `notice-{id}.pdf`.

**History is never deleted.** Allocations, complaint status logs and comments are only ever added. Seats are retired, not deleted. Foreign keys use `restrictOnDelete` where deleting would lose history. Account deletion is disabled.

**No N+1 queries.** Lists eager-load what they display and are paginated. `Model::preventLazyLoading()` is on outside production, so an accidental N+1 throws during development and tests.

**Seeders use the real services.** Records are created through the same services the app uses, with the clock moved back via `Carbon::setTestNow`. Allocation history, status timelines, overdue flags, tracking ID order and dashboard figures therefore all agree, and the seeders double as an end-to-end check of the services.

**UI.** Plain Blade components on top of Breeze, mobile-first:
- Lists are stacked cards rather than wide tables.
- Filters are ordinary GET forms, so results can be bookmarked.
- Validation errors appear under each field, and success messages appear as a banner.
- The dashboard uses stat tiles and a single-colour bar list with printed counts, backed by a real `<table>` for screen readers.

**Trade-offs accepted:**
- **Dashboard average:** the average resolution time is computed in PHP over the last 30 days of resolved complaints rather than in SQL, to stay portable between MySQL and SQLite. That is fine for a single hall's volume and is marked in the code.
- **Club admin picker:** it is a plain multi-select of approved students (about 60 in the seed data). A searchable picker would be the upgrade for a hall with thousands of students.

---

## Data model and deviations from Section 7

The suggested model is followed closely. The differences:

| Table | Change | Why |
|---|---|---|
| `users` | Added `rejection_reason` | Rejection needs a reason that the applicant sees at login |
| `roles` / `role_user` | Role rows are inserted by the migration | They mirror the `RoleName` enum, so they must always exist |
| `seats` | Added `is_active` | Lowering capacity retires seats instead of deleting their history (BR-05) |
| `seat_allocations` | Added `vacated_by` and generated columns `active_seat_id`, `active_student_profile_id` | Audit of who vacated; DB-level BR-01/BR-02 |
| `house_tutors` | No name or email columns | Name and email live on `users`, which the tutor logs in with |
| `block_house_tutor` | Pivot | Many-to-many tutors and blocks |
| `complaints` | Added `resolved_at` | Resolution time (BR-10) and the 7-day reopen window. `is_anonymous` is used for the anonymous-complaint bonus. |
| `complaint_counters` | New table (`year`, `last_number`) | Collision-free tracking IDs per year (BR-09) |
| `club_admins` | Pivot with unique `user_id` | "Has admins", at most one club per admin |
| `club_memberships` | Added `decided_at` and generated `open_student_profile_id` | Audit; DB-level BR-12 |
| `notices` | As suggested | Composite index on `(status, is_pinned, published_at)` for the public board |

Indexes exist on `registration_no`, `tracking_id`, every `status` column, `category` and the foreign keys.

---

## Business rules: where each is enforced and tested

| Rule | Enforced in | Tests |
|---|---|---|
| BR-01 one active seat per student | `SeatAllocationService::allocate` plus unique generated column | `SeatAllocationTest` |
| BR-02 one student per seat, room never over capacity | Same service plus unique generated column; only active seats are allocatable | `SeatAllocationTest`, `HallStructureTest` |
| BR-03 only approved students allocate, complain, join | Login gate plus `active` middleware; service and policy checks | `SeatAllocationTest`, `ComplaintTest`, `ClubTest` |
| BR-04 vacate keeps history, student back to Attached | `SeatAllocationService::vacate` | `SeatAllocationTest` |
| BR-05 capacity not below occupied | `RoomService::update` | `HallStructureTest` |
| BR-06 complaint transitions | `ComplaintStatus::allowedTransitions`, `ComplaintStatusService` | `ComplaintStatusTest` (valid and invalid datasets) |
| BR-07 reason / resolution note | `ComplaintStatusService` | `ComplaintStatusTest` |
| BR-08 tutor scope | `ComplaintPolicy`, `StudentProfile::scopeVisibleTo` | `ComplaintStatusTest`, `HouseTutorTest` |
| BR-09 unique tracking IDs | `TrackingIdGenerator` (locked per-year counter) plus unique index | `ComplaintTest` (format, year rollover); manual 40-process MySQL test |
| BR-10 resolution time, overdue | `Complaint::scopeOverdue`, `DashboardService` | `ComplaintTest`, `DashboardTest` |
| BR-11 drafts never public | `Notice::scopePublished`, `NoticePolicy::view` (404) | `NoticeTest` |
| BR-12 own club only, no duplicate requests | `ClubPolicy`, `ClubMembershipService` plus unique generated column | `ClubTest` |
| BR-13 safe uploads | Form Requests (type and size), random names, private disk, routes that check permissions | `ComplaintTest`, `NoticeTest`, `ClubTest` |

---

## Time spent

_To be filled in by the candidate._

---

## AI tools used

- **Claude Code (Anthropic, Claude Opus 5.5)** was used as a pair programmer throughout. It read the brief, proposed the data model and build order, and wrote the migrations, models, services, policies, controllers, Blade views, Pest tests and seeders, module by module. It ran the tests on SQLite and MySQL, ran Pint, and committed after each working unit.
- **The candidate** reviewed each module before the next one started and made the product decisions where the brief was open. For example: adding `is_active` to house tutors; requiring club presidents to be students, with at most two admins per club and one club per student; letting the Super Admin pick admins while creating a club; and running the test suite against MySQL.
- The candidate also asked for the anonymous-complaint bonus once the must-haves were done.
- **Review round.** The candidate then asked Claude Code to assume the app was wrong and try to prove it against the brief. It wrote temporary probe tests (deleted afterwards) and reported each gap it confirmed. This led to the Users and roles screen, final Closed/Rejected complaints, same-block tutor assignment, hiding the room number on anonymous complaints, drafts returning 404 for everyone, and the composer fix for PHP 8.3.
- **Screenshots and the 375 px check** were taken by Claude Code with headless Chrome (via `puppeteer-core`, installed outside this repository) against a seeded copy of the database, logged in as each demo account.
- **Problems found by testing rather than by reading:** a tracking-ID deadlock under concurrent load, found by running 20 to 40 parallel processes against MySQL; a route-ordering bug (`/rooms/create` matched by `/rooms/{room}`); and services relying on `$fillable` to drop extra keys, found when the seeders ran unguarded.
