# Result — work-order Steps 3–5 only

## Delivered

| Step | Result |
|---|---|
| 3 — Auth foundation | Stateful Sanctum CSRF/session transport, role-validated login, throttling, student registration with configured university email domains, email verification/resend, reset password, forced temporary-password change, active-account gating, Role enum, User/Company policies, Form Request/Resource patterns; corresponding SPA screens and guards. |
| 4 — Database | Six additive migrations covering profile/advising/CV/placement/support foundations, original rollback repair, safe legacy conversion, 42 tables including framework and compatibility tables, six component seeders, 11 explicit workflow fixtures, ERD and data dictionary. |
| 5 — Profile and verification | Eight-step student wizard, per-step saving/resume, missing-field checklist, independent readiness calculation, editable own contact/company profiles, Admin paginated verification queue and detail/reason/decision, server-side university verification gate on application submission, audit trail, ownership/validation tests. |

## Verification evidence

| Check | Result |
|---|---|
| PHP 8.3.6 / PHPUnit 11.5.57 — SQLite in memory | **35 tests, 109 assertions passed** |
| MariaDB 10.11.14 — same PHPUnit suite | **35 tests, 109 assertions passed** |
| SQLite and MariaDB: migrate:fresh --seed ×2; repeat db:seed | Passed |
| SQLite and MariaDB: migrate:rollback followed by migrate --seed | Passed |
| MariaDB legacy-data conversion fixture | 6 preservation checks passed: department/profile, active assignment, skill pivot, application state/owner, CV path, advisor note |
| Generated one-active-advisor unique constraint | Tested, duplicate active assignment rejected |
| Cookie login/logout, role mismatch, throttle, email signing/expiry, reset single use, forced change | Covered by backend tests |
| Cross-role verification, profile ownership, company scope, nested ownership spoof, unverified application block | Covered by backend tests |
| Browser: Chromium 134 / Playwright 1.51.1 | Cookie login, wizard 1–8, saved position after reload, one-shot CSRF 419 refresh/retry, admin approval passed |
| Browser: 320 / 768 / 1440px | Profile-screen horizontal-overflow checks passed; not a whole-site accessibility audit |
| Browser JavaScript exceptions | None in the exercised flow. Expected signed-out 401 responses and intentionally injected 419 are visible in the network console. |
| npm run build | Passed, 98 modules; inherited Vite/plugin deprecation warnings remain |
| Composer install | Passed with the existing lock file |
| Pint --test --dirty | Passed for changed/new PHP files |
| git diff --check | Passed |
| Laravel route:list, config:cache, route:cache | Passed; 42 non-vendor routes listed |

Tests were executed in Linux against SQLite and MariaDB, not on a physical Windows/XAMPP installation. No coverage percentage or full-platform acceptance is claimed. API tests use fake notifications; the browser smoke exercises cookie/profile/verification-admin flow, not email delivery through a real SMTP provider.

## Scope boundary

Step 2 remains undone, as requested: folder/package names, full XAMPP setup and its Vite 8/plugin 4 peer-dependency repair were not included. Use the documented temporary `npm ci --legacy-peer-deps` workaround to reproduce this incremental build. No Step 6–15 workflow, final SQL dump, Windows setup scripts, full visual redesign or full acceptance audit has been performed.

Schema tables and seeded states for later modules are Step 4 foundations only. Existing CV/advisor/application/recruiter prototype controllers were touched only where the Role enum and auth gates required compatibility. They still have the limitations recorded by the original audit. In particular, the future workflow service, CV versioning, allocation, sessions, notifications scheduler and full eligibility UI remain later work.

## Read next

- [Run and upgrade instructions](STEP_3_5_RUN.md)
- [API](STEP_3_5_API.md)
- [Design decisions](DECISIONS.md)
- [ER diagram](ERD.md)
- [Data dictionary](DATA_DICTIONARY.md)
- [Changed files](STEP_3_5_CHANGED_FILES.md)

Work stops at Step 5.
