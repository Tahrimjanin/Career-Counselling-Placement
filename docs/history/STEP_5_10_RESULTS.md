# Step 5–10 validation report

Date: 29 October 2026. Input: user-supplied `Career_Counseling_Placement.zip`.

## Executed checks

- PHPUnit: 56 tests, 368 assertions, passing on SQLite and MariaDB.
- SQLite and MariaDB 10.11: backend suite, fresh migrate/seed twice, seed repeatability, rollback and remigrate.
- Concurrent MariaDB allocation: two independent PHP processes compete for one advisor place; exactly one assignment succeeds and the second receives a capacity error.
- Vitest / Testing Library: 10 tests for role-aware login/registration, inline role errors, logo, submitted status, server field validation and pending-request disabling.
- ESLint and Vite production build. React/Vite plugin versions now agree; the previous deprecation warnings are gone.
- Laravel config/route caching and 64 unique method/URI routes.
- Chromium: four-role login, 22 role screens, full CV review → post/approve opportunity → apply → Admin forward → recruiter interview/offer → student accept → Admin placement record flow.
- Chromium: eight-step profile wizard, refresh persistence, simulated 419 refresh/retry, Admin student verification, registration with a personal email, verification link opened while signed out and preserved through login.
- Horizontal overflow checks at 320/375/768/1024/1440 for application workspace; profile at 320/768/1440. No unhandled JavaScript errors or HTTP 500 responses in the completed journeys. Initial anonymous `/me` 401 and the deliberately simulated 419 are expected test traffic.
- npm audit: zero reported vulnerabilities. Composer audit found symfony/mime v7.4.19 advisory PKSA-wtxr-p26d-nn42; upgraded to v7.4.22; Composer then reported no security vulnerability advisories.

## Covered backend regressions

Four correct roles and all role mismatches; account creation privilege boundary; personal-email registration; original verification/reset/logout/throttle/password tests; profile completion/readiness; assignment preferences versus confirmation; capacity and unique active assignment; past/overlapping counselling; assigned-student and cross-user restrictions; milestone permissions; CV review/version immutability; file extension/MIME spoof and size checks; company isolation; approval visibility; idempotent saved opportunities; each eligibility gate; duplicate application; every legal state-machine transition and every illegal state pair; correction/rejection reasons; pre-forward recruiter restriction; interview overlap and feedback timing; expired offer; duplicate acceptance; placement transaction; retired legacy mutation endpoints; deadline closure and advisor availability.

## Bugs found and corrected during this phase

Role-agnostic registration link, hidden role mismatch message, university-email-only restriction, mismatched temporary-password hint, missing advisor profile on account creation, Vite plugin compatibility, unguarded legacy mutations, saved-opportunity FK mismatch, ambiguous joined advisor IDs, unnormalized MySQL offer timestamp, verification link lost through login, stale dashboard labels, private CV response paths, MIME spoof acceptance, missing candidate overlap guard, phone-regex regression, and duplicate form IDs.

## Not claimed

Native Windows/XAMPP execution; Edge/Firefox/manual screen-reader QA; external SMTP delivery; all screen/state combinations; 80% measured coverage; full Step 11–15 completion. No real user database was reset or modified during development.
