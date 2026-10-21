**TECHNICAL ASSESSMENT**

**Hall Management System**

Product Requirements & Assessment Brief — Kabi Jasimuddin Hall (sandbox scenario)

| **Item**            | **Detail**                                                                     |
|---------------------|--------------------------------------------------------------------------------|
| Positions           | PHP Laravel Web Developer · Next.js / React Developer                          |
| Issued by           | Focus University Admission Coaching — Product Research & QA                    |
| Contact             | Sakib Uddin, Product Research and QA Executive (reply to the assessment email) |
| Version             | 2.0 — Final for candidates                                                     |
| Issued on           | 21 October 2026                                                                |
| Submission deadline | 23 October 2026, 11:59 PM Bangladesh Time (GMT+6)                              |
| Expected effort     | About 12–16 focused hours across two days                                      |

> **Please read first**
>
> This is a hiring exercise in a fictional sandbox. The hall name gives the scenario a realistic setting, but all data must be fictional and your code will not be deployed or used in production by Focus or by any hall.
>
> You keep ownership of the code you write. We only review it for this hiring decision.
>
> We do not expect you to finish everything. A smaller set of features done well beats a large set done poorly. Section 5 tells you exactly what is mandatory and what is a bonus.

**Contents**

1. About this assessment

2. Choose your track

3. Product context

4. Users and roles

5. Scope

6. Business rules

7. Suggested data model

8. API contract

9. Technical requirements by track

10. Seed data and demo accounts

11. Deliverables and submission

12. Evaluation rubric

13. Self-check before you submit

14. Assumptions and glossary

1. About this assessment

We want to see how you build a small but realistic business application: how you model data, enforce rules and permissions, structure code, handle edge cases, and explain your decisions. The domain is a public university residential hall, where a hall office manages resident students, house tutors (teachers), rooms and seats, complaints, notices and student clubs.

1.1 What we are evaluating

- Correct, working features that follow the business rules in Section 6.

- Clean, readable code that follows the conventions of your framework.

- Sound data modelling and relationships.

- Security: authentication, role-based authorisation on every action, and safe input handling.

- Automated tests for the most important rules.

- A usable, mobile-friendly interface (or, for backend-only work, a well-designed API).

- Clear documentation of how to run the project and what you chose not to do.

1.2 Ground rules

- Work individually. You may use documentation, packages, Stack Overflow and AI coding tools, but you must state in your README which AI tools you used and for what. You must be able to explain and modify any line of your submission during the follow-up interview.

- Use only fictional data. Do not use real student names, real registration numbers or real email addresses.

- Do not commit secrets. Provide a .env.example file instead.

- Clarifying questions are welcome until 22 October 2026, 12:00 PM. Reply to the assessment email. If something is unclear and you cannot wait, make a reasonable assumption and write it down in your README.

- Partial submissions are accepted and reviewed. Prioritise the must-have features.

2. Choose your track

Pick the track that matches the position you applied for. State your track at the top of your README.

| **Track**                 | **Who it is for**                                   | **What you build**                                                                                                                                                                                                      |
|---------------------------|-----------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| A — Laravel               | PHP Laravel Web Developer applicants                | A Laravel application covering all must-have modules. UI may be Blade, Livewire, or Inertia (Vue/React). The JSON API in Section 8 is a bonus for this track.                                                           |
| B — Next.js / React       | Next.js or React Developer applicants               | A Next.js or React frontend covering the must-have screens, consuming the API contract in Section 8. You may mock the API (MSW, json-server, or Next.js route handlers with SQLite) as long as it follows the contract. |
| C — Full-stack (optional) | Applicants who want to be considered for both roles | A Laravel REST API (Section 8) plus a separate Next.js / React frontend. Same must-have scope; the two parts may each be slightly less polished.                                                                        |

*Table 1: Assessment tracks*

> **Fair comparison**
>
> All candidates in a track are scored with the same rubric (Section 12). Choosing Track C does not earn extra points by itself; it is assessed against both rubrics with the expectation adjusted for the wider scope.

3. Product context

In many public university halls, day-to-day administration still runs on paper registers, notice boards and word of mouth. This creates recurring problems:

| **Problem**                             | **Impact**                                                                                                                                       |
|-----------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| No single source of truth for residents | The hall office cannot quickly answer who lives in which room, how many seats are free, or which students are attached but non-resident.         |
| Complaints are not tracked              | Water, electricity, internet and cleanliness issues are reported verbally. Nobody can see who is responsible or how long an issue has been open. |
| House tutors lack visibility            | Teachers responsible for a block do not get a clear list of the issues and students in their block.                                              |
| Notices are missed                      | Paper notices are easy to miss and impossible to search later.                                                                                   |
| Club activity is opaque                 | Students do not know which clubs exist or how to join; club leaders track members on paper.                                                      |

*Table 2: Problems the system addresses*

Your job is to build the first version of a Hall Management System that solves these problems for one hall.

4. Users and roles

| **Role**          | **Description**                                         | **Typical tasks**                                                                                   |
|-------------------|---------------------------------------------------------|-----------------------------------------------------------------------------------------------------|
| Super Admin       | Technical administrator of the system                   | Manage users and roles, full access to everything                                                   |
| Provost           | Head of the hall (a senior teacher)                     | View dashboard and all records, publish official notices, close or reject complaints                |
| House Tutor       | Teacher responsible for one or more blocks              | View students in assigned blocks, handle complaints assigned to them, add comments                  |
| Hall Office Staff | Administrative staff                                    | Approve student registrations, manage rooms and seat allocation, assign complaints, publish notices |
| Club Admin        | A student who leads a club                              | Edit own club page, approve or reject membership requests                                           |
| Student           | Resident or attached (non-resident) student of the hall | Maintain profile, submit and track complaints, read notices, request club membership                |

