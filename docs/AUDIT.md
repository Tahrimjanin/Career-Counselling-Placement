# AUDIT — Career Counselling and Placement → Career Counselling and Placement (CCP)

Scope: Step 0 (read the master prompt) and Step 1 (audit) of the work order. **No application code was changed in this step.**
Date of audit: 2026-10-29. Archive audited: the original uploaded archive (146 entries, 600 KB, no `.git` directory).

## 1. How this audit was done

| Method | What was covered |
|---|---|
| Static read | Every hand-written file: 8 controllers, middleware, 8 models, 6 migrations, seeder, factory, routes, bootstrap, config deltas, `.env*`, `composer.json/lock`, `phpunit.xml`, 15 React files, `index.css`, 3 READMEs, `setup-backend.sh`. Framework stubs (`welcome.blade.php`, stock config) were skimmed for deviations only. |
| Executed in a scratch copy | `npm ci`, `npm run build`, `npm audit`; `composer install`; `migrate:fresh --seed` ×2; `db:seed` ×2; `migrate:rollback` + `migrate`; `route:list`; `php artisan test`; `pint --test`; 11 live HTTP probes against `php artisan serve` (tagged **[R]** = reproduced below). |
| Computed | WCAG contrast ratios for every colour pair in `index.css`. |

**Limits (be honest):** run on PHP 8.3.6 / Node 22.22 / **SQLite**, not on XAMPP + MySQL. No browser was used, so responsive/keyboard findings are static (**[S]**). `composer audit` could not reach Packagist from the sandbox; `npm audit` = 0 vulnerabilities.

## 2. Verdict

The project is a **working prototype skeleton**: 28 API routes, a 4-role model, and an Admin-review-before-company flow that works on the happy path. It is **not runnable from its own README** (3 blockers), has **no tests, no authorization policies, no state machine, no throttling, and 20+ confirmed logic/security bugs (most reproduced by running the code)**. By a rough estimate it implements about **15 %** of the CCP specification (sections 4–6). Keep list and rebuild list are in §9.

## 3. Blockers (project cannot be installed/run as documented)

| ID | Finding | Evidence | Fix in |
|---|---|---|---|
| B1 | `npm ci` / `npm install` **fails with ERESOLVE** on a clean clone: `vite ^8.3.5` (lock: 8.3.5) vs `@vitejs/plugin-react 4.7.0` whose peer range is `vite ^4–^7`. Works only with `--legacy-peer-deps` (build then passes: 95 modules, 255 KB JS). Vite 8 also needs Node `^20.19 \|\| >=22.12`. | [R] | Step 2 |
| B2 | `setup-backend.sh` and `career-counselling-placement/README.md` rely on a **`backend-overlay/` folder that does not exist** in the archive, and run `composer create-project` into an already-existing `ccp-api/`. Bash script is unusable on plain Windows/XAMPP. README says "Laravel 11"; lock = **12.70.1**. | [S] | Step 2, 15 |
| B3 | `.env.example` is **SQLite**, `APP_NAME=Laravel`, `SESSION_DOMAIN=null`, no `FRONTEND_URL` / `SANCTUM_STATEFUL_DOMAINS`. Not XAMPP/MySQL ready. | [S] | Step 2 |
| B4 | **`migrate:rollback` then `migrate` fails**: `down()` of `create_career_tables` never drops `companies` nor the columns added to `users` → `table "companies" already exists`. | [R] | Step 4 |
| B5 | Seeder is **not idempotent**: second `db:seed` → `UNIQUE constraint failed: users.email`. (`migrate:fresh --seed` ×2 does pass.) | [R] | Step 4 |

## 4. Security findings

