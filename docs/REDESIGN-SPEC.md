# FAKT redesign specification

Status: **awaiting approval**. Nothing in this document is implemented yet.

Decisions taken before writing this spec:

- The redesign follows the **design** brief only (the 20 "vibecoded" tells). The legal and
  accessibility checklist is explicitly out of scope for this round and is parked in section 7.
- The production database is **wiped and rebuilt** from migrations. No data migration is written.
- KTSZT is a **rank with permissions**. No in-app voting module in this round.
- Spec first, implementation in stages after approval.

---

## 1. Audit: the 20 tells against the current app

Measured against the working tree, not guessed.

| # | Tell | Present | Evidence |
|---|---|---|---|
| 1 | Purple-to-blue gradient | No | Brand is `#308330`, green. Only 2 files mention `gradient` |
| 2 | Gradient hero text | No | — |
| 3 | Emojis in headings | No | — |
| 4 | Inter font everywhere | **Yes, variant** | `--font-sans: Instrument Sans` — the Laravel starter default. Same tell, different face |
| 5 | Colored border cards | Partial | Stock shadcn `card` + status colours |
| 6 | Glassmorphism cards | No | 0 uses of `backdrop-blur` |
| 7 | Low-contrast dark mode | **Yes** | `--background: hsl(0 0% 3.9%)` against `--border: hsl(0 0% 14.9%)`. Borders are nearly invisible |
| 8 | 3 icon boxes in a row | To verify per page | Dashboard and landing surfaces |
| 9 | Badge above the headline | To verify per page | — |
| 10 | Lucide icons everywhere | **Yes** | 40 files import Lucide |
| 11 | Untouched shadcn UI | **Yes** | 22 stock components in `components/ui`, unmodified |
| 12 | Fade-in on scroll | **Yes** | `tw-animate-css` imported; 21 files use `animate-*` / `transition-all` |
| 13 | Cursor-following beam | No | — |
| 14 | Buttons fade on hover | **Yes** | `transition-all` on stock button |
| 15 | Inconsistent spacing | **Yes** | No enforced scale; arbitrary Tailwind values in page components |
| 16 | Em dashes everywhere | No | 0 in `pages/` |
| 17 | Generic buzzword copy | Partial | Hungarian copy is mostly institutional already |
| 18 | Serif italic accents | No | — |
| 19 | Space Grotesk + Instrument Serif | **Yes, half** | Instrument Sans is the sibling of the named pair |
| 20 | Grain over a gradient | No | — |

**Eight real hits.** The app doesn't look vibecoded because of gradients and glassmorphism — it looks
vibecoded because it is a *stock Laravel + shadcn starter kit* with the brand colour swapped in.
The fix is therefore not decoration removal. It is committing to a typeface, a contrast ramp, a
spacing scale, and restyled primitives.

---

## 2. The replacement design system

### 2.1 A constraint that shapes everything

`app/Http/Middleware/SecurityHeaders.php` sets `font-src 'self' data:`. **Google Fonts will be
blocked by our own CSP.** All fonts must be self-hosted under `public/fonts` as woff2. This is
non-negotiable unless the CSP changes, and the CSP should not change.

### 2.2 Typography

Away from the starter default, and away from the named AI-default pair.

| Role | Face | Weights | Use |
|---|---|---|---|
| Headings | IBM Plex Serif | 500, 600 | Page titles, section headings, card titles |
| UI and body | IBM Plex Sans | 400, 500, 600 | Everything else |
| Numeric | IBM Plex Sans, `font-variant-numeric: tabular-nums` | 400, 500 | Points, deadlines, counts, every table column |

One superfamily, so the metrics agree. A serif for headings reads as an academic institution
rather than a SaaS landing page, which is what FAKT actually is. Not Instrument Serif, not Space
Grotesk, not Inter.

Type scale, fixed, no arbitrary sizes:

```
display  28px / 34px   600
h1       22px / 28px   600
h2       18px / 24px   600
h3       15px / 20px   600
body     14px / 21px   400
small    13px / 18px   400
caption  12px / 16px   500   uppercase, 0.04em tracking
```

### 2.3 Colour