*Table 3: Roles*

A user can hold more than one role (for example, a Student who is also a Club Admin).

4.1 Permission matrix (must be enforced on the server)

| **Action**                   | **Student** | **Club Admin**     | **House Tutor**  | **Office Staff** | **Provost** | **Super Admin** |
|------------------------------|-------------|--------------------|------------------|------------------|-------------|-----------------|
| Approve student registration | —           | —                  | —                | Yes              | Yes         | Yes             |
| Manage blocks, rooms, seats  | —           | —                  | —                | Yes              | View        | Yes             |
| Allocate / vacate seat       | —           | —                  | —                | Yes              | Yes         | Yes             |
| View student list            | Own only    | Own only           | Assigned blocks  | All              | All         | All             |
| Manage house tutors          | —           | —                  | —                | —                | Yes         | Yes             |
| Submit complaint             | Yes         | Yes                | —                | —                | —           | —               |
| View complaints              | Own only    | Own only           | Assigned to them | All              | All         | All             |
| Assign complaint             | —           | —                  | —                | Yes              | Yes         | Yes             |
| Change complaint status      | —           | —                  | Assigned only    | Yes              | Yes         | Yes             |
| Publish notice               | —           | Club category only | —                | Yes              | Yes         | Yes             |
| Approve club membership      | —           | Own club           | —                | —                | —           | Yes             |
| View dashboard               | —           | —                  | Own blocks       | Yes              | Yes         | Yes             |
| Manage users and roles       | —           | —                  | —                | —                | —           | Yes             |

*Table 4: Role-based permission matrix. Hiding a button in the UI is not enough; the backend (or mock API) must reject unauthorised requests with 403.*

5. Scope

Must-have modules (M1–M6) are required. Should-have items are optional bonus work; attempt them only after the must-haves work and are tested.

5.1 Must-have modules

M1 — Authentication and registration

- Login and logout for all roles using the seeded demo accounts (Section 10).

- Student self-registration with: full name, registration number (10 digits), department, session (e.g. 2023–24), phone (Bangladeshi format, 01XXXXXXXXX), email, password.

- Email must use the fictional university domain @student.du.test. Registration number and email must be unique.

- New student accounts start as Pending and cannot log in to protected areas until Hall Office Staff approves them. Staff can also reject with a reason.

- Passwords are hashed. Track A: Laravel’s built-in auth or Breeze/Fortify is fine. Track B: protect routes by role on the client and assume the API returns 401/403 correctly.

M2 — Students, rooms and seat allocation

- Hall structure: Blocks (e.g. North, South, Extension) → Rooms (room number, floor) → Seats (each room has a capacity, typically 2–4).

- Office Staff can create, edit and list blocks and rooms, and see which seats are occupied or free.

- Student status: Attached (non-resident) or Resident. A student becomes Resident only when a seat is allocated.

- Allocate a seat to an approved student; vacate a seat with a date and reason. Keep allocation history (who stayed where, from when to when).

- Student list with search (name or registration number), filter (block, department, session, status) and pagination.

- Student profile page showing personal details, current room and allocation history.

M3 — House tutors (teachers)

- Provost or Super Admin can create, edit and deactivate house tutors: name, designation (e.g. Assistant Professor), department, phone, email.

- Assign each house tutor to one or more blocks. A block may have more than one tutor.

- A logged-in house tutor sees the students in their assigned blocks and the complaints assigned to them.

M4 — Complaint management (highest priority)

- Students submit a complaint with: category (Water, Electricity, Internet, Cleanliness, Furniture, Security, Other), title, description, optional photo (JPG/PNG, max 2 MB). The student’s current room is filled in automatically; an attached (non-resident) student may enter a location instead.

- Each complaint receives a unique, human-readable tracking ID in the format KJH-YYYY-NNNN (for example KJH-2026-0042). The sequence restarts each year.

- Status workflow and allowed transitions are defined in BR-06. Every status change records who changed it, when, and an optional note.

- Office Staff or Provost assigns a complaint to a house tutor or a staff member.

- Both the student and staff can add comments to a complaint. Comments are shown in time order.

- Each complaint shows how many days it has been open. Complaints open for more than 7 days are visibly flagged as overdue.

- Students see only their own complaints; list views support filtering by status and category.

M5 — Notice board

- Authorised roles create notices: title, body, category (Provost Office, Hall Office, Club), optional PDF attachment (max 5 MB), status (Draft or Published), and a pinned flag.

- Public notice list (no login needed) shows published notices only, pinned first, then newest first, with search by title, filter by category, and pagination.

- Notice detail page with a working attachment download.

M6 — Clubs and membership

- Club directory: name, short description, logo (optional), club admin, member count.

- Students request to join a club. A student cannot have more than one pending or active request for the same club.

- The club admin approves or rejects requests and can remove members. Super Admin can assign club admins.

M7 — Dashboard (small, but required)

- For Office Staff and Provost: total seats, occupied seats and occupancy percentage; number of pending registrations; open complaints by category; number of overdue complaints; average resolution time (in days) of complaints resolved in the last 30 days.

- For House Tutors: the same complaint figures, limited to their blocks.

5.2 Should-have (bonus)

| **Bonus item**           | **Notes**                                                                                   |
|--------------------------|---------------------------------------------------------------------------------------------|
| Anonymous complaints     | Student may hide identity; only Provost and Super Admin can see who submitted it.           |
| Events with registration | Event list, capacity limit, register/cancel, no duplicate registration.                     |
| Polls                    | One vote per student, results visible after voting.                                         |
| Notifications            | Email (to Mailpit or the log driver) when a complaint status changes, sent through a queue. |
| Bangla / English toggle  | Interface strings localised; all data fields must already accept Bangla Unicode text.       |
| CSV export               | Export the filtered student list or complaint list.                                         |
| Activity log             | Audit trail of important admin actions.                                                     |
| Docker setup             | docker compose up brings up the full app.                                                   |
| CI                       | GitHub Actions running tests and linting on each push.                                      |
| Live deployment          | Any free host. Share the URL and demo accounts.                                             |

