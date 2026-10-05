# FAKT app: all-round upgrade plan (2026/27)

Status: **Phase 0, Phase 1 and eight Phase 3 features are built** (branch
`claude/determined-hamilton-bfzi1g`). Section 8 records the decisions they follow, and
section 9 lists what was built. Phase 2 (delegation changes) was dropped by decision Q3.
The step-by-step server procedure is in `deploy/UPGRADE-DEPLOY-2026-27.md` (Hungarian).

---

## 1. Project map (as of `main` = `b40e75c`)

### 1.1 Layers

```
Browser (Vue 3 + Inertia v3, TS, Tailwind v4, shadcn-vue)
   │  Inertia page props (one JSON payload per visit) + shared props on every request
   ▼
routes/web.php ── middleware: auth, approved, verified, throttle:*, password.confirm
   ▼
Controllers (11)            Support services (domain logic)       Console (cron → schedule:run)
 Dashboard                   AccessScope   – "who manages what"    queue:work (in-process)
 Organization  ───────────▶  TaskDelegation – "who can assign whom"fakt:recurring-tasks
 Admin                       Ktszt         – KTSZT seats, COI      fakt:due-reminders
 Courses                     PersonalCalendar – visible events     fakt:retention
 Calendar / CalendarFeed     LifecycleProgress – obligations       fakt:diagnose, fakt:bootstrap-president
 Tasks, Lifecycle, Alumni,   OrgStructure  – 4 portfolios, 6 Teams
 Documents, Notifications    Audit, SecureUpload, SecurityLog
   ▼
MySQL `nxt02408_faktapp` (2 domain migrations, 24 domain tables)
```

### 1.2 Data model (everything hangs off `semesters`)

| Cluster | Tables | Notes |
|---|---|---|
| People | `users`, `member_profiles`, `mentorships` | approval workflow on `users` |
| Org structure | `org_units` (portfolio → team tree), `role_assignments`, `team_memberships`, `projects`, `project_members` | **every row is scoped to one semester** |
| Courses | `course_offerings`, `enrollment_requests` | instructor is a free-text name, not a user |
| Calendar | `events`, `attendances` | an event can point to a unit, project or course |
| Work | `tasks`, `task_assignees`, `task_comments` | `parent_id` used for recurrences and subtasks |
| Lifecycle | `obligation_rules`, `progress_records`, `member_requests` | versioned rules |
| Comms / files | `announcements`, `documents`, `notifications` | |
| Ops | `audit_entries`, `import_batches`, `import_rows`, `jobs`, `cache`, `sessions` | queue, cache and session are all on the database driver |

### 1.3 Levels as implemented today

| Level | Role (`role_assignments.role`) | Scope source |
|---|---|---|
| L0 | `president` (Elnök) | the whole semester |
| L1 | `vice_president` (Alelnök) | own portfolio and its Teams |
| L2 | `team_leader` (Teamvezető) | own Team |
| L2′ | `project_leader` (Projektvezető) | own Projekt (beside the Teams, not under them) |
| L3 | member: `team_memberships` row, `project_member` | own Team / Projekt |
| Board | `ktszt_member` and 2 derived ex-officio seats | course decisions only (Határozat 1.2) |
| Outside | alumni (`member_profiles.member_status`) | alumni events, mentoring |

---

## 2. Findings (measured or verified, not guessed)

### 2.1 Critical: activating a new semester locks the Elnök out

`User::isPresident()` only looks at role assignments **in the active semester**.
`AdminController::storeSemester` with `activate = true` creates the new semester and its org
units, but copies no roles. I reproduced it in a throwaway test:

```
isPresident after activating new semester: false
admin page status: 403
fakt:bootstrap-president → exit 1 "Már létezik aktív elnöki kinevezés."
```

So after the switch, nobody can reach Admin. The recovery command refuses as well, because a
non-revoked `president` row still exists in the old semester. The same switch also makes
every Alelnök, Teamvezető, Team membership, Projekt and KTSZT seat disappear at once.

**Until this is fixed (Phase 0): do not create a new semester with "Aktiválás" ticked in
production.** The spring semester starts in February 2027, so the fix has to ship before then.

### 2.2 Data flow: the same lookups run over and over

