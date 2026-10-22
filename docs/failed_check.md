# Failed check: assume the app is wrong, then try to prove it

**Method.** I started from the assumption that the app gets everything wrong, then listed every way it could break the brief (`docs/ASSESSMENT.md`) or the build rules (`docs/CLAUDE.md`). I tested each claim against the code, the test suite, 27 temporary probe tests (deleted afterwards), a direct upload-validation script and Composer.

- A claim that **held** is a failure in the app. Each one is listed in section 1 with its proof.
- A claim that **did not hold** means the app is correct on that point (section 3).

This is the second round. It re-checks every finding from the first round against the current working tree (uncommitted changes included).

**Baseline (PHP 8.3.30):** `php artisan test` gives **174 passed (698 assertions)**. `vendor/bin/pint --test` passes.

## Verdict

The business rules, the permission matrix and the security basics survived every attack. All 13 rules held, and so did the 12 extra attack probes. Most first-round findings are fixed in the working tree.

What still fails is mostly **around** the code rather than in it:

1. **The committed code does not install.** The fixes are uncommitted, and even with them the app needs PHP 8.3, while the brief says 8.2+ and this machine's own `php` is 8.2.
2. **Required deliverables are missing:** screenshots or video, time spent, and a cleaned-up AI section.
3. **Two product gaps a reviewer will notice:** students cannot maintain their profile at all, and a departed staff member can never be locked out.

---

## 1. Confirmed failures

### F1. CRITICAL: the committed lockfile does not install on PHP 8.2 or 8.3

- `HEAD:composer.lock` pins Symfony **8.1** (`symfony/clock v8.1.9` and others), which needs PHP **≥ 8.4.1**.
- The fix (Symfony 7.4, `"php": "^8.3"`) is only in the **uncommitted** working tree. A reviewer cloning the repo today gets the broken lockfile.
- The brief lists *"the project does not run from the README instructions"* as an **automatic concern**.
- **Fix:** commit `composer.json` and `composer.lock` now, then do a fresh clone → `composer install` → `migrate --seed` → `php artisan test`.

### F2. HIGH: even after F1, the app refuses to start on PHP 8.2, which the brief allows

- The brief says *"PHP 8.2+"*. The README now says 8.3+, which is honest, but a reviewer on 8.2 still cannot run it.
- Proof on this machine: `php` on PATH is **XAMPP PHP 8.2.12**, and every `php artisan …` command dies:
  ```
  Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.3.0". You are running 8.2.12.
  ```