*Table 5: Optional bonus items*

5.3 Out of scope

- Payments, fees, dining/meal management.

- Native mobile apps.

- Integration with real university systems or single sign-on.

- Real SMS sending.

- Alumni portal, magazine/blog, gallery, job board (these are future phases of the real product, not part of this test).

6. Business rules

These rules are where most of the evaluation happens. Each one should be enforced on the server and, ideally, covered by a test.

| ID | Rule |
|---|---|
| BR-01 | A student can hold at most one active seat at any time. |
| BR-02 | A seat can be occupied by at most one student at any time. A room can never exceed its capacity. |
| BR-03 | Only approved students can be allocated a seat, submit complaints, or join clubs. |
| BR-04 | Vacating a seat ends the current allocation (sets an end date) and sets the student back to Attached. History is never deleted. |
| BR-05 | Room capacity cannot be reduced below the number of currently occupied seats. |
| BR-06 | Complaint status transitions:<br>Submitted → Under Review or Rejected<br>Under Review → In Progress or Rejected<br>In Progress → Resolved<br>Resolved → Closed (by Provost or Office Staff), or back to In Progress if the student reopens it within 7 days<br>Any other transition must be refused with a clear error. |
| BR-07 | Rejecting a complaint requires a reason. Moving to Resolved requires a resolution note. |
| BR-08 | A house tutor can update only complaints assigned to them, and see only students in their assigned blocks. |
| BR-09 | Tracking IDs are unique and must not collide if two complaints are submitted at the same moment. |
| BR-10 | Resolution time = time from Submitted to Resolved. Overdue = not Resolved/Closed/Rejected and older than 7 days. |
| BR-11 | Draft notices are never visible on public pages or through the public API. |
| BR-12 | A club admin can manage only their own club. A student cannot request the same club twice while a request is pending or active. |
| BR-13 | Uploaded files must be validated by type and size and must not be executable. Stored file names must not be the user-supplied names. |

*Table 6: Business rules*

7. Suggested data model

This is a guide, not a requirement. You may rename, split or merge entities if you explain why in your README.

| **Entity**            | **Key fields**                                                                                                                                  | **Relationships**                                                       |
|-----------------------|-------------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------|
| users                 | name, email, password, status (pending/active/rejected/inactive)                                                                                | has many roles; may have one student_profile or one house_tutor profile |
| roles / role_user     | name (super_admin, provost, house_tutor, office_staff, club_admin, student)                                                                     | many-to-many with users                                                 |
| student_profiles      | registration_no, department, session, phone, residency_status                                                                                   | belongs to user; has many seat_allocations                              |
| blocks                | name, code                                                                                                                                      | has many rooms; many-to-many with house_tutors                          |
| rooms                 | block_id, room_no, floor, capacity                                                                                                              | belongs to block; has many seats                                        |
| seats                 | room_id, label (A/B/C/D)                                                                                                                        | belongs to room; has many seat_allocations                              |
| seat_allocations      | seat_id, student_profile_id, allocated_at, vacated_at, vacate_reason, allocated_by                                                              | history of who occupied which seat                                      |
| house_tutors          | user_id, designation, department, phone, is_active                                                                                              | many-to-many with blocks                                                |
| complaints            | tracking_id, student_profile_id, room_id (nullable), location_text, category, title, description, photo_path, status, assigned_to, is_anonymous | has many complaint_status_logs and complaint_comments                   |
| complaint_status_logs | complaint_id, from_status, to_status, note, changed_by, created_at                                                                              | audit trail                                                             |
| complaint_comments    | complaint_id, user_id, body                                                                                                                     |                                                                         |
| notices               | title, body, category, attachment_path, status, is_pinned, published_at, created_by                                                             |                                                                         |
| clubs                 | name, description, logo_path                                                                                                                    | has many club_memberships; has admins                                   |
| club_memberships      | club_id, student_profile_id, status (pending/active/rejected/removed), decided_by                                                               |                                                                         |

*Table 7: Suggested entities*

8. API contract

Required for Track B (as the contract your frontend consumes) and Track C (as the contract your Laravel API implements). Optional for Track A. Small deviations are acceptable if documented.

8.1 Conventions

- Base URL: /api/v1. JSON only. Dates in ISO 8601.

- Authentication: bearer token (Laravel Sanctum is recommended). POST /auth/login returns { token, user }.

- Success response: { "data": ..., "meta": { pagination } } for lists; { "data": ... } for single items.

- Validation error: HTTP 422 with { "message": "...", "errors": { "field": \["..."\] } } (Laravel default format).

- Unauthenticated: 401. Forbidden: 403. Not found: 404. Invalid status transition: 422 with a clear message.

- Lists accept page, per_page (max 50), search and the filters named below.

8.2 Endpoints

