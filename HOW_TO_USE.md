# How to Use the Hall Management System

A walk-through of the app for each role, from the Super Admin down to a student. For installation, see [README.md](README.md#setup).

---

## Contents

1. [Logging in](#logging-in)
2. [Who can do what](#who-can-do-what)
3. [Super Admin](#1-super-admin)
4. [Provost](#2-provost)
5. [Hall Office Staff](#3-hall-office-staff)
6. [House Tutor](#4-house-tutor)
7. [Club Admin](#5-club-admin)
8. [Student](#6-student)
9. [Visitors (no login)](#visitors-no-login)
10. [The complaint lifecycle](#the-complaint-lifecycle)
11. [A full story: from registration to a resolved complaint](#a-full-story-from-registration-to-a-resolved-complaint)

---

## Logging in

Open the app (`http://localhost:8000` locally). The home page is the public **Notice board**. Click **Log in** at the top right.

Every demo account uses the password **`password`**:

| Role | Email |
|---|---|
| Super Admin | `admin@kjh.test` |
| Provost | `provost@kjh.test` |
| Hall Office Staff | `office@kjh.test` |
| House Tutor (North block) | `tutor@kjh.test` |
| Club Admin (also a student) | `clubadmin@student.du.test` |
| Student (resident) | `student@student.du.test` |

After logging in, the menu at the top shows only the screens your role can use. On a phone it folds into the **☰** button. Your name at the top right opens **Profile** and **Log Out**.

If you open a page your role is not allowed to use, you see an **Access denied** page with a link back to your dashboard.

---

## Who can do what

| Action | Student | Club Admin | House Tutor | Office Staff | Provost | Super Admin |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Approve student registrations | – | – | – | ✅ | ✅ | ✅ |
| Manage blocks, rooms, seats | – | – | – | ✅ | view only | ✅ |
| Allocate / vacate a seat | – | – | – | ✅ | ✅ | ✅ |
| See students | own | own | own blocks | all | all | all |
| Manage house tutors | – | – | – | – | ✅ | ✅ |
| Submit a complaint | ✅ | ✅ | – | – | – | – |
| See complaints | own | own | assigned to them | all | all | all |
| Assign a complaint | – | – | – | ✅ | ✅ | ✅ |
| Change complaint status | – | – | assigned only | ✅ | ✅ | ✅ |
| Publish notices | – | Club only | – | ✅ | ✅ | ✅ |
| Approve club members | – | own club | – | – | – | ✅ |
| Dashboard figures | – | – | own blocks | ✅ | ✅ | ✅ |
| Manage users and roles | – | – | – | – | – | ✅ |

A person can hold more than one role. The Club Admin, for example, is also a student.

---

## 1. Super Admin

**Log in as** `admin@kjh.test`. The Super Admin can do everything below, plus two things nobody else can: manage staff roles and run clubs.

### Manage users and roles

1. Open **Users**. It lists staff accounts, with a search box.
2. Pick the roles for a person (Super Admin, Provost, Hall Office Staff) and click **Update role**.

Notes:

- House Tutor, Club Admin and Student roles are not set here. They come from the **House tutors** screen, club admin assignment and student registration.
- Student accounts cannot be given staff roles.
- You cannot remove your own Super Admin role, and every user keeps at least one role.

### Create a club and choose its admins

1. Open **Clubs** and click **New club**. Fill in the name, a short description and an optional logo (JPG or PNG, up to 1 MB), then click **Save**.
2. On the club's edit page, pick up to two admins (the president plus one more) and click **Save admins**.

A club admin must be an approved student, and a student can lead only one club. The student gets the Club Admin role automatically and loses it when removed.

Until a club has an admin, the Super Admin handles its membership requests under **Manage members**.

### Everything else

The Super Admin can also do everything listed below for the [Provost](#2-provost) and [Office Staff](#3-hall-office-staff).

---

## 2. Provost

**Log in as** `provost@kjh.test`. The Provost oversees the hall: figures, complaints, house tutors and notices.

### Dashboard

**Dashboard** shows the whole hall:

- total and occupied seats, and occupancy %
- pending registrations
- open complaints by category
- overdue complaints (open for more than 7 days)
- average resolution time over the last 30 days

### Manage house tutors

1. Open **House tutors** and click **New tutor**.
2. Enter the name, designation (e.g. Assistant Professor), department, phone, login email and a password. Then tick the blocks this tutor covers.
3. Click **Save**. The tutor can now log in.

To change a tutor, click **Edit**. Clicking **Deactivate** stops the tutor from logging in and from being assigned complaints, and their history is kept. **Reactivate** restores them.

### Complaints

The Provost sees every complaint. On a complaint page the Provost can:

- **Assign** it to an office staff member or a house tutor of that block
- move it to an allowed next status. **Rejected** needs a reason, **Resolved** needs a resolution note.
- **Close** a resolved complaint
- add a comment

The Provost (and Super Admin) also see the name on **anonymous** complaints. Everyone else sees "Anonymous student".

### Also available

- **Registrations**: approve or reject students (see [Office Staff](#approve-new-students)).
- **Students**: allocate and vacate seats.
- **Blocks / Rooms**: view only. Editing is for Office Staff and the Super Admin.
- **Notices**: publish notices (see [Office Staff](#publish-a-notice)).

---

## 3. Hall Office Staff

**Log in as** `office@kjh.test`. Office Staff run the hall day to day.

### Approve new students

1. Open **Registrations**. Students who registered are listed as pending.
2. Click **Approve**. The student can now log in.
3. Or click **Reject…**, type a reason, then click **Reject**. The student sees that reason when they try to log in.

### Set up blocks and rooms

1. **Blocks** → **New block**: enter a name and a short code (e.g. North, `N`), then click **Save**.
2. **Rooms** → **New room**: pick the block, then enter the room number, floor and capacity (1–6), and click **Save**.

Each room gets one seat per bed, labelled A, B, C and so on. Open a room to see which seats are occupied and which are free.

If you lower a room's capacity, free seats are retired. Occupied seats are never removed.

### Allocate and vacate seats

1. Open **Students** and search or filter by block, department, session or status.
2. Open a student.
3. To give them a seat, pick a **Free seat** and click **Allocate**.
4. To move them out, enter a **Vacate date** and a **Reason**, then click **Vacate seat**.

Rules the app enforces:

- A student can hold only one seat at a time.
- A seat can hold only one student.
- Allocation history is never deleted, so the student's page shows every room they have lived in.

### Handle complaints

1. Open **Complaints**. You can filter by status or category, tick **Overdue only**, or search by tracking ID (e.g. `KJH-2026-0012`) or title.
2. Open a complaint. Under **Assign**, pick a staff member or a house tutor of that block, then click **Assign** (or **Reassign**).
3. Under **Update status**, pick the next status, add a note if needed, then click **Update**.
4. Use **Add a comment** to talk with the student. Comments appear in time order.

See [the complaint lifecycle](#the-complaint-lifecycle) for which status can follow which.

### Publish a notice

1. Open **Notices** (the management page) and click **New notice**.
2. Fill in:
   - title and body
   - category: Provost Office, Hall Office or Club
   - status: **Draft** (not public) or **Published**
   - optional PDF attachment (up to 5 MB)
   - optional **Pin to the top of the board**
3. Click **Save**.

Drafts appear only on the management page. The public board shows published notices only, pinned first.

---

## 4. House Tutor

**Log in as** `tutor@kjh.test`. This tutor covers the North block.

### What you see

- **Dashboard**: complaint figures for your blocks only.
- **Students**: only students whose current seat is in one of your blocks.
- **Complaints**: only complaints assigned to you. Other complaints show **Access denied**.

### Work on an assigned complaint

1. Open **Complaints** and click a complaint.
2. Under **Update status**, move it forward: **Under Review**, then **In Progress**, then **Resolved**. Resolving needs a resolution note.
3. Use **Add a comment** to ask the student for details or report progress.

Closing and rejecting complaints are left to Office Staff, the Provost and the Super Admin.

On an anonymous complaint you see "Anonymous student" and the block only, not the room number.

---

## 5. Club Admin

**Log in as** `clubadmin@student.du.test`. This student is president of the Debating Club. A Club Admin is still a student, so everything under [Student](#6-student) applies too.

### Handle membership requests

1. Open **Clubs**, choose your club and click **Manage members**.
2. For each pending request, click **Approve** or **Reject**.
3. To take someone out of the club, click **Remove** next to a member.

### Post a club notice

1. Open **Notices** and click **New notice**.
2. The category is **Club**: Club Admins can only publish in that category.
3. Choose **Draft** or **Published**, then click **Save**.

You can edit or delete only the Club notices you wrote yourself.

---

## 6. Student

**Log in as** `student@student.du.test`. This student lives in North block.

### Register (new students)

New students sign up from **Register** on the login page. They enter:

- full name
- university email
- registration number (10 digits)
- department
- session (e.g. `2023-24`)
- phone (11 digits, starting `013`–`019`)
- a password

The account starts as **Pending**. The student can log in only after Office Staff, the Provost or the Super Admin approve it. If it is rejected, the login page shows the reason.

### Your home page

**Dashboard** is a personal page with your seat, your open complaints and your clubs. **My hall profile** shows your current room and every room you have lived in.

### Keep your profile up to date

1. Click your name at the top right → **Profile**.
2. Change your **Name** or **Phone** and click **Save**. A green "Profile updated." message confirms it.
3. Change your password in the second box.

Your email, registration number, department and session come from the university record and cannot be changed here. Ask the hall office if one of them is wrong.

### Submit a complaint

1. Open **Complaints** and click **New complaint**.
2. Pick a category: Water, Electricity, Internet, Cleanliness, Furniture, Security or Other.
3. Enter a title and description.
4. Location:
   - Residents: your room is filled in automatically.
   - Attached (non-resident) students: type where the problem is.
5. Optionally add a photo (JPG or PNG, up to 2 MB).
6. Optionally tick **Submit anonymously**. Only the Provost and Super Admin will see your name, and others see only your block, not your room number.
7. Click **Submit complaint**.

You get a tracking ID such as `KJH-2026-0025`.

### Track your complaint

Open **Complaints** to see your own complaints. Each one shows:

- its status and how many days it has been open
- an **Overdue** badge if it has been open more than 7 days
- comments
- a full history of status changes

If a complaint was marked **Resolved** but the problem is back, you can **reopen** it within 7 days of resolution.

### Join a club

1. Open **Clubs** and choose a club.
2. Click **Request to join**. The request waits for the club's admin.

You cannot send a second request while one is pending, or once you are a member.

### Read notices

**Notice board** lists published notices, pinned ones first. You can search by title, filter by category, open a notice and download its PDF.

---

## Visitors (no login)

Anyone can open the **Notice board** and **Clubs** pages without logging in. Draft notices are never shown; their links return "Page not found".

---

## The complaint lifecycle

```
Submitted ──► Under Review ──► In Progress ──► Resolved ──► Closed
    │              │                              │
    └──► Rejected ◄┘                              └──► In Progress (student reopens, within 7 days)
```

| From | Allowed next | Who | Note needed |
|---|---|---|---|
| Submitted | Under Review, Rejected | Staff, Provost, Super Admin; assigned tutor (Under Review only) | Reason for Rejected |
| Under Review | In Progress, Rejected | Staff, Provost, Super Admin; assigned tutor (In Progress only) | Reason for Rejected |
| In Progress | Resolved | Staff, Provost, Super Admin, assigned tutor | Resolution note |
| Resolved | Closed | Staff, Provost, Super Admin | – |
| Resolved | In Progress (reopen) | The student who filed it, within 7 days | – |
| Closed, Rejected | none (final) | – | – |

Any other move is refused with a clear message, for example "A complaint cannot move from Submitted to Closed."

---

## A full story: from registration to a resolved complaint

Follow these steps to see every role at work. Log out between steps.

1. **New student:** on the login page click **Register** and sign up. Logging in now is refused, because the account is pending.
2. **Office Staff** (`office@kjh.test`): open **Registrations** and click **Approve** for the new student.
3. **Office Staff:** open **Students**, find the student, pick a **Free seat** in North block and click **Allocate**.
4. **Student:** log in. **My hall profile** shows the room. Open **Complaints** → **New complaint**, report a broken fan and submit. Note the tracking ID.
5. **Office Staff:** open the complaint, assign it to the North block tutor (Dr. Nusrat Jahan) and move it to **Under Review**.
6. **House Tutor** (`tutor@kjh.test`): open the complaint, add a comment, then move it to **In Progress** and later **Resolved** with a resolution note.
7. **Student:** the complaint now shows Resolved, with the full history. If the fan breaks again, reopen it.
8. **Provost** (`provost@kjh.test`): open the complaint and **Close** it. The dashboard's average resolution time now includes it.
9. **Student:** open **Clubs**, choose the Debating Club and click **Request to join**.
10. **Club Admin** (`clubadmin@student.du.test`): open **Clubs** → Debating Club → **Manage members** and click **Approve**. Then post a **Club** notice. It appears on the public notice board.
11. **Super Admin** (`admin@kjh.test`): open **Users** to see the staff roles, and **Clubs** to create a new club and pick its president.