| ID | Sev | Finding | Where | Fix in |
|---|---|---|---|---|
| S1 | High | Sanctum **bearer token stored in `localStorage`** (XSS-stealable). Tokens never expire (`sanctum.expiration = null`), one new token per login is never pruned, `logout` swallows errors so the token can stay valid. | `api.js:4,10`, `AuthContext.jsx:8-16` | 3 |
| S2 | High | **No rate limiting anywhere.** 12 wrong logins → 12×422, never 429 (Laravel 12 `api` group has no throttle unless `throttleApi()` is called). | `bootstrap/app.php` [R] | 3 |
| S3 | High | **Stored XSS vector:** interview `link` is `nullable\|string`; `javascript:alert(1)` is accepted (201) and rendered as `<a href={i.link}>` for the student. | `InterviewController@store`, `Interviews.jsx:15` [R] | 10 |
| S4 | High | `register` returns a working token immediately; **no email verification**, no password reset, no forced password change, no account status/disable, admin sets other users' passwords in clear. | `AuthController`, `PeopleController@createUser` [R] | 3 |
| S5 | Med | Role selected on the login screen is checked **only in the browser**; server issues a token for any valid credentials, then the client calls logout. | `Login.jsx`, `AuthController@login` | 3 |
| S6 | Med | All 8 models use `$guarded = []`. Safe today only because controllers pass validated arrays. | `app/Models/*` | 3 |
| S7 | Med | CV JSON **exposes the internal storage `path`**; client file name stored unsanitised; limit 4 MB and `.doc` allowed (spec: 5 MB, PDF/DOCX); no upload throttle; `max(version)+1` race and **no `unique(user_id, version)`**. Good: disk is private (`storage/app/private`) and download is authorised. | `CvController`, migration [R] | 7 |
| S8 | Med | No `config/cors.php` (framework default: origins `*`, `supports_credentials=false`) → cookie auth impossible until configured. No security-headers middleware. `APP_DEBUG=true` in example. | config | 3 |
| S9 | Med | Unvalidated query params cause **500**: `/opportunities?q[]=x` [R]; also `type`, `review`, `role`. LIKE wildcards (`%`, `_`) not escaped. | `OpportunityController`, `ApplicationController@queue`, `PeopleController@users` | 3/8 |
| S10 | Low | No audit trail for admin decisions, company verification, user creation, allocation. | all admin actions | 12 |
| S11 | Low | Demo credentials printed on the login page and the text "seeded demo CV has no real file" shown in the UI — both would ship to production. | `Login.jsx:53`, `ui.jsx:26` | 13 |
| S12 | Low | `logout` uses `currentAccessToken()->delete()`; this **will break** under SPA cookie auth (transient token). | `AuthController` | 3 |

## 5. Authorization (object-level) findings

| ID | Sev | Finding | Fix in |
|---|---|---|---|
| A1 | High | **No Policies/Gates.** Only `RoleMiddleware` plus ad-hoc inline checks in 3 controllers. Spec requires `authorize()` on every action + IDOR tests. | 3 |
| A2 | High | Advisor can **re-review an already approved CV** (`approved → changes_requested`, 200) [R]. Approved CVs must be immutable because applications point to `cv_id`. | 7 |
| A3 | High | Recruiter still sees (and acts on) an application the **admin rejected after forwarding**: `forCompany` checks only `forwarded_at` [R]. | 9 |
| A4 | Med | Student notes are saved `private=false` → **visible to the advisor**, while the UI/README call them personal. The advisor page claims "authorized admin policy" access that is not implemented. | 6 |
| A5 | Med | Company verify is a **toggle** (double-click flips it back). Un-verifying does not close its open opportunities or block its recruiters. | 8 |
| A6 | Low | `createUser` accepts `company_id` for non-recruiters; email not lower-cased; `name` has no max length. | 3/12 |

**Checked and correct (keep the idea, re-implement as Policies):** CV download covers student/advisor/admin/recruiter correctly; advisor CV review requires the assigned student; advisor notes require the assigned student; recruiter candidate/status/interview actions are company-scoped.

## 6. Workflow and business-logic bugs