I seeded 1 Elnök, 1 Alelnök, 1 Teamvezető, 20 members, 30 tasks and 40 events, then counted
SQL queries per page load:

| Page | Elnök | Alelnök | Teamvezető | Tag |
|---|---:|---:|---:|---:|
| Dashboard | 29 | 40 | 40 | 36 |
| Feladatok | 43 | **70** | **70** | 58 |
| Naptár | 24 | 26 | 26 | 23 |
| Kurzusok | 12 | 23 | 23 | 21 |
| Szervezet | 28 | 38 | 36 | 32 |

Breakdown of the Alelnök's Feladatok page (70 queries):
- **22** identical `select * from semesters where is_active = 1` queries. `Semester::active()` is not memoised.
- **26** `role_assignments` queries. `isPresident()`, `isLeader()` and `managedOrgUnitIds()` each re-query the roles. `TaskDelegation::optionsFor()` runs 3 times per request, because `assignableIdsFor()` and `summaryFor()` call it again.
- The shared Inertia props (`abilities.isPresident`, `abilities.isLeader`, `roles`) add three more role lookups to **every** page.

Other inefficiencies:

| Where | Problem | Fix |
|---|---|---|
| `CalendarController::rsvp` / `finalize` | Load the participant's **whole** calendar to check one event id | an `Event::visibleTo($user)` scope + `whereKey()->exists()` |
| `DashboardController` | Loads every visible event, then `->take(5)` in PHP | `where('ends_at','>=',now())->limit(5)` in SQL |
| `TaskController::index` | Eager-loads **all** comments of **all** tasks | `withCount('comments')`; load the thread when a task is opened |
| `CourseController::request` | Loads every enrollment + course to detect a time clash | one `exists()` query with an interval-overlap condition |
| `Ktszt` / `AccessScope::managesCourses` | Find Szakmaiság with `LIKE '%szakmaisag%'` on name or slug | a stable `org_units.code` column |
| `LifecycleProgress::for` | Sums `progress_records` across **all** semesters, but compares against the **active** semester's rules | needs a decision, see Q7 |
| `CalendarFeed` | Rebuilds the full ICS on every poll and has no `ETag` | `ETag` / `Last-Modified` + `304` |

### 2.3 The course → calendar gap (your main request)

`PersonalCalendar` already shows course events to users with an **approved** enrollment
(`course_offering_id IN approved`). But:

1. `CourseController::store` creates **one** `Event`, the first session. The `recurrence_rule`
   (`FREQ=WEEKLY;COUNT=10`) is stored and **never expanded**. A weekly 10-session course appears
   **once** in the calendar and in the ICS feed.
2. The instructor is `instructor_name` (text), so the instructor, the KTSZT member in charge
   and any helper **never** see the course in their calendar.
3. Attendance can only be recorded for that single event. Course completion can't be derived
   from attendance.
4. The ICS feed has no reminders (`VALARM`), no `SEQUENCE`/`LAST-MODIFIED` (clients miss
   time changes), and no `REFRESH-INTERVAL` hint.
5. When an enrollment is approved, the notification doesn't say the course is now in the
   member's calendar, and there is no "Add to Google Calendar" path.

---

## 3. Mathematical framework

The upgrade makes "who sees what" and "who may do what" **set functions computed once per
request**, instead of ad-hoc checks scattered across controllers. This section defines them
so that the code, `docs/PERMISSIONS.md` and the tests all describe the same objects.

### 3.1 Org structure as a time-indexed forest

For a semester $s$, let $U_s$ be its org units and $\pi: U_s \to U_s \cup \{\bot\}$ the parent
map (Team → portfolio, portfolio → $\bot$). This gives a forest of depth $\le 2$. Write
$\operatorname{desc}^*(x) = \{x\} \cup \{y : \pi(y) = x\}$.

A role assignment is a tuple $a = (u, r, x, t_0, t_1, \rho)$: user $u$, role $r$, unit $x$
(possibly $\bot$), validity interval $[t_0, t_1]$ and revocation time $\rho$ (possibly
$\infty$). It is **active at** time $t$ iff

$$\operatorname{act}(a,t) \iff t_0 \le t \;\wedge\; (t_1 = \bot \vee t \le t_1) \;\wedge\; t < \rho .$$

