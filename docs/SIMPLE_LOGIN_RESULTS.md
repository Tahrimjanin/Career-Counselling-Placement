> Historical snapshot. Current features and measured results: FINAL_RESULTS.md.

# Simple login revision — 29 October 2026

User request: defer cookies and email verification to focus on internal workflows.

## Root cause from supplied log

The backend reported production.ERROR: No application encryption key has been specified. This was an environment-loading/missing-key failure, not proof of a CORS failure. The requested authentication change removes the API's cookie/CSRF dependency. Setup additionally prepares the correct .env and preserves existing nonempty APP_KEY values.

## Changes

- Removed stateful API middleware; protected API routes use Sanctum bearer tokens with no web-session fallback. Disabled the unused Sanctum cookie route.
- Login/registration return an eight-hour token after password/workspace validation. Database stores token hashes; only the issuing response returns the plaintext token.
- Browser uses tab sessionStorage, Authorization header, no credentials/cookie bootstrap and no CSRF retry. Initial signed-out load makes no unnecessary /me request.
- Logout revokes its token. Password change invalidates other tokens; reset invalidates all tokens. Existing suspension and permissions remain.
- Email checks default off across backend/frontend, registration and Admin staff creation. Existing unverified accounts can use their workspace without changing email_verified_at. Student approval/eligibility remains enforced.
- Retained optional email-verification implementation behind CCP_REQUIRE_EMAIL_VERIFICATION for later work; tested when enabled. No verification mail sent in default mode.
- Updated setup, start instructions and regression/browser tests. Existing DB and private uploads are preserved by update instructions.

## Executed tests

- PHP: **70 tests / 717 assertions**, passing on SQLite and dedicated MariaDB test database.
- Frontend: **12 tests**, lint and production build pass.
- Browser: token login across four roles, eight profile steps, reload, student approval, CV review, opportunity posting/approval, application forwarding, interview, offer acceptance and placement; registration enters dashboard without email verification, reload preserves login. Asserted zero authentication cookies and zero csrf-cookie requests. Responsive widths 320/375/768/1024/1440.
- Regression cases: no APP_KEY cookie dependency for API login/profile; unverified old/new accounts; no verification mail; invalid/expired/revoked tokens; suspension after token issue; password change/reset revocation; Admin approval without email verification.
- Dedicated database checks: repeated migrations/seeds, rollback/remigrate, concurrent capacity allocation, SQL export/import. No real user database touched.
- Formatting/config/route validation and archive hygiene checked before delivery. No .env, tokens, vendor/node_modules, runtime logs or private uploaded files included.

Native Windows/XAMPP, all browser engines, real SMTP and coverage percentage are not claimed. Earlier cookie/419-specific results belong to the previous version and are superseded for this release.