| **Method**      | **Endpoint**                                             | **Purpose**                                  | **Who**                     |
|-----------------|----------------------------------------------------------|----------------------------------------------|-----------------------------|
| POST            | /auth/register                                           | Student registration (creates pending user)  | Public                      |
| POST            | /auth/login, /auth/logout                                | Get / revoke token                           | Public / Any                |
| GET             | /me                                                      | Current user with roles and profile          | Any                         |
| GET             | /registrations?status=pending                            | Pending student accounts                     | Staff                       |
| POST            | /registrations/{id}/approve \| reject                    | Decide on registration (reject needs reason) | Staff                       |
| GET             | /students?search=&block_id=&department=&session=&status= | Student list                                 | Staff, Tutor (scoped)       |
| GET             | /students/{id}                                           | Profile with current seat and history        | Staff, Tutor (scoped), Self |
| GET/POST/PUT    | /blocks, /rooms, /rooms/{id}                             | Hall structure with occupancy                | Staff                       |
| POST            | /seat-allocations                                        | Allocate { student_id, seat_id }             | Staff                       |
| POST            | /seat-allocations/{id}/vacate                            | Vacate { vacated_at, reason }                | Staff                       |
| GET/POST/PUT    | /house-tutors, /house-tutors/{id}                        | Manage tutors and block assignment           | Provost, Super Admin        |
| GET             | /complaints?status=&category=&overdue=                   | Complaint list (scoped by role)              | All logged-in               |
| POST            | /complaints (multipart)                                  | Submit complaint                             | Student                     |
| GET             | /complaints/{tracking_id}                                | Detail with status log and comments          | Owner, Assignee, Staff      |
| POST            | /complaints/{id}/assign                                  | Assign { assignee_id }                       | Staff, Provost              |
| POST            | /complaints/{id}/status                                  | Transition { to_status, note }               | Per BR-06 / BR-08           |
| POST            | /complaints/{id}/comments                                | Add comment                                  | Owner, Assignee, Staff      |
| GET             | /notices, /notices/{id}                                  | Published notices                            | Public                      |
| POST/PUT/DELETE | /admin/notices, /admin/notices/{id}                      | Manage notices incl. drafts                  | Per matrix                  |
| GET             | /clubs, /clubs/{id}                                      | Club directory                               | Public                      |
| POST            | /clubs/{id}/join                                         | Request membership                           | Student                     |
| POST            | /club-memberships/{id}/approve \| reject \| remove       | Decide on membership                         | Club Admin (own club)       |
| GET             | /dashboard                                               | Dashboard figures (scoped by role)           | Staff, Provost, Tutor       |

*Table 8: API endpoints*

Example: complaint detail response

```json

```

9. Technical requirements by track

10. Seed data and demo accounts

11. Deliverables and submission

12. Evaluation rubric

13. Self-check before you submit

14. Assumptions and glossary

1. About this assessment

We want to see how you build a small but realistic business application: how you model data, enforce rules and permissions, structure code, handle edge cases, and explain your decisions. The domain is a public university residential hall, where a hall office manages resident students, house tutors (teachers), rooms and seats, complaints, notices and student clubs.

1.1 What we are evaluating

- Correct, working features that follow the business rules in Section 6.

- Clean, readable code that follows the conventions of your framework.

- Sound data modelling and relationships.

- Security: authentication, role-based authorisation on every action, and safe input handling.

- Automated tests for the most important rules.

- A usable, mobile-friendly interface (or, for backend-only work, a well-designed API).

- Clear documentation of how to run the project and what you chose not to do.

1.2 Ground rules

- Work individually. You may use documentation, packages, Stack Overflow and AI coding tools, but you must state in your README which AI tools you used and for what. You must be able to explain and modify any line of your submission during the follow-up interview.

- Use only fictional data. Do not use real student names, real registration numbers or real email addresses.

- Do not commit secrets. Provide a .env.example file instead.

- Clarifying questions are welcome until 22 October 2026, 12:00 PM. Reply to the assessment email. If something is unclear and you cannot wait, make a reasonable assumption and write it down in your README.

- Partial submissions are accepted and reviewed. Prioritise the must-have features.

2. Choose your track

Pick the track that matches the position you applied for. State your track at the top of your README.

| **Track**                 | **Who it is for**                                   | **What you build**                                                                                                                                                                                                      |
|---------------------------|-----------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| A — Laravel               | PHP Laravel Web Developer applicants                | A Laravel application covering all must-have modules. UI may be Blade, Livewire, or Inertia (Vue/React). The JSON API in Section 8 is a bonus for this track.                                                           |
| B — Next.js / React       | Next.js or React Developer applicants               | A Next.js or React frontend covering the must-have screens, consuming the API contract in Section 8. You may mock the API (MSW, json-server, or Next.js route handlers with SQLite) as long as it follows the contract. |
| C — Full-stack (optional) | Applicants who want to be considered for both roles | A Laravel REST API (Section 8) plus a separate Next.js / React frontend. Same must-have scope; the two parts may each be slightly less polished.                                                                        |

*Table 1: Assessment tracks*

> **Fair comparison**
>
> All candidates in a track are scored with the same rubric (Section 12). Choosing Track C does not earn extra points by itself; it is assessed against both rubrics with the expectation adjusted for the wider scope.

3. Product context

In many public university halls, day-to-day administration still runs on paper registers, notice boards and word of mouth. This creates recurring problems:

| **Problem**                             | **Impact**                                                                                                                                       |
|-----------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| No single source of truth for residents | The hall office cannot quickly answer who lives in which room, how many seats are free, or which students are attached but non-resident.         |
| Complaints are not tracked              | Water, electricity, internet and cleanliness issues are reported verbally. Nobody can see who is responsible or how long an issue has been open. |
| House tutors lack visibility            | Teachers responsible for a block do not get a clear list of the issues and students in their block.                                              |
| Notices are missed                      | Paper notices are easy to miss and impossible to search later.                                                                                   |
| Club activity is opaque                 | Students do not know which clubs exist or how to join; club leaders track members on paper.                                                      |

*Table 2: Problems the system addresses*

Your job is to build the first version of a Hall Management System that solves these problems for one hall.

4. Users and roles