Let $A_u(t) = \{a : a.u = u,\ \operatorname{act}(a,t)\}$. The **managed scope** is

$$M_u(t) = \begin{cases} U_s & \text{president} \in r(A_u(t)) \\[2pt]
\displaystyle\bigcup_{a \in A_u(t),\ a.r = \text{vp}} \operatorname{desc}^*(a.x) \;\cup \bigcup_{a \in A_u(t),\ a.r = \text{tl}} \{a.x\} & \text{otherwise.}\end{cases}$$

That is exactly what `User::managedOrgUnitIds()` computes today. The change is to compute
$A_u(t)$ **once per request** and derive $M_u$, `isPresident`, `isLeader` and the KTSZT
membership from it in memory.

**Query cost.** If a page performs $k$ authorization checks and each one re-reads roles and
the active semester, the cost is $Q = q_0 + k(c_{\text{sem}} + c_{\text{role}})$. With a
request-scoped context it is $Q' = q_0 + c_{\text{sem}} + c_{\text{role}}$, so the saving is
$(k-1)(c_{\text{sem}} + c_{\text{role}})$. On the Alelnök's task page the measured counts are
22 semester reads and 26 role reads; collapsing each to one removes $21 + 25 = 46$ queries,
leaving $70 - 46 = 24$. The other fixes in 2.2 (comments, delegation called 3 times) should
bring it to about 15–20.

**Rollover invariant (fixes 2.1).** Let $\sigma: U_s \to U_{s'}$ map units of the old semester
to the new one by `code`. Rollover must satisfy

$$\forall a \in \operatorname{Carry}(s):\quad \exists a' \in A(s'):\ a'.u = a.u,\ a'.r = a.r,\ a'.x = \sigma(a.x),$$

where $\operatorname{Carry}(s)$ is the set of mandates that run past the end of $s$
(see Q1). The hard constraint is
$\exists u: \text{president} \in r(A_u(t))$ **at every instant**: activation is refused if
it would leave the new active semester without an Elnök.

### 3.2 Delegation as a relation with a no-escalation invariant

Let $\Lambda$ be the abilities (appoint, assign task, decide enrollment, …) and
$\operatorname{Ab}(u) \subseteq \Lambda \times U_s$ the (ability, scope) pairs user $u$ holds
through roles. Today the task-assignment relation is "**exactly one level down**":

$$u \to_{\text{task}} v \iff v = u \;\vee\; v \in \operatorname{Reports}(u),$$

with $\operatorname{Reports}(\text{Elnök}) = \{\text{Alelnökök}, \text{Projektvezetők}\}$,
$\operatorname{Reports}(\text{Alelnök}) = $ Teamvezetők in the portfolio, and
$\operatorname{Reports}(\text{Teamvezető}) = $ Team members.

**Proposal A: reach-based delegation with depth $d$.** Let $\operatorname{level}(v)$ be the
level of $v$'s highest active role. Then

$$u \to_{\text{task}}^{(d)} v \iff \operatorname{scope}(v) \subseteq M_u \;\wedge\; 0 \le \operatorname{level}(v) - \operatorname{level}(u) \le d .$$

$d = 1$ reproduces today's behaviour. $d = 2$ lets an Alelnök assign directly to a Team member
(skip-level) while the Teamvezető still sees the task, because it carries `org_unit_id`.

**Proposal B: temporary deputy grants (helyettesítés).** A grant is
$g = (\text{grantor}, \text{grantee}, B, S, t_0, t_1)$ with $B \subseteq \Lambda$ and
$S \subseteq U_s$. It is valid iff

$$B \times S \;\subseteq\; \operatorname{Ab}(\text{grantor}) \qquad (\text{no escalation}),$$

and it is **non-transitive**: abilities obtained through a grant are not themselves grantable.
Then $\operatorname{Ab}^{+}(u, t) = \operatorname{Ab}(u, t) \cup \bigcup_{g \to u,\ \operatorname{act}(g,t)} B_g \times S_g$.
Because $\operatorname{Ab}^+$ only grows by subsets of a grantor's own abilities and is never
re-granted, the closure can't exceed the Elnök's rights, and the delegation graph has depth
$\le 1$. This models SZMSZ substitution (e.g. Határozat 3.3: the Szakmaiság Teamvezető leads
KTSZT when the Alelnök is absent) and holidays, without permanent re-appointments.