Keep `#308330`. It is the FAKT green, it is already in the manifest `theme-color`, and it is the
opposite of the purple-to-blue tell. What changes is everything around it.

**Light** stays close to current. **Dark is rebuilt** — this is tell #7 and the current values fail:

Values below are **solved against the contrast targets, not chosen by eye.** The first draft of
this table was written by eye and three of its five pairs failed when measured; these are the
corrected figures.

```
                        was                   now
--background      hsl(0 0% 3.9%)        hsl(150 8% 7%)
--card            hsl(0 0% 3.9%)        hsl(150 7% 15%)
--muted           hsl(0 0% 16.08%)      hsl(150 6% 23%)
--border          hsl(0 0% 14.9%)       hsl(150 6% 33%)
--input           hsl(0 0% 14.9%)       hsl(150 6% 43%)
--foreground      hsl(0 0% 98%)         hsl(150 6% 95%)
--muted-foreground hsl(0 0% 63.9%)      hsl(150 5% 68%)
--primary         #64b864               #5cb85c
```

Light mode changes too: `--input` was `hsl(0 0% 89.8%)`, which is **1.26:1 against white** — a form
control boundary that barely exists. It becomes `hsl(150 8% 56%)`, measured at 3.05:1.

Measured result:

| Pair | Target | Measured |
|---|---|---|
| dark: card vs background | 1.2:1 | 1.26:1 |
| dark: border vs card | 2.0:1 | 2.04:1 |
| dark: input vs card | 3.0:1 | 3.04:1 |
| dark: body text vs card | 4.5:1 | 13.30:1 |
| dark: muted text vs card | 4.5:1 | 6.79:1 |
| light: border vs background | 1.3:1 | 1.36:1 |
| light: input vs background | 3.0:1 | 3.05:1 |

Card separation from background does the structural work; `--border` is decorative and sits at
2:1; `--input` is a real control boundary and clears the 3:1 non-text requirement.

The chart ramp `--chart-2..5` is currently stock shadcn teal / navy / sand / orange with no
relationship to the brand. Replace with a green-anchored sequence plus two neutrals.

### 2.4 Space, shape, elevation

Spacing: a 4px base, and **only** these steps: `4, 8, 12, 16, 24, 32, 48, 64`. Arbitrary Tailwind
values (`p-[13px]`) are banned; tell #15 is what happens without this rule.

Radius: stop using one value for everything. `--radius-control: 4px` (buttons, inputs, badges),
`--radius-surface: 10px` (cards, sheets, dialogs), `0` for table cells and list rows.

Elevation: exactly two levels. Flat with a hairline border for cards; one soft shadow for
overlays (dialog, dropdown, popover). No coloured borders, no left-accent stripes, no blur.
Status is carried by a small solid dot plus text, never by tinting a whole card.

### 2.5 Motion

**Deviation from the first draft.** The draft said "delete `tw-animate-css`". On inspection, 12
shadcn overlay components (dialog, dropdown, select, sheet, tooltip, navigation-menu) depend on its
`animate-in` / `animate-out` utilities for exactly the 120ms enter/exit this section asks for.
Deleting it would silently strip every overlay transition. It stays, with durations pinned to the
two tokens below. No scroll-triggered animation is used anywhere.