| **Role**          | **Description**                                         | **Typical tasks**                                                                                   |
|-------------------|---------------------------------------------------------|-----------------------------------------------------------------------------------------------------|
| Super Admin       | Technical administrator of the system                   | Manage users and roles, full access to everything                                                   |
| Provost           | Head of the hall (a senior teacher)                     | View dashboard and all records, publish official notices, close or reject complaints                |
| House Tutor       | Teacher responsible for one or more blocks              | View students in assigned blocks, handle complaints assigned to them, add comments                  |
| Hall Office Staff | Administrative staff                                    | Approve student registrations, manage rooms and seat allocation, assign complaints, publish notices |
| Club Admin        | A student who leads a club                              | Edit own club page, approve or reject membership requests                                           |
| Student           | Resident or attached (non-resident) student of the hall | Maintain profile, submit and track complaints, read notices, request club membership                |

*Table 3: Roles*

A user can hold more than one role (for example, a Student who is also a Club Admin).

4.1 Permission matrix (must be enforced on the server)

| **Action**                   | **Student** | **Club Admin**     | **House Tutor**  | **Office Staff** | **Provost** | **Super Admin** |
|------------------------------|-------------|--------------------|------------------|------------------|-------------|-----------------|
| Approve student registration | —           | —                  | —                | Yes              | Yes         | Yes             |
| Manage blocks, rooms, seats  | —           | —                  | —                | Yes              | View        | Yes             |
| Allocate / vacate seat       | —           | —                  | —                | Yes              | Yes         | Yes             |
| View student list            | Own only    | Own only           | Assigned blocks  | All              | All         | All             |
| Manage house tutors          | —           | —                  | —                | —                | Yes         | Yes             |
| Submit complaint             | Yes         | Yes                | —                | —                | —           | —               |
| View complaints              | Own only    | Own only           | Assigned to them | All              | All         | All             |
| Assign complaint             | —           | —                  | —                | Yes              | Yes         | Yes             |
| Change complaint status      | —           | —                  | Assigned only    | Yes              | Yes         | Yes             |
| Publish notice               | —           | Club category only | —                | Yes              | Yes         | Yes             |
| Approve club membership      | —           | Own club           | —                | —                | —           | Yes             |
| View dashboard               | —           | —                  | Own blocks       | Yes              | Yes         | Yes             |
| Manage users and roles       | —           | —                  | —                | —                | —           | Yes             |

*Table 4: Role-based permission matrix. Hiding a button in the UI is not enough; the backend (or mock API) must reject unauthorised requests with 403.*

5. Scope

Must-have modules (M1–M6) are required. Should-have items are optional bonus work; attempt them only after the must-haves work and are tested.

5.1 Must-have modules

M1 — Authentication and registration

- Login and logout for all roles using the seeded demo accounts (Section 10).

- Student self-registration with: full name, registration number (10 digits), department, session (e.g. 2023–24), phone (Bangladeshi format, 01XXXXXXXXX), email, password.

- Email must use the fictional university domain @student.du.test. Registration number and email must be unique.

- New student accounts start as Pending and cannot log in to protected areas until Hall Office Staff approves them. Staff can also reject with a reason.

- Passwords are hashed. Track A: Laravel’s built-in auth or Breeze/Fortify is fine. Track B: protect routes by role on the client and assume the API returns 401/403 correctly.

M2 — Students, rooms and seat allocation

- Hall structure: Blocks (e.g. North, South, Extension) → Rooms (room number, floor) → Seats (each room has a capacity, typically 2–4).

- Office Staff can create, edit and list blocks and rooms, and see which seats are occupied or free.

- Student status: Attached (non-resident) or Resident. A student becomes Resident only when a seat is allocated.

- Allocate a seat to an approved student; vacate a seat with a date and reason. Keep allocation history (who stayed where, from when to when).

- Student list with search (name or registration number), filter (block, department, session, status) and pagination.

- Student profile page showing personal details, current room and allocation history.

M3 — House tutors (teachers)

- Provost or Super Admin can create, edit and deactivate house tutors: name, designation (e.g. Assistant Professor), department, phone, email.

- Assign each house tutor to one or more blocks. A block may have more than one tutor.

- A logged-in house tutor sees the students in their assigned blocks and the complaints assigned to them.

M4 — Complaint management (highest priority)

- Students submit a complaint with: category (Water, Electricity, Internet, Cleanliness, Furniture, Security, Other), title, description, optional photo (JPG/PNG, max 2 MB). The student’s current room is filled in automatically; an attached (non-resident) student may enter a location instead.

- Each complaint receives a unique, human-readable tracking ID in the format KJH-YYYY-NNNN (for example KJH-2026-0042). The sequence restarts each year.

- Status workflow and allowed transitions are defined in BR-06. Every status change records who changed it, when, and an optional note.

- Office Staff or Provost assigns a complaint to a house tutor or a staff member.

- Both the student and staff can add comments to a complaint. Comments are shown in time order.

- Each complaint shows how many days it has been open. Complaints open for more than 7 days are visibly flagged as overdue.

- Students see only their own complaints; list views support filtering by status and category.

M5 — Notice board

- Authorised roles create notices: title, body, category (Provost Office, Hall Office, Club), optional PDF attachment (max 5 MB), status (Draft or Published), and a pinned flag.

- Public notice list (no login needed) shows published notices only, pinned first, then newest first, with search by title, filter by category, and pagination.

- Notice detail page with a working attachment download.

M6 — Clubs and membership

- Club directory: name, short description, logo (optional), club admin, member count.

- Students request to join a club. A student cannot have more than one pending or active request for the same club.

- The club admin approves or rejects requests and can remove members. Super Admin can assign club admins.

M7 — Dashboard (small, but required)

- For Office Staff and Provost: total seats, occupied seats and occupancy percentage; number of pending registrations; open complaints by category; number of overdue complaints; average resolution time (in days) of complaints resolved in the last 30 days.

- For House Tutors: the same complaint figures, limited to their blocks.

5.2 Should-have (bonus)