**Conflict of interest** stays a hard filter on top of everything:
$\operatorname{decide}(u, \text{rec}) \Rightarrow u \notin \operatorname{subjects}(\text{rec})$ (`AccessScope::canDecideFor`).

### 3.3 Personal calendar as a union of sets

For user $u$ in semester $s$:

$$E(u) = E_{\text{vis}}(u) \;\cup\; \{e : \text{unit}(e) \in T_u \cup M_u\} \;\cup\; \{e : \text{proj}(e) \in P_u\} \;\cup\; \{e : \text{course}(e) \in C_u\},$$

where $T_u$ are the member's Teams, $P_u$ the Projekts, and, **new**,

$$C_u = \{c : \text{enroll}(u, c) = \text{approved}\} \;\cup\; \{c : (u, c) \in \text{course\_staff}\}.$$

The second term is what makes "if you are assigned to a course it shows up in your own
calendar" hold for instructors, assistants and the KTSZT member in charge, not only for students.

The same predicate becomes one Eloquent scope, `Event::visibleTo($u)`, used by the calendar
page, the ICS feed, the dashboard **and** the authorization checks. Then "can RSVP" equals
"is in the calendar" by construction, with no second definition to drift out of sync.

### 3.4 Course sessions: recurrence expansion with DST

A course has a first session $[t_0, t_0 + \delta)$, a frequency $\Delta \in \{\text{1 day}, i \cdot \text{7 days}, \text{1 month}\}$,
a count $n$ and a set of cancelled dates $X$. The sessions are

$$S = \{\, [\tau_k, \tau_k + \delta) \;:\; \tau_k = t_0 \oplus k\Delta,\ 0 \le k < n,\ \tau_k \notin X \,\}.$$

$\oplus$ is applied in **local wall-clock time** (`Europe/Budapest`) and converted to UTC only
when the ICS is written. A course at 18:00 therefore stays at 18:00 after the 25 October and
28 March DST switches; adding $k \cdot 604800$ s in UTC would shift it by an hour. Monthly steps
use `addMonthNoOverflow`, so $31 \oplus 1\,\text{month} = 30$ in a 30-day month.

The sessions are **materialised** as `events` rows, one per occurrence, rather than sent as
an `RRULE`. That gives per-session attendance, per-session room or time changes, and an exact
ICS. The feed never has to evaluate RRULE/EXDATE logic, and client support for those is
inconsistent anyway.

**Completion from attendance.** With $p_u = |\{k : \text{present}_{u,k}\}| / |S|$ and a
threshold $\theta$ (e.g. 0.75, from an `obligation_rules` row), a member completes the course
iff $p_u \ge \theta$. A pending `progress_records` row is created automatically and KTSZT
approves it (COI rule applies).

### 3.5 Course placement (KTSZT "beosztás") as a min-cost flow

Today every enrollment is approved by hand, one at a time. With students $I$, courses $J$,
preference ranks $r_{ij} \in \{1, \dots, 9\}$, capacities $\kappa_j$ and per-student quotas
$q_i$ (usually 1 or 2), solve

$$\min_{x} \sum_{i,j} w_{ij} x_{ij} \quad \text{s.t.} \quad \sum_j x_{ij} \le q_i,\quad \sum_i x_{ij} \le \kappa_j,\quad x_{ij} \in \{0,1\},\ x_{ij} = 0 \text{ if no request.}$$

We want, **lexicographically**, (1) as many placements as possible, then (2) the best ranks.
That is a *max-flow, min-cost* problem: among all maximum flows, take the cheapest. Priority
(e.g. first-year members first) enters through the weights
$w_{ij} = r_{ij} + \lambda\,\mathbb{1}[\neg\text{first\_year}_i]$, which keeps them non-negative.
Equivalently, in a single objective: $w'_{ij} = w_{ij} - M$ with $M > \max w$.

The constraint matrix is the incidence matrix of a bipartite graph, so it is **totally
unimodular**. The LP relaxation therefore has an integral optimum, and successive-shortest-path
min-cost flow on $s \to I \to J \to t$ solves it exactly in $O(F \cdot E \log V)$. With about 100
students that takes milliseconds, so it can run in PHP inside the request; no queue or
`proc_open` is needed.