| ID | Sev | Finding | Fix in |
|---|---|---|---|
| W1 | High | **No state machine.** Admin: `rejected → approved → forward` succeeds, leaving `status='Rejected'` + `admin_review='approved'` [R]. `forward` is repeatable (re-stamps `forwarded_at`, duplicate alert) [R]. Recruiter can jump **`Rejected → Offer`** [R]. Scheduling an interview overwrites any status with `Interview`. | 9 |
| W2 | High | **Expired opportunities** are listed and **application is accepted (201)** [R]. No status/deadline check, no scheduler to close, none of the eligibility rules (profile, verification, advisor, recommendation, CGPA) exist. | 8, 9 |
| W3 | High | Opportunity created by a recruiter is **`open` immediately** (DB default) — no Admin approval step [R]. Types lack *Part-time*. `skills` elements not validated. | 8 |
| W4 | High | New application shows status **"Applied"** and the toast says "Applied to X" — spec requires **"Submitted to Admin"**. University rejection and company rejection share the same `Rejected`. No reason, no "corrections requested", no withdraw, no status history. [R] | 9 |
| W5 | High | **Offer is a dead end:** "Make offer" only sets a status; student cannot accept/decline; no placement record. | 10 |
| W6 | Med | **Timezone bug:** recruiter's `datetime-local` (no tz) is stored as UTC; API returns `…T10:00:00Z`; a student in Dhaka sees it **6 h later** [R]. `timezone` is hard-coded `'UTC'`, no `APP_TIMEZONE`, `fmt()` uses browser locale. | 2, 10 |
| W7 | Med | No double-submit protection (buttons never disabled while busy). Admin review is not idempotent → duplicate alerts. Duplicate-application race returns 500 from the unique index instead of 422. | 9, 13 |
| W8 | Med | Interviews: no duration/type/interviewer/prep notes, no overlap check, no reschedule/cancel/feedback; one interview max per application. | 10 |
| W9 | Med | Allocation is a bare dropdown: no preferences, capacity, transaction, or "one active advisor" constraint; reassigning silently changes CV-review ownership. | 6 |
| W10 | Low | Spec deviation to decide: current UI has **separate** *Approve* and *Forward to company*; spec has **Approve & forward** (still two audit events). | 9 |

## 7. Database and API findings

| ID | Sev | Finding | Fix in |
|---|---|---|---|
| D1 | High | **Only 8 application tables exist (users, companies, opportunities, cvs, applications, interviews, notes, alerts) vs ~33 specified.** Missing: profiles, skills, preferences, assignments, sessions, plans, cv_reviews, status history, offers, placements, advisor_notes, reminders, audit_logs, settings, standard notifications. Custom `alerts` has no mark-as-read or list endpoint; column `read` is a MySQL reserved word (Laravel quotes it; raw SQL would break). | 4 |
| D2 | Med | Missing indexes/constraints: `unique(cvs.user_id,version)`, `opportunities(status,deadline)`, `applications.status`, `alerts(user_id,read)`; `applications.cv_id` FK has no `onDelete`; no soft deletes; `role`/statuses are free strings → an unknown role makes `DashboardController` throw `UnhandledMatchError` (500) and `Layout` crash (`NAV[user.role]`). | 4 |
| D3 | Med | Naming/shape differs from spec: `applications.user_id` (spec `student_id`), `notes` (spec `advisor_notes`), `cvs` lacks `content/source/mime/size/reviewed_by`, skills stored as JSON on users/opportunities instead of tables. Old-data conversion migration will be needed. | 4 |
| D4 | Med | `UserFactory` is stock (no `role`, etc.) and **`User` lacks `HasFactory`** → `User::factory()` throws `BadMethodCallException` [R]. Tests cannot use factories yet. | 3, 14 |
| D5 | Low | Seed data is thin: 1 student, 1 verified company, all four opportunities share skills `['React','Laravel']`, seeded CV points to a **non-existent file**, no workflow-stage data. | 4 |
| D6 | Info | `phpunit.xml` targets SQLite `:memory:`. Spec'd generated column + FULLTEXT are MySQL-only → a MySQL test DB (or guarded migrations) is needed. Record in `DECISIONS.md`. | 4, 14 |
| P1 | High | **No Form Requests, API Resources, pagination, `/api/v1` prefix, or `{data,meta}` envelope.** Every list is `->get()`; raw models are returned (e.g. `/users` returns `skills`, `advisor_id`, `company_id`). | 3 |
| P2 | Med | `AppServiceProvider` is empty: no `preventLazyLoading` / `shouldBeStrict`. | 3 |
| P3 | Med | No scheduler/queue jobs; `routes/console.php` only has `inspire`. | 11 |

## 8. Frontend, UI and accessibility findings