| **Bonus item**           | **Notes**                                                                                   |
|--------------------------|---------------------------------------------------------------------------------------------|
| Anonymous complaints     | Student may hide identity; only Provost and Super Admin can see who submitted it.           |
| Events with registration | Event list, capacity limit, register/cancel, no duplicate registration.                     |
| Polls                    | One vote per student, results visible after voting.                                         |
| Notifications            | Email (to Mailpit or the log driver) when a complaint status changes, sent through a queue. |
| Bangla / English toggle  | Interface strings localised; all data fields must already accept Bangla Unicode text.       |
| CSV export               | Export the filtered student list or complaint list.                                         |
| Activity log             | Audit trail of important admin actions.                                                     |
| Docker setup             | docker compose up brings up the full app.                                                   |
| CI                       | GitHub Actions running tests and linting on each push.                                      |
| Live deployment          | Any free host. Share the URL and demo accounts.                                             |

*Table 5: Optional bonus items*

5.3 Out of scope

- Payments, fees, dining/meal management.

- Native mobile apps.

- Integration with real university systems or single sign-on.

- Real SMS sending.

- Alumni portal, magazine/blog, gallery, job board (these are future phases of the real product, not part of this test).

6. Business rules

These rules are where most of the evaluation happens. Each one should be enforced on the server and, ideally, covered by a test.

| ID | Rule |
|---|---|
| BR-01 | A student can hold at most one active seat at any time. |
| BR-02 | A seat can be occupied by at most one student at any time. A room can never exceed its capacity. |
| BR-03 | Only approved students can be allocated a seat, submit complaints, or join clubs. |
| BR-04 | Vacating a seat ends the current allocation (sets an end date) and sets the student back to Attached. History is never deleted. |
| BR-05 | Room capacity cannot be reduced below the number of currently occupied seats. |
| BR-06 | Complaint status transitions:<br>Submitted → Under Review or Rejected<br>Under Review → In Progress or Rejected<br>In Progress → Resolved<br>Resolved → Closed (by Provost or Office Staff), or back to In Progress if the student reopens it within 7 days<br>Any other transition must be refused with a clear error. |
| BR-07 | Rejecting a complaint requires a reason. Moving to Resolved requires a resolution note. |
| BR-08 | A house tutor can update only complaints assigned to them, and see only students in their assigned blocks. |
| BR-09 | Tracking IDs are unique and must not collide if two complaints are submitted at the same moment. |
| BR-10 | Resolution time = time from Submitted to Resolved. Overdue = not Resolved/Closed/Rejected and older than 7 days. |
| BR-11 | Draft notices are never visible on public pages or through the public API. |
| BR-12 | A club admin can manage only their own club. A student cannot request the same club twice while a request is pending or active. |
| BR-13 | Uploaded files must be validated by type and size and must not be executable. Stored file names must not be the user-supplied names. |

*Table 6: Business rules*

7. Suggested data model

This is a guide, not a requirement. You may rename, split or merge entities if you explain why in your README.

| **Entity**            | **Key fields**                                                                                                                                  | **Relationships**                                                       |
|-----------------------|-------------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------|
| users                 | name, email, password, status (pending/active/rejected/inactive)                                                                                | has many roles; may have one student_profile or one house_tutor profile |
| roles / role_user     | name (super_admin, provost, house_tutor, office_staff, club_admin, student)                                                                     | many-to-many with users                                                 |
| student_profiles      | registration_no, department, session, phone, residency_status                                                                                   | belongs to user; has many seat_allocations                              |
| blocks                | name, code                                                                                                                                      | has many rooms; many-to-many with house_tutors                          |
| rooms                 | block_id, room_no, floor, capacity                                                                                                              | belongs to block; has many seats                                        |
| seats                 | room_id, label (A/B/C/D)                                                                                                                        | belongs to room; has many seat_allocations                              |
| seat_allocations      | seat_id, student_profile_id, allocated_at, vacated_at, vacate_reason, allocated_by                                                              | history of who occupied which seat                                      |
| house_tutors          | user_id, designation, department, phone, is_active                                                                                              | many-to-many with blocks                                                |
| complaints            | tracking_id, student_profile_id, room_id (nullable), location_text, category, title, description, photo_path, status, assigned_to, is_anonymous | has many complaint_status_logs and complaint_comments                   |
| complaint_status_logs | complaint_id, from_status, to_status, note, changed_by, created_at                                                                              | audit trail                                                             |
| complaint_comments    | complaint_id, user_id, body                                                                                                                     |                                                                         |
| notices               | title, body, category, attachment_path, status, is_pinned, published_at, created_by                                                             |                                                                         |
| clubs                 | name, description, logo_path                                                                                                                    | has many club_memberships; has admins                                   |
| club_memberships      | club_id, student_profile_id, status (pending/active/rejected/removed), decided_by                                                               |                                                                         |

*Table 7: Suggested entities*

8. API contract

Required for Track B (as the contract your frontend consumes) and Track C (as the contract your Laravel API implements). Optional for Track A. Small deviations are acceptable if documented.

8.1 Conventions

- Base URL: /api/v1. JSON only. Dates in ISO 8601.

- Authentication: bearer token (Laravel Sanctum is recommended). POST /auth/login returns { token, user }.

- Success response: { "data": ..., "meta": { pagination } } for lists; { "data": ... } for single items.

- Validation error: HTTP 422 with { "message": "...", "errors": { "field": \["..."\] } } (Laravel default format).

- Unauthenticated: 401. Forbidden: 403. Not found: 404. Invalid status transition: 422 with a clear message.

- Lists accept page, per_page (max 50), search and the filters named below.

8.2 Endpoints