Time clashes are already prevented at request time (`CourseController::request`), so no clash
constraint is needed in the flow. Ties are broken by a seeded permutation (seed stored with
the run), which keeps the result reproducible and auditable.

KTSZT sees the proposal as a **preview**, adjusts it and applies it. Each applied decision is
still an audited `enrollment_requests` update, and members whose own enrollment is affected
cannot apply it (COI).

Python reference (to cross-check the PHP implementation on real data):

```python
import networkx as nx

def place(requests, capacity, quota, first_year, lam=5):
    """requests: {(student, course): rank 1..9}; returns the set of placed (student, course).

    Maximises the number of placements, then minimises sum(rank + lam * not_first_year).
    """
    G = nx.DiGraph()
    for i in {i for i, _ in requests}:
        G.add_edge("s", ("i", i), capacity=quota.get(i, 1), weight=0)
    for (i, j), r in requests.items():
        G.add_edge(("i", i), ("j", j), capacity=1,
                   weight=r + (0 if first_year.get(i) else lam))
    for j, k in capacity.items():
        G.add_edge(("j", j), "t", capacity=k, weight=0)
    flow = nx.max_flow_min_cost(G, "s", "t")
    return {(i, j) for (i, j) in requests if flow[("i", i)].get(("j", j), 0) == 1}
```

### 3.6 Reminder digest (fewer emails)

If member $u$ receives $m_u$ notifications per day, one email each gives $\sum_u m_u$ SMTP sends.
A daily digest sends $|\{u : m_u > 0\}|$. A personal Gmail account has a daily sending cap of a few hundred messages. With 60 members
and a busy week ($m_u \approx 5$), per-notification emails need about 300 sends a day; the
digest needs at most 60. Urgent items (registration approval,
password reset) stay immediate.

---

## 4. Upgrade roadmap

Each phase is one release (one zip from the `cpanel-release` workflow) and is deployable on its
own. The migrations are **additive only** (new tables or columns, no drops or renames), so the
previous release keeps working against the new schema. That is what makes the rollback in the
deploy guide safe.

### Phase 0: Safety and speed (no migration) — ship first