| ID | Sev | Finding | Fix in |
|---|---|---|---|
| F1 | High | **No component library.** No Button/Input/Modal/Toast/ConfirmDialog/Skeleton; only `Card`, `Badge`, `Msg`. Loading = text or blank; no retry; no field-level errors (`err()` returns the first message only); no confirm on Reject / Revoke / Make offer / Allocate. | 13 |
| F2 | Med | `useFetch` has **no abort/race handling**: `Opportunities` fires a request per keystroke and a stale response can overwrite a newer one [S]; stale data stays on URL change; no loading flag. | 13 |
| F3 | Med | `openCv`: `window.open(blobURL)` after an `await` (popup blockers), URL never revoked, **every** error (403/500) is shown as "File not available (seeded demo CV…)". | 7 |
| F4 | Med | `Opportunities`: `window.prompt` for the cover note; no details page, save, skill-match %, remote/paid/skills filters, sort; Apply enabled for expired items. | 8, 9 |
| F5 | Med | "CV Builder" is **upload-only** (no builder, preview, print/PDF); every upload auto-becomes `pending` (no submit step); no double-click guard. | 7 |
| F6 | Med | `CvReview` lists **all** CVs incl. approved ones with live Approve/Request buttons (see A2); no strengths/improvements/placement recommendation. | 7 |
| F7 | Med | `Queue`: reject has **no reason**, no detail page (CV/eligibility/history), no corrections action, no pagination. | 9 |
| F8 | Med | `Layout`: no off-canvas drawer (sidebar just wraps above content ≤800 px), no skip link, unlabeled `nav`, no bell/profile menu/page title; no lazy routes and **no ErrorBoundary** (any render error = white screen). | 13 |
| F9 | Med | `Login`: `<a href="#">` used as a button, two `<h1>`, errors use `role="status"` (should be `alert`), no `autocomplete`, no Forgot password, register has no department/confirm-password. | 3, 13 |
| F10 | Low | `Users`: form not reset after create (second click → 422), no search/pagination/disable/reset, raw lowercase roles. `Companies`: toggle without confirm or error handling (unhandled rejection). `Post`: skills as free text, missing spec fields, says "Opportunity posted." although approval is required, no *My Opportunities*. | 8, 12 |
| F11 | Info | **Screens missing vs spec:** landing, register route, verify/forgot/reset, profile wizard, notifications, sessions, career plan, allocation, reports, audit log, settings, all detail pages. | 5–13 |

### Contrast results (WCAG 2.1 AA: 4.5 : 1 text, 3 : 1 UI)

| Pair (from `index.css`) | Ratio | Result |
|---|---:|---|
| **White on `--teal #078a83`** (buttons, active nav) | **4.22** | **Fail** (normal text) |
| **Teal `#078a83` on white** (`.role` 12 px label) | **4.22** | **Fail** |
| Ghost-button border `#3b5080` on navy | **1.89** | **Fail** (3 : 1 UI) |
| Disabled text `#8a8f98` on `#dde6ef` | 2.57 | Exempt, but needs visible reason text |
| Muted `#556b83` on `#f2f6fa` / white | 5.07 / 5.50 | Pass |
| Nav `#bfccdb` / brand `#adc6d3` on navy | 9.20 / 8.43 | Pass |
| Badges success / attention / info | 6.13 / 5.76 / 13.10 | Pass |
| *Spec:* white on `--teal-700 #0A7F7A` | 4.85 | Pass (use this) |
| *Spec:* white on `--teal-500 #0FA3A3` | 3.09 | Fail — never put white text on it |

Other CSS findings: only 7 CSS variables (`--orange`, `--green` unused) and ~10 hard-coded hex values; no spacing/type/radius tokens; no dark-mode structure; Inter is named but not bundled; one breakpoint (800 px); 5-column tables sit in a card with no overflow container → horizontal page scroll expected at 320 px **[S]**; no `:focus-visible` style; no favicon/meta description. **Good and kept:** 44 px touch targets, navy/teal direction, labels bound to inputs on most forms, no `dangerouslySetInnerHTML`, no `console.*`, no TODO/lorem.

## 9. Documentation, config hygiene, and claims