- `composer why-not php 8.2.12` shows that **only dev tools** block 8.2: Pest 4 and its plugins, PHPUnit 12 / sebastian/*, Pint 1.32 and paratest 7.20. Laravel 12 and Symfony 7.4 both run on 8.2.
- Because `platform_check.php` is generated from *all* installed packages, a dev-only requirement still stops `artisan serve` and `migrate`.
- **Fix:** move the dev tools to 8.2-compatible versions (Pest 3 / PHPUnit 11, an 8.2-compatible Pint), add `"config": {"platform": {"php": "8.2.0"}}`, set `"php": "^8.2"`, re-lock, and run the suite on 8.2 and 8.3. If you'd rather not, keep 8.3+ but say clearly at the top of the README that **8.2 will not work**.

### F3. MEDIUM: a large part of the work is uncommitted, so the README describes code the reviewer won't see

- `git status` shows 26 modified files and 7 new ones: the Users screen, `UserRoleService`, `UserRoleTest`, the anonymity, notice and complaint fixes, the composer fix and their tests.
- The README already claims *"174 Pest tests"* and a **Users** screen. `HEAD` has neither.
- **Fix:** commit in meaningful units (composer fix; users and roles screen; complaint lock and assignment rules; anonymous room hiding; draft notice visibility; student profile). Keep `failed_check.md` out of the repo, or delete it before submitting.

### F4. LOW (was MEDIUM): one Section 11 deliverable is still open

- ~~No screenshots or video~~ **Fixed:** 10 screenshots in `docs/screenshots/` (desktop and 375 px), listed in the README's new Screenshots section.
- ~~"AI tools used" placeholder~~ **Fixed:** placeholder removed; the section now also covers the review round and how the screenshots were taken.
- **README "Time spent"** still says *"To be filled in by the candidate."* The candidate is filling this in.

### F5. MEDIUM: students cannot "maintain" their profile at all

- Table 3 gives the Student the task *"Maintain profile"*.
- The first round found that students could only edit name and email, and suggested adding phone. The change went the other way: `ProfileUpdateRequest::authorize()` now returns `false` for every student, and the profile page is read-only.
- Probe: a student's `PATCH /profile` returns **403**, and no route lets a student change their phone.
- The README documents this as a decision, but it directly contradicts the brief's wording, and phone numbers really do change.
- **Fix:** let students edit **phone** (and probably name) through their own Form Request, and keep registration number, department, session and email locked.

### F6. MEDIUM: a departed staff member can never be locked out

- The matrix gives the Super Admin *"Manage users and roles"*. The new Users screen only toggles three roles. There is no **create** and no **deactivate** (probe: no `POST /users`, no toggle or status route; only the two `users` routes exist).
- Combined with the rule *"A user must keep at least one role"* (`UpdateUserRolesRequest::after`), this means that **if an Office Staff member or the Provost leaves, the Super Admin cannot remove their access.** Their last role cannot be removed, and their account cannot be deactivated. Swapping the role still leaves them with a staff role.
- A new Office Staff account cannot be created from the UI either. The only path is to create a *house tutor* and then add Office Staff to it.
- **Fix:** add activate/deactivate to the Users screen (the `inactive` status and the `active` middleware already exist), and ideally a small "add staff account" form.

### F7. LOW: a club admin can pin their own notice above official hall notices

- `NoticeService::save` accepts `is_pinned` from anyone who can publish, and the checkbox is shown to club admins.
- Probe: a club admin posts a pinned Club notice, and it appears **above** a Hall Office notice on the public board.
- **Fix:** only hall admins may pin (ignore or reject `is_pinned` for others, and hide the checkbox).

### F8. LOW: a club admin can approve their own membership request

- Probe: a club admin requests to join their own club, then approves it. The membership becomes Active with `decided_by` = themself.
- **Fix:** refuse a decision on your own request, or make club admins members automatically.

### F9. LOW: deactivating a house tutor has side effects

- **It locks out other roles.** `HouseTutorService::setActive(false)` sets the whole **account** to `inactive`. If that user also holds Office Staff (now possible through the Users screen), their staff access is gone too. Probe confirmed: login refused.
- **Open complaints are stranded.** A deactivated tutor keeps their open complaints assigned (probe confirmed). Nobody is warned, and the complaint list still shows the old assignee.
- **Fix:** only deactivate the account when House Tutor is the user's only role, and show or unassign open complaints when a tutor is deactivated.

### F10. LOW: assigning a complaint leaves no audit trail

- Probe: after an assignment, `complaint_status_logs` has no new row, and `complaints` has no `assigned_by` / `assigned_at`.
- Status changes record who, when and a note, but reassignment silently overwrites `assigned_to`. You cannot tell who moved a complaint away from a tutor.
- **Fix:** log assignments (a log row or columns), or show them in the timeline.

### F11. LOW: smaller gaps

| Gap | Probe | Note |
|---|---|---|
| Searching the middle digits of a registration number finds nothing | confirmed | `StudentController` uses a prefix match (`"{$search}%"`) for `registration_no` but a contains match for names. |
| Editors can no longer preview a draft or open its PDF | confirmed (404 for Office Staff) | A side effect of the BR-11 fix. The edit form only says "A PDF is attached", so a wrong upload can't be checked until it is public. |
| Unpublish → republish keeps the old `published_at` | confirmed: a re-issued notice sorts below last week's | Documented as intentional, but a re-issued notice appears buried. |
| A room-less complaint assigned to a tutor is in their list but not on their dashboard | confirmed (`open_total` = 0) | Documented in the README. Mention it in the demo so it doesn't look like a bug. |
| Registration accepts session `2099-00` | confirmed | The consecutive-year check passes on the century wrap. Consider a sane year range. |
| A house tutor can be created with an `@student.du.test` email | confirmed | Documented. Harmless but odd. |
| The Super Admin can remove the Provost role from the only Provost | confirmed | Nothing guarantees one Provost exists. A judgement call. |
| The 10,000th complaint in a year gets `KJH-2026-10000` | confirmed | Breaks the 4-digit format. Unrealistic for one hall; mention it or ignore it. |

---

## 2. Fixed since the first round (re-checked)

| First-round finding | Now |
|---|---|
| No "Manage users and roles" screen | **Partly fixed:** role editing exists, but create and deactivate are missing (see F6) |
| Editors could see drafts at the public URL (BR-11) | **Fixed:** `NoticePolicy::view` = published only, 404 for everyone |
| Anonymous complaints showed the room number | **Fixed:** viewers without `viewReporter` see the block only (list and detail) |
| Closed or Rejected complaints could be reassigned or commented on | **Fixed:** `ComplaintStatus::isLocked()` in the `assign` and `comment` policies |
| A complaint could be assigned to a tutor of another block | **Fixed:** `User::scopeAssignableTo` (probe A8: other-block tutor, Provost and student all refused) |
| Students could not edit phone | **Changed, not fixed:** now nothing is editable (see F5) |
| Tutor cannot reject: undocumented | **Fixed:** documented in the README assumptions |

---

## 3. Claims that did not hold (the app is correct here)

| # | Claim: "the app gets this wrong" | Evidence it doesn't |
|---|---|---|
| 1 | A pending, rejected or deactivated user can log in or keep a session | `LoginRequest::authenticate` refuses with the reason; `EnsureUserIsActive` logs out mid-session (probe A11) |
| 2 | Registration accepts a bad domain, phone, reg no or session format | `StudentRegistrationRequest`: `ends_with:@student.du.test`, `01[3-9]\d{8}`, `digits:10`, unique email and reg no |
| 3 | Passwords stored in plain text | `'password' => 'hashed'` cast |
| 4 | Mass assignment of status, tracking ID, assignee, room or owner | Probe A1: posting `status=closed`, `tracking_id`, `assigned_to`, `room_id`, `student_profile_id` changes nothing |
| 5 | BR-01 / BR-02: second seat for a student, second student in a seat | Row locks + checks + unique generated columns `active_seat_id` / `active_student_profile_id`; probe A12 |
| 6 | BR-03: pending student allocated, complains or joins | Login gate, `active` middleware, service and policy checks |
| 7 | BR-04: vacating deletes history or leaves the student Resident | Sets `vacated_at`, reason and `vacated_by`, student back to Attached; date not before allocation or in future |
| 8 | BR-05: capacity below occupied | `RoomService::update` → 422; retires only free seats |
| 9 | BR-06: invalid transition accepted, or a bogus status crashes | `allowedTransitions()` → 422 with a clear message; probe A3: `to_status=teleported` → validation error, not 500 |
| 10 | BR-06: tutor or student closes or rejects; non-owner reopens; reopen after 7 days | `ComplaintPolicy::transition` + `canBeReopened()` |
| 11 | BR-07: reject or resolve without a note | `ComplaintStatusService` → 422 |
| 12 | BR-08: tutor sees or edits unassigned complaints, or students outside their blocks | Shared `scopeVisibleTo` for list and policy; tested |
| 13 | BR-09: duplicate tracking IDs | Per-year counter under `lockForUpdate`, unique index, year rollover tested; 40-process MySQL run documented |
| 14 | Tracking-ID year or overdue clock in the wrong timezone | `config/app.php` timezone is `Asia/Dhaka` |
| 15 | BR-10: overdue counts finished complaints | `scopeOverdue` excludes Resolved/Closed/Rejected, uses `created_at` > 7 days |
| 16 | BR-11: drafts on public list, detail or attachment | `Notice::published()`; detail and attachment 404 for everyone |
| 17 | BR-12: duplicate request; club admin manages another club | Service check + unique generated column; `ClubPolicy` checks the pivot |
| 18 | **BR-13: a PHP file renamed `.jpg` / `.pdf` gets through** | Real upload through the app's validator: sniffed as `text/x-php`, **rejected** for photos and notice PDFs. Random names, private disk, served only through authorised routes with `nosniff` |
| 19 | Club admin escapes "Club category only" | Probe A4: moving own notice to Provost Office → validation error |
| 20 | Privilege escalation through the Users screen | Probes A5–A7: Office Staff and Provost get 403; students can't get staff roles (403); House Tutor and Club Admin can't be set there; Super Admin can't drop their own role |
| 21 | Anonymous reporter leaks to office staff | Probe A10: name absent from list, detail, status log and comments; room hidden |
| 22 | Students reach staff pages | Probe A9: approve registration, student list, another student's profile → 403 |
| 23 | XSS | No `{!! !!}` anywhere in the views |
| 24 | CSRF missing | Standard `web` middleware; forms use `@csrf` |
| 25 | Secrets committed | Only `.env.example` is tracked; no credentials in it |
| 26 | Validation inline in controllers / fat controllers | Every write action uses a Form Request; rules live in services |
| 27 | N+1 queries | `Model::preventLazyLoading()` outside production, so 174 tests would throw on a lazy load |
| 28 | Missing indexes or constraints | Unique: `registration_no`, `tracking_id`, `(block_id, room_no)`, `(room_id, label)`, `club_admins.user_id`; indexes on every status column; FKs with `restrictOnDelete` on history |
| 29 | Lists not paginated or filters missing | Students: search, block, department, session, status; complaints: status, category, overdue, search; notices: search, category |
| 30 | Dashboard wrong or unscoped | `DashboardService` checked in `DashboardTest` and `SeederTest`; tutors scoped to their blocks |
| 31 | Seed data short of Section 10 or demo logins broken | `SeederTest`: 3 blocks, 32 rooms, 61 students (40/15/5), 5 tutors, 4 clubs, 24 complaints (6 overdue), 12 notices (2 draft, 2 pinned); all six accounts log in |
| 32 | Required tests missing | BR-01, 02, 06 (valid and invalid), 08 and many 403 tests present; suite passes |
| 33 | Code style | Pint passes |
| 34 | Layout breaks at 375 px | Headless Chrome at a 375 px viewport, logged in as each of the six roles: 32 pages (lists, details, forms) and none scrolls horizontally |

---

## 4. Not verified

- **BR-09 under MySQL load.** Covered only by the manual 40-process run in the progress log. The automated tests use SQLite, where `lockForUpdate` does nothing.

---

## 5. Fix order before submitting

1. **F1 + F3:** commit the working tree in meaningful units, starting with `composer.json` / `composer.lock`. Then fresh clone → install → `migrate --seed` → test.
2. **F2:** make the dependencies install on PHP 8.2 (dev tools only), or state "8.3 required, 8.2 will not work" at the top of the README.
3. **F4:** fill in "Time spent" (screenshots and the AI section are done).
4. **F6:** add deactivate to the Users screen. It's small, and it closes a real access-control hole.
5. **F5:** let students edit their phone.
6. **F7, F8, F9:** small policy fixes, about one line each.
7. **F10, F11:** fix if there is time, or add one line each to the README assumptions.