| # | Change | Why |
|---|---|---|
| 0.1 | Refuse to activate a semester that has no `president` assignment. Rollover wizard v1: "copy the leadership into the new semester" (portfolio and Team units mapped by slug; roles, Team memberships, KTSZT seats, active Projekts) | fixes 2.1 |
| 0.2 | `fakt:bootstrap-president` gains `--semester=active` recovery: allowed when the **active** semester has no president, even if older semesters do | no-SSH recovery path |
| 0.3 | `App\Support\UserContext`: request-scoped, memoised active semester, active roles, $M_u$, Teams, Projekts, KTSZT flag. `User::isPresident()` etc. delegate to it | about 70 → about 20 queries |
| 0.4 | `Semester::active()` memoised per request with `once()` | 22 queries → 1 |
| 0.5 | `TaskDelegation::optionsFor()` computed once per request | 3× → 1× |
| 0.6 | `Event::scopeVisibleTo()` replaces the in-PHP collection checks in RSVP/finalize; dashboard limit moved into SQL | O(n) → O(1) rows |
| 0.7 | Task list: `withCount('comments')`, lazy thread | payload size |
| 0.8 | Query-budget tests (`assertQueryCountLessThan`) per page and role | keeps it fast |
| 0.9 | Unblock Dependabot (#13, #15, #2): find out why "tests" fails, merge the safe ones | security |

### Phase 1: Courses in your personal calendar (migration)

| # | Change |
|---|---|
| 1.1 | New `course_staff` table (`course_offering_id`, `user_id`, `role` ∈ {`instructor`, `assistant`, `ktszt_owner`}). The `instructor_name` text stays for external instructors |
| 1.2 | Sessions materialised as `events` (one per occurrence, §3.4), with DST-correct expansion; `fakt:backfill-course-sessions` expands existing courses once |
| 1.3 | `PersonalCalendar` → `Event::visibleTo()` with the new $C_u$ (§3.3). Staff see the sessions; approved students see them; waitlisted members see them only after approval |
| 1.4 | Editing a course time or cancelling a session updates the events and bumps `SEQUENCE`, and the affected members are notified |
| 1.5 | ICS feed v2: `VALARM` (default 60 min, configurable), `SEQUENCE`, `LAST-MODIFIED`, `REFRESH-INTERVAL;VALUE=DURATION:PT1H`, `X-PUBLISHED-TTL:PT1H`, `ETag` + `304`, `STATUS:CANCELLED` for cancelled sessions, and a 30-day look-back so past items don't vanish |
| 1.6 | The approval notification says "bekerült a naptáradba", with an "Add to Google/Outlook" one-click link and a per-event `.ics` download |
| 1.7 | Per-session attendance; automatic completion proposal from $p_u \ge \theta$ (§3.4) |
| 1.8 | KTSZT placement tool: preview → adjust → apply (§3.5) |

### Phase 2: Levels and delegation made explicit (migration)

| # | Change |
|---|---|
| 2.1 | `org_units.code` (`szakmaisag`, `penzugy`, …) replaces the `LIKE` lookups |
| 2.2 | Laravel **Policies/Gates** generated from one ability matrix (`config/fakt-abilities.php`) that mirrors `docs/PERMISSIONS.md`. A data-driven test walks every (role × ability × scope) cell |
| 2.3 | Delegation depth setting $d$ (§3.2-A), default 1 = today's behaviour |
| 2.4 | `delegation_grants` table: deputy grants (§3.2-B), with UI on the Szervezet page, audit, automatic expiry and notifications to both sides |
| 2.5 | Task re-delegation: a Teamvezető who receives a task from an Alelnök can split it into subtasks for Team members (uses `parent_id`); the parent's progress = done subtasks / all subtasks |
| 2.6 | "Ki kicsoda" org chart page: who holds what, since when, who appointed them, open vacancies |
| 2.7 | Rollover wizard v2 with per-mandate choices (carry / end / re-elect) |

### Phase 3: New features (pick from §5)

### Phase 4: Polish

- The page-by-page design sweep (`docs/REDESIGN-SPEC.md`), accessibility checklist, Hungarian copy review.

---

## 5. Feature ideas (ranked by value for effort on shared hosting)

| Idea | Value | Effort | Hosting notes |
|---|---|---|---|
| **Calendar month/week grid** with filters (Team, Projekt, course, type) and clash warnings | high | M | pure frontend |
| **Daily email digest** + notification preferences (§3.6) | high | S | one scheduled callback; fits the in-process scheduler |
| **QR self check-in** for events: the organiser shows a rotating code (TOTP-style, 30 s window), members scan it and attendance is recorded as `present` (pending organiser confirmation) | high | M | no extra service; the code is $\mathrm{HMAC}(k_e, \lfloor t/30 \rfloor)$ |
| **Leader dashboard**: overdue tasks per Team, attendance rate per member, missing RSVPs, obligation progress at risk | high | M | aggregate queries only |
| **Member directory** with search, expertise tags, Team/Projekt badges | med | S | |
| **System health page** for the Elnök (`fakt:diagnose` output, queue depth, failed jobs, last scheduler tick, mail test button) | high (no SSH!) | S | removes many temporary crons |
| **Course feedback** (anonymous 1–5 + text after the last session) → KTSZT report | med | S | |
| **CSV/XLSX exports**: attendance sheet, member list, diploma progress | med | S | PhpSpreadsheet is bundled through the CI zip |
| **Audit log viewer** (filter by actor, record, date) | med | S | `audit_entries` already exists |
| **Kanban view** for tasks, with drag-and-drop status | med | M | |
| **Onboarding checklist** for first-year members (auto-created tasks on approval) | med | S | |
| **FAKT Diploma tracker**: the 4-semester requirement as a visual timeline from `role_assignments` | med | M | |
| **PWA** (installable, offline last-known calendar) | low–med | M | Web Push needs VAPID keys; email is enough at first |
| **Google Calendar push sync** (instead of ICS polling, which Google refreshes only every 8–24 h) | med | L | OAuth app + HTTPS calls from cron; ICS stays the default |
| **Meeting minutes templates and decision log** for közgyűlés (agenda, quorum, votes) | med | M | builds on `events.minutes` |

---

## 6. Hosting constraints that shape every phase

- **No SSH, no Composer, no `proc_open`, no `pcntl_signal`** → every feature runs inside a web
  request or the in-process scheduler. No Node on the server: assets are built in CI.
- **Migrations need `nxt02408_faktdep`** (the runtime user has DML only), so a release with a
  migration needs the user swap described in the deploy guide.
- **Config is cached** → every `.env` change needs `optimize:clear && optimize`.
- **Queue on the database driver, drained once a minute** → emails arrive within about 1 minute.
  A daily digest keeps the queue small.
- Long jobs (placement, backfills) must finish in about 50 s (`--max-time=50`) or be chunked.

## 7. Testing strategy

- Every phase keeps the 116 existing tests green and adds:
  - query-budget tests (Phase 0),
  - DST tests: a weekly 18:00 course across 25 Oct 2026 and 28 Mar 2027 (Phase 1),
  - an ICS golden file (Phase 1),
  - placement optimality, checked against brute force on small random instances (Phase 1),
  - a generated permission matrix test (Phase 2),
  - a rollover test: after activating, the Elnök can still open Admin (Phase 0).

## 8. Decisions (Marci, 2026-10-05)

| # | Question | Decision | Where it lives |
|---|---|---|---|
| Q1 | Mandates | Elnök and Alelnök: **1 July – 30 June**. Teamvezető: **1 July – 31 December** or **1 January – 30 June**. Semesters follow the same halves | `App\Support\Mandate`, `SemesterRollover` |
| Q2/Q3 | Who sees a course in their calendar | **Approved students and waitlisted members**, once a date is fixed. If there are several possible dates, applicants **vote** (approval voting) and the KTSZT fixes one | `PersonalCalendar::COURSE_CALENDAR_STATUSES`, `CourseController::voteDate/fixDate` |
| Q4 | Skip-level delegation | **No.** Delegation stays one level down (§3.2, $d = 1$) | unchanged `TaskDelegation` |
| Q5 | Deputies | Explained below; **not built** | — |
| Q6 | Placement | Any number of courses per member ($q_i = \infty$), **no** priority group | `CoursePlacement` |
| Q7/Q8 | Course obligation | A member may miss **2 sessions** per course (configurable per course). Above that, **the whole Elnökség can waive** the obligation | `CourseCompletion`, `ElnoksegWaivers` |
| Q10 | Features | 8, chosen by Claude: see §9 | |
| Q11 | October update deploy | **Not done**, Gmail sending works. The new release includes it | `deploy/UPGRADE-DEPLOY-2026-27.md` |

**What a deputy (helyettes) would have been.** A temporary, dated hand-over of some of your
powers to someone else while you are away, e.g. an Alelnök on Erasmus for a month lets a
Teamvezető approve things in the portfolio, and it switches off by itself on the end date.
The rule that keeps it safe is $B \times S \subseteq \operatorname{Ab}(\text{grantor})$: you can only
lend what you have, and a borrowed power can't be lent on. It is not built; if you want it
later, it is a self-contained addition (one table, one panel on the Szervezet page).

### 8.1 Mathematics of the two new rules

**Mandate end.** For a role $r$ starting on date $t$ (month $m$, year $y$):

$$\operatorname{end}(r, t) = \begin{cases} (y + \mathbb{1}[m \ge 7])\text{-06-30} & r \in \{\text{Elnök}, \text{Alelnök}\} \\ y\text{-12-31} & r = \text{Teamvezető},\ m \ge 7 \\ y\text{-06-30} & r = \text{Teamvezető},\ m < 7 \end{cases}$$

Activating semester $s'$ carries assignment $a$ iff $\operatorname{end}(a) \ge \operatorname{start}(s')$. So
autumn → spring carries the Elnök and the Alelnökök but not the Teamvezetők, and spring →
autumn carries nobody: the outgoing Elnök must name the next Elnök for the new semester first,
and activation is refused while $\nexists u:\ \text{president} \in r(A_u(s'))$.

**Course completion and the waiver.** Let $S_c$ be the scheduled sessions of course $c$ and
$a_u = |\{k \in S_c : \text{final}_{u,k} \in \{\text{absent}, \text{excused}\}\}|$. Member $u$
completes $c$ iff $a_u \le \alpha_c$ (default $\alpha_c = 2$) or a waiver $w$ is approved. With
$E$ the active Elnökség (Elnök and Alelnökök) and $V = E \setminus \{u\}$ (nobody votes on
their own case), and votes $v_e \in \{\text{yes}, \text{no}, \bot\}$:

$$\operatorname{status}(w) = \begin{cases} \text{rejected} & \exists e \in V: v_e = \text{no} \\ \text{approved} & V \ne \emptyset \wedge \forall e \in V: v_e = \text{yes} \\ \text{pending} & \text{otherwise.} \end{cases}$$

**Placement with $q_i = \infty$.** The member constraints $\sum_j x_{ij} \le q_i$ never bind, so the
problem separates into one problem per course: choose at most $\kappa_j' = \kappa_j - |\text{approved}_j|$
requests minimising $\sum r_{ij}$. Sorting by rank and taking the first $\kappa_j'$ is optimal (an
exchange argument: swapping a chosen request for an unchosen one with a higher rank can't
lower the sum). Ties are broken by $\mathrm{SHA256}(\text{seed}\,\|\,\text{id})$, so the proposal is
reproducible. The min-cost-flow formulation in §3.5 stays the general method if quotas or
priorities are ever introduced.

**QR check-in.** $\text{code}(e, w) = \mathrm{HMAC}_{k_e}(e \,\|\, w)[0..10)$ with
$w = \lfloor t / 30\,\text{s} \rfloor$. Accepting $w$ and $w-1$ gives a validity of 30–60 s; a guess
succeeds with probability $2 \cdot 16^{-10} \approx 1.8 \cdot 10^{-12}$ per try.

## 9. What was built

Measured on the same seeded data as §2.2: the Alelnök's Feladatok page went from **70 to 20
queries**, and every page reads the active semester at most once. `tests/Feature/QueryBudgetTest.php` keeps it that way.

| Area | Built | Tests |
|---|---|---|
| Lockout fix | Activation guard, rollover with mandate carry (+ optional Team memberships and KTSZT seats), next-Elnök appointment, semester editing, `fakt:bootstrap-president` recovery when the active semester has no Elnök | `SemesterRolloverTest` |
| Speed | `RequestMemo` (active semester, roles, scope, KTSZT, delegation), `Event` visibility as one query, dashboard limit in SQL | `QueryBudgetTest` |
| Courses → calendar | One event per session, DST-safe; approved + waitlisted see them (`[Várólista]` in the feed); date poll; placement proposal; 2-absence rule; Elnökség waivers; course managers record session attendance | `CourseCalendarTest` |
| ICS v2 | `SEQUENCE`, `LAST-MODIFIED`, `STATUS:CANCELLED`, 60-min `VALARM`, line folding, `ETag`/304 | `CourseCalendarTest` |
| Feature 1 | Calendar month grid with type / required / upcoming filters | `UpgradeFeaturesTest` |
| Feature 2 | "Google Naptár" link and `.ics` download for every event | `UpgradeFeaturesTest` |
| Feature 3 | Daily email digest at 07:30 + per-member setting (registration mail stays immediate) | `UpgradeFeaturesTest`, scheduler tests |
| Feature 4 | QR self check-in with a 30-second rotating code | `UpgradeFeaturesTest` |
| Feature 5 | Vezetői áttekintés: open/overdue tasks, attendance rate, missing RSVPs per member | `UpgradeFeaturesTest` |
| Feature 6 | Rendszerállapot for the Elnök: PHP, caches, pending migrations, scheduler heartbeat, queue, failed jobs, log errors, test email | `UpgradeFeaturesTest` |
| Feature 7 | CSV exports (Excel-friendly, formula-injection safe): attendance sheet, course roster with absences, member list | `UpgradeFeaturesTest` |
| Feature 8 | Tagnévsor with search (accent-insensitive), Team, offices, expertise | `UpgradeFeaturesTest` |

Not built yet, still on the list: task Kanban, course feedback, audit-log viewer, onboarding
checklist, Diploma tracker, PWA, Google push sync, and the design sweep (§5, Phase 4).