| ID | Finding |
|---|---|
| R1 | Root `README.md` says "UI/UX prototype, no backend claimed" but the repo contains a Laravel API; it also **lists features that are not built**: save internships, skill-based matching %, reminders, notifications, interview feedback, advisor career sessions, analytics, reports, settings, recruiter approval, student profile. |
| R2 | `career-counselling-placement/README.md` mixes brands ("Career Counselling and Placement (Career Counselling and Placement)"), says Laravel 11, points to `backend-overlay/`. `ccp-api/README.md` is the stock Laravel README with sponsor list. |
| R3 | Dead scaffolding in the API: `package.json`, `vite.config.js`, `resources/css|js`, an 82 KB `welcome.blade.php` served at `/`; `composer.json` still named `laravel/laravel`; `laravel/pail` (needs `pcntl`, not on Windows) and `laravel/sail`; `composer run dev` would fail on XAMPP/Windows **[S]**. |
| R4 | `ccp-web/.env` **is in the archive** (URL only, no secret) and `.gitignore` does not exclude `.env`; no root `.gitignore`; archive has no `.git`. |
| R5 | Tests: only the two stock examples (pass, but `ExampleTest` just checks the Laravel welcome page). `pint --test` fails on 5 files (`User.php`, `bootstrap/app.php`, the career migration, `DatabaseSeeder.php`, `routes/api.php`). |

### Old-brand leftovers (for the Step 2 grep gate)

`grep -ri "career counselling and placement|career-counselling-placement|career_counselling_placement|ccp-api|ccp-web"` → **17 lines in 4 files**: `README.md` (5), `career-counselling-placement/README.md` (6), `career-counselling-placement/setup-backend.sh` (5), `career-counselling-placement/ccp-web/package.json` (1). Folder names `career-counselling-placement/` and `ccp-api/` and `APP_NAME=Laravel` also need renaming. **Already new-brand:** `index.html` title, sidebar brand, login hero/subtitle. The DB name `career_counselling_placement` appears only in docs/scripts, not in code.

## 10. Keep / replace

**Keep:** Laravel 12 + Sanctum, four roles, Admin-review-before-company idea, React/Vite SPA layout, navy/teal direction, 44 px targets, object-level checks (as Policies), `RoleMiddleware` as a coarse gate, the `forCompany` scope idea.
**Replace:** auth transport, DB schema, workflow logic, all controllers/validation/responses, seeders/factory, all pages except as visual reference, CSS (tokens), README/scripts.

## 11. Fix plan by work-order step

| Step | Findings closed |
|---|---|
| 2 Rebrand + XAMPP | B1, B2, B3, R1–R4 (docs/scripts), brand grep, W6 (`APP_TIMEZONE`) |
| 3 Auth/foundation | S1, S2, S4, S5, S6, S8, S9, S12, A1, A6, D4, P1, P2, F9 |
| 4 Database | B4, B5, D1–D3, D5, D6 |
| 5–6 Profile, advisors, sessions, plan | A4, W9, F11 (part) |
| 7 CV lifecycle | S7, A2, F3, F5, F6 |
| 8 Opportunities | W2 (part), W3, A5, F4, F10 |
| 9 Application workflow | A3, W1, W2, W4, W7, W10, F7 |
| 10 Recruiter/interviews/offers | S3, W5, W6, W8 |
| 11 Notifications/scheduler | P3 |
| 12 Admin | S10, F10 |
| 13 Landing/design/a11y | S11, F1, F2, F8, contrast fixes, tokens, responsive |
| 14–15 Tests, QA, docs | R5, remaining gates |

## 12. Assumptions made in this step

1. The archive has no `.git`, so a local repository was created, the **untouched** archive committed on `main`, and branch `feature/ccp-upgrade` cut from it (this audit is the first commit on that branch).
2. Scratch copies in `/tmp` were used for all executed checks; the project tree itself is unmodified apart from adding `docs/AUDIT.md`.
3. Findings tagged **[S]** are static (not browser- or MySQL-verified) and will be re-checked in Step 14.

## Final Steps 1–10 re-audit

See STEP_1_10_RESULTS.md for current findings/fixes and executed evidence. Earlier findings above describe the original snapshot; current scope/decisions are in DECISIONS.md. Retired duplicate controllers, hardened privacy/authorization, completed rename, repaired setup and added regression checks.