| **Method**      | **Endpoint**                                             | **Purpose**                                  | **Who**                     |
|-----------------|----------------------------------------------------------|----------------------------------------------|-----------------------------|
| POST            | /auth/register                                           | Student registration (creates pending user)  | Public                      |
| POST            | /auth/login, /auth/logout                                | Get / revoke token                           | Public / Any                |
| GET             | /me                                                      | Current user with roles and profile          | Any                         |
| GET             | /registrations?status=pending                            | Pending student accounts                     | Staff                       |
| POST            | /registrations/{id}/approve \| reject                    | Decide on registration (reject needs reason) | Staff                       |
| GET             | /students?search=&block_id=&department=&session=&status= | Student list                                 | Staff, Tutor (scoped)       |
| GET             | /students/{id}                                           | Profile with current seat and history        | Staff, Tutor (scoped), Self |
| GET/POST/PUT    | /blocks, /rooms, /rooms/{id}                             | Hall structure with occupancy                | Staff                       |
| POST            | /seat-allocations                                        | Allocate { student_id, seat_id }             | Staff                       |
| POST            | /seat-allocations/{id}/vacate                            | Vacate { vacated_at, reason }                | Staff                       |
| GET/POST/PUT    | /house-tutors, /house-tutors/{id}                        | Manage tutors and block assignment           | Provost, Super Admin        |
| GET             | /complaints?status=&category=&overdue=                   | Complaint list (scoped by role)              | All logged-in               |
| POST            | /complaints (multipart)                                  | Submit complaint                             | Student                     |
| GET             | /complaints/{tracking_id}                                | Detail with status log and comments          | Owner, Assignee, Staff      |
| POST            | /complaints/{id}/assign                                  | Assign { assignee_id }                       | Staff, Provost              |
| POST            | /complaints/{id}/status                                  | Transition { to_status, note }               | Per BR-06 / BR-08           |
| POST            | /complaints/{id}/comments                                | Add comment                                  | Owner, Assignee, Staff      |
| GET             | /notices, /notices/{id}                                  | Published notices                            | Public                      |
| POST/PUT/DELETE | /admin/notices, /admin/notices/{id}                      | Manage notices incl. drafts                  | Per matrix                  |
| GET             | /clubs, /clubs/{id}                                      | Club directory                               | Public                      |
| POST            | /clubs/{id}/join                                         | Request membership                           | Student                     |
| POST            | /club-memberships/{id}/approve \| reject \| remove       | Decide on membership                         | Club Admin (own club)       |
| GET             | /dashboard                                               | Dashboard figures (scoped by role)           | Staff, Provost, Tutor       |

*Table 8: API endpoints*

Example: complaint detail response

{ "data": {

"id": 42, "tracking_id": "KJH-2026-0042",

"category": "water", "title": "No water on 3rd floor",

"status": "in_progress", "days_open": 3, "is_overdue": false,

"room": { "id": 17, "room_no": "305", "block": "North" },

"student": { "id": 9, "name": "Rahim Uddin" },

"assignee": { "id": 4, "name": "Dr. Nusrat Jahan" },

"photo_url": "https://.../complaints/abc123.jpg",

"status_logs": \[ { "from": "submitted", "to": "under_review",

"note": null, "by": "Hall Office", "at": "2026-10-18T09:12:00+06:00" } \],

"comments": \[ { "by": "Rahim Uddin", "body": "Still no water.", "at": "..." } \],

"created_at": "2026-10-18T08:40:00+06:00" } }

9. Technical requirements by track

9.1 Track A — Laravel

| **Area**      | **Requirement**                                                                                                                                                                                                 |
|---------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Versions      | PHP 8.2+ and Laravel 11 or 12. Composer for dependencies.                                                                                                                                                       |
| Database      | MySQL 8, PostgreSQL or SQLite. All schema changes through migrations; foreign keys and sensible indexes (e.g. registration_no, tracking_id, status).                                                            |
| Seed data     | Seeders and factories that create the demo data in Section 10 with one command: php artisan migrate --seed.                                                                                                     |
| Validation    | Form Request classes (not inline controller validation) for create/update actions.                                                                                                                              |
| Authorisation | Policies and/or Gates. Every route that changes data must be authorised on the server.                                                                                                                          |
| Architecture  | Thin controllers. Put business rules (seat allocation, status transitions, tracking ID generation) in services, actions or model methods. Use database transactions where several writes must succeed together. |
| Eloquent      | Proper relationships, eager loading to avoid N+1 queries, pagination for lists, enums or constants for statuses.                                                                                                |
| Files         | Use the Storage facade; validate type and size; serve private files (complaint photos) through an authorised route.                                                                                             |
| UI            | Blade (with Tailwind or Bootstrap), Livewire, or Inertia. Mobile-friendly. Show validation errors and success messages.                                                                                         |
| Tests         | Pest or PHPUnit feature tests. Minimum: BR-01, BR-02, BR-06, BR-08 and one authorisation (403) test. More is better.                                                                                            |
| Quality       | Follow PSR-12 (Laravel Pint is welcome). Meaningful commit history; please do not squash everything into one commit.                                                                                            |

*Table 9: Track A requirements*

9.2 Track B — Next.js / React

| **Area**       | **Requirement**                                                                                                                                                                                                                                                             |
|----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Versions       | Next.js 14+ (App Router preferred) or React 18+ with Vite. TypeScript strongly preferred.                                                                                                                                                                                   |
| Data           | Consume the API in Section 8. If you mock it, the mock must keep state during a session and enforce the main rules (BR-01, BR-02, BR-06, BR-08) so the UI can show real error states.                                                                                       |
| Data fetching  | TanStack Query, SWR, or Server Components / Server Actions. Handle loading, empty and error states on every screen.                                                                                                                                                         |
| Forms          | Client-side validation (e.g. React Hook Form + Zod) that mirrors the server rules, plus display of server-side 422 errors per field.                                                                                                                                        |
| Auth & routing | Role-based route protection and role-aware navigation; redirect on 401; friendly page on 403.                                                                                                                                                                               |
| Screens        | Login, register, dashboard, student list and profile, room/seat view with allocate and vacate, complaint submit / list / detail (with status timeline and comments), notice list and detail, admin notice editor, club directory and membership requests, house tutor list. |
| UI             | Responsive, mobile-first (test at 375 px width). Tailwind CSS or a component library. Basic accessibility: labels on inputs, keyboard focus, sufficient contrast.                                                                                                           |
| Tests          | At least a few component or integration tests (Vitest/Jest + React Testing Library, or Playwright), e.g. complaint form validation and status transition buttons.                                                                                                           |
| Quality        | ESLint and Prettier; sensible folder structure; reusable components; no secrets in the repository.                                                                                                                                                                          |