- Hover: 80ms background/border change. Never an opacity fade on a button (tell #14).
- Enter/exit for dialogs and toasts: 120ms, transform plus opacity.
- Everything inside `@media (prefers-reduced-motion: reduce)` drops to 0ms.

### 2.6 Icons

Lucide stays — rewriting 40 files buys nothing. What changes is the usage rule:

- Icons appear in navigation, in status indicators, and on icon-only buttons.
- Never decorative beside a heading. Never three-in-a-row feature boxes (tell #8).
- One size per context: 16px inline, 20px nav, 24px empty states.
- One custom-drawn FAKT mark for the sidebar header, so the identity is not a stock glyph.

### 2.7 Components

The 22 stock shadcn components get restyled **at the primitive level** so pages inherit the change:
`button`, `card`, `input`, `badge`, `table`, `sidebar` first. Untouched shadcn is tell #11, and
restyling primitives fixes it everywhere at once instead of page by page.

### 2.8 Copy

Hungarian institutional register. No emoji in headings, no em dashes in UI strings (currently
zero — keep it), no "platform / streamline / supercharge" buzzwords. Button labels state the
action: `Kinevezés visszavonása`, not `Tovább`.

---

## 3. Removing 2FA

23 files touch two-factor. Removal order:

**Backend**
- `config/fortify.php` — drop `Features::twoFactorAuthentication(...)` and the `two-factor` limiter
- `app/Providers/FortifyServiceProvider.php` — drop `twoFactorChallengeView`, the `two-factor` rate limiter
- `app/Http/Middleware/EnsurePrivilegedMfa.php` — delete
- `app/Http/Kernel.php` — drop the `mfa.leader` alias
- `routes/web.php` — drop `mfa.leader` from the dashboard middleware group
- `app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php` — delete
- `app/Http/Controllers/Settings/SecurityController.php` — strip 2FA branches
- `app/Models/User.php` — drop `TwoFactorAuthenticatable`, the hidden 2FA attributes
- `config/security.php` — drop `require_privileged_mfa`
- `.env.cpanel.example` / `.env.example` — drop `SECURITY_REQUIRE_PRIVILEGED_MFA`

**Database** — the `two_factor_*` columns are simply not created, since the schema is rebuilt from
scratch. `2025_08_14_170933_add_two_factor_columns_to_users_table.php` is deleted rather than
reversed.

**Frontend** — delete `ManageTwoFactor.vue`, `TwoFactorRecoveryCodes.vue`, `TwoFactorSetupModal.vue`,
`useTwoFactorAuth.ts`, `pages/auth/TwoFactorChallenge.vue`, the six generated
`actions/Laravel/Fortify/.../TwoFactor*.ts` wrappers, and the 2FA block in `pages/settings/Security.vue`.

**What this costs.** The Elnök account can read every member's personal data, hibapont record and
uploaded evidence. Password alone now guards that. The compensating controls already in the
codebase stay and should not be weakened: the 15-character breach-checked production password
policy, `SessionSecurity::revokeFor`, `password.confirm` on sensitive routes, and the audit log.
Your call, noted and implemented as asked.

---

## 4. KTSZT — Kurzustervező és -szervező Testület

Modelled from `KTSzT_hatarozat_2026_27_1_nyit_`.

### 4.1 Seats

Five at most (§2.1): two ex-officio, up to three elected.

| Seat | Source | How the app fills it |
|---|---|---|
| Szakmaiságért felelős Alelnök | §2.2.1 ex officio | **Derived automatically** from the active role assignment |
| Szakmaiság Teamvezető | §2.2.1 ex officio | **Derived automatically** |
| Elected member ×3 | §2.2.2 | Assigned by the Elnök, must be Szakkollégiumi Tag or Alumni Tag |

Deriving the two ex-officio seats matters: §4.2 says an elected member who later wins either office
converts to an ex-officio seat and their elected mandate ends. If seats were assigned by hand this
would silently break. The app enforces it.

Chair is the Szakmaiságért felelős Alelnök, falling back to the Szakmaiság Teamvezető (§3.3).

Mandate runs from the autumn Negyedéves gyűlés for one year (§3.1); it ends with the underlying
office for ex-officio members (§4.1) and on resignation (§4.3).

### 4.2 What the rank grants

Per §1.2, and nothing beyond it (§1.2.5 is explicit that the Testület has no other professional
authority):

- Plan courses, record the invited instructor, attach a provisional tematika and requirement set
- Open and close course applications; record the final course list and the course placement
- Decide course completion in disputed cases, and record the outcome with a reason
- Decide plagiarism cases under the Szakmai Határozat procedure
- Read the Szakmaiság records needed for the above

Not granted: appointments, approvals, finance, membership decisions, anything in another portfolio.

### 4.3 Conflict of interest

§1.2.6 bars a member from any decision touching their own submission, their own course completion,
or a plagiarism suspicion against them. The app enforces this structurally — a KTSZT member never
sees the decision control on a record where they are the subject or a co-author — rather than
relying on people to recuse themselves.

---

## 5. Projekt and Projektvezető

**The SZMSZ does not define a "Projekt Team".** There are exactly six Teams (§12.3), and the
Vezetőség is fixed at 11 people: 5 Elnökség + 6 Teamvezető. A seventh Team would break that count
and the Vezetőségi Stratégia process attached to it.

What the SZMSZ *does* recognise is `Projektvezető` as a tisztség for FAKT Diploma purposes (§8.4,
alongside Teamtag / Teamvezető / Alelnök / Elnök), and §12.5 lets the Elnökség create supplementary
posts for up to one year. So:

**A Projekt is its own entity, beside the six Teams, not among them.**

| Action | Who |
|---|---|
| Create a Projekt (name, purpose, semester, end date ≤ 1 year) | Elnök |
| Appoint the Projektvezető | Elnök |
| Revoke the Projektvezető | Elnök |
| Add and remove project members | **Projektvezető**, from approved Tag and Alumni accounts |
| Delegate tasks inside the Projekt | Projektvezető → project members |
| Close or archive the Projekt | Elnök |

Every appointment is a `role_assignment` row with `role = 'project_leader'` or `'project_member'`,
scoped to the projekt and the semester, with start date, end date and audit entry — so a Projekt
tisztség counts correctly toward the four semesters the FAKT Diploma requires, and the Elnökség's
end-of-semester `elfogadott` / `nem elfogadott` vote has something to attach to.

Membership of a Projekt does not make anyone a Teamtag and does not change the Vezetőség.

---

## 6. Permission matrix after the change

Replaces `docs/PERMISSIONS.md`, which is regenerated in Hungarian at implementation time.

| Action | Elnök | Alelnök | Teamvezető | Projektvezető | KTSZT | Tag | Alumni |
|---|---|---|---|---|---|---|---|
| Appoint/revoke Alelnök | yes | no | no | no | no | no | no |
| Appoint Teamvezető | yes | own portfolio | no | no | no | no | no |
| Assign Teamtag | yes | own area | own Team | no | no | no | no |
| **Create Projekt** | **yes** | no | no | no | no | no | no |
| **Appoint Projektvezető** | **yes** | no | no | no | no | no | no |
| **Manage project members** | yes | no | no | **own projekt** | no | no | no |
| Approve/reject registration | yes | no | no | no | no | no | no |
| Plan courses, set tematika | yes | Szakmaiság | no | no | **yes** | no | no |
| Open enrollment, set placement | yes | Szakmaiság | no | no | **yes** | no | no |
| Decide disputed completion | yes | Szakmaiság | no | no | **yes** | no | no |
| Decide plagiarism case | yes | Szakmaiság | no | no | **yes** | no | no |
| Delegate task | Alelnök, Projektvezető | own Teamvezetők | own Teamtagok | own project members | no | self | self |
| Lifecycle decision and status | yes | no | no | no | no | own request | no |
| Alumni directory, mentoring | yes | yes | yes | yes | yes | yes | yes |

Unchanged principles: every role carries start and end dates; the Elnök's administrative power
comes solely from an active `president` assignment; every record-id endpoint re-checks scope
server-side; hiding a control in the client is not a permission.

---

## 7. Parked: the compliance checklist

Out of scope by decision, recorded so it isn't lost. For an app holding member personal data,
academic records and uploaded evidence, the items that will eventually matter most are the
adatkezelési tájékoztató, a lawful basis for the evidence uploads, WCAG AA contrast (partly solved
by §2.3 above), alt text, and keyboard navigation. Say the word and it becomes a round of its own.

---

## 8. Implementation order

1. Wipe and rebuild the database; delete the 2FA migration (section 3)
2. Remove 2FA end to end (section 3)
3. Design tokens, self-hosted fonts, dark mode rebuild (sections 2.2–2.5)
4. Restyle the shadcn primitives (section 2.7)
5. KTSZT rank, seat derivation, conflict-of-interest rule (section 4)
6. Projekt and Projektvezető (section 5)
7. Sweep the pages for tells 8, 9, 15 and regenerate `docs/PERMISSIONS.md`

Steps 1–2 and 3–4 are independent and can land in either order. Steps 5 and 6 both touch
`role_assignments` and should land together.