*Table 10: Track B requirements*

9.3 Track C — Full-stack

- Laravel API meeting Section 9.1 (except UI) and implementing Section 8 with API Resources for responses.

- Next.js / React frontend meeting Section 9.2, talking to the real Laravel API (configure CORS and Sanctum correctly).

- One README at the repository root (monorepo) or two linked repositories, with instructions to run both.

10. Seed data and demo accounts

Your seeders (or mock data) must create at least the following, using fictional names. Bangla names are welcome to show Unicode support.

- 1 hall, 3 blocks, at least 30 rooms with capacities between 2 and 4.

- At least 60 students: about 40 Resident with seats, 15 Attached, 5 Pending registration.

- 5 house tutors assigned across the 3 blocks.

- 4 clubs (e.g. Debating, Cultural, Sports, Science) with some members and some pending requests.

- At least 20 complaints spread across all statuses, including at least 3 overdue.

- At least 10 notices, including 2 drafts and 2 pinned.

| **Role**                    | **Email**                 | **Password** |
|-----------------------------|---------------------------|--------------|
| Super Admin                 | admin@kjh.test            | password     |
| Provost                     | provost@kjh.test          | password     |
| Hall Office Staff           | office@kjh.test           | password     |
| House Tutor (North Block)   | tutor@kjh.test            | password     |
| Club Admin (also a Student) | clubadmin@student.du.test | password     |
| Student (Resident)          | student@student.du.test   | password     |

*Table 11: Required demo accounts (fictional; for local review only)*

11. Deliverables and submission

**Reply to the assessment email by 23 October 2026, 11:59 PM (GMT+6)** with:

1.  A GitHub (or GitLab) repository link. Public, or private with access granted to the reviewer email given in the assessment email.

2.  A README.md containing: chosen track; tech stack and versions; step-by-step setup (clone → install → .env → migrate/seed → run → test); demo accounts; a list of what is done, partly done and not done; assumptions you made; key design decisions and trade-offs; approximate time spent; which AI tools you used and how.

3.  A .env.example file. No real credentials.

4.  A short demo: either a 3–5 minute screen recording (Loom, Google Drive, YouTube unlisted) or 6–10 screenshots in a /docs folder.

5.  Optional: a live deployment URL. Deployment is a bonus and is never required.

> **After submission**
>
> Shortlisted candidates will be invited to a 30–45 minute follow-up session to walk us through the code, explain decisions, and make one small live change. Submissions received after the deadline may not be reviewed.

12. Evaluation rubric

| **Criterion**                                       | **Weight** | **What good looks like**                                                                                                                          |
|-----------------------------------------------------|------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| Functionality and business rules                    | 30%        | Must-have modules work end to end; business rules in Section 6 hold, including edge cases.                                                        |
| Code quality and architecture                       | 20%        | Readable, consistent, idiomatic for the framework; logic in the right layer; no copy-paste duplication.                                           |
| Security and authorisation                          | 15%        | Server-side checks on every action (or mock enforcement for Track B); safe uploads; no secrets; protection against mass assignment, XSS and CSRF. |
| Data modelling (A/C) or state and data handling (B) | 10%        | Normalised schema, constraints and indexes; or clean data fetching, caching and state management.                                                 |
| Testing                                             | 10%        | Meaningful tests for the critical rules that pass with one command.                                                                               |
| UI / UX                                             | 10%        | Clear, responsive, consistent; good loading, empty and error states; helpful validation messages.                                                 |
| Documentation and git hygiene                       | 5%         | Setup works first time from the README; honest status list; clear commit history.                                                                 |

*Table 12: Scoring rubric (bonus items can add up to 10 extra points but cannot compensate for missing must-haves)*

Automatic concerns: the project does not run from the README instructions; real personal data is used; secrets are committed; the candidate cannot explain their own code in the follow-up session.

13. Self-check before you submit

- A fresh clone runs by following only the README.

- All six demo accounts can log in and see what their role allows, and nothing more.

- A pending student cannot log in to protected areas until approved.

- Allocating a second seat to the same student, or a student to an occupied seat, is refused (BR-01, BR-02).

- An invalid complaint status transition is refused with a clear message (BR-06).

- A house tutor cannot open or update a complaint that is not assigned to them (BR-08).

- Draft notices do not appear publicly (BR-11).

- Tracking IDs follow KJH-YYYY-NNNN.

- Dashboard figures match the seeded data.

- Tests pass with one command.

- The interface works on a 375 px wide screen.

14. Assumptions and glossary

| **Term**         | **Meaning**                                                                       |
|------------------|-----------------------------------------------------------------------------------|
| Hall             | A residential hall of a public university, managed by a Provost and house tutors. |
| Provost          | The senior teacher in charge of the hall.                                         |
| House Tutor      | A teacher responsible for supervising one or more blocks of the hall.             |
| Resident student | A student who has been allocated a seat in the hall.                              |
| Attached student | A student affiliated with the hall who does not (yet) have a seat.                |
| Block            | A building or wing of the hall containing rooms.                                  |
| Session          | Academic intake year, written like 2023–24.                                       |

*Good luck. We look forward to seeing how you think and build.*
