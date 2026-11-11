# Career Counselling and Placement

Four-role career counselling and placement workspace: Laravel API, React/Vite frontend and MySQL/MariaDB. Start with **START_HERE_BN.md** for Windows/Git Bash commands.

## What is implemented

- Student, Career Advisor, Recruiter and Admin can register with ordinary valid email addresses and sign in through the same role selector. Students are active immediately. Other new accounts sign in to an approval screen until an existing active administrator approves them in **Users → Pending approval → Approve account**. Recruiter signup creates an unverified company; Admin separately verifies it in Companies before opportunities can be posted.
- Profile wizard, separate completion/readiness scores, student verification, advisor preferences and controlled allocation/capacity, counselling sessions, career plans, CV builder/private versioned uploads/review, approved opportunities and eligibility checks.
- Application state machine and history, university forwarding, recruiter shortlist/interviews/feedback, offers and placement records.
- Own private profile image upload/removal in **My profile**; the uploaded image appears beside the name in the top-right header for every role. JPG/PNG/WebP, maximum 2 MB and 4096×4096. Initials show until an image is uploaded.
- Notification bell, All/Unread/Deadlines/Interviews filters, single/all read actions, queued mail copies, session/interview reminders, saved/applied opportunity deadline reminders, repeating application follow-ups.
- Admin can create Student, Advisor, Admin and Recruiter accounts, approve/suspend accounts, reset passwords, promote Advisor accounts to Admin, and remove accounts. Direct Admin account creation remains available. Removal revokes access and soft-deletes the account, preserving its profile and workflow history. Company management, date-filtered reports and CSV, audit log and settings are also available. Admin Settings can edit public page copy, FAQs, contact/policy text, section visibility/order, announcements, university/site branding, logo and theme colors. Logo uploads are validated and served publicly; admin-only edits are audited.
- Advisors can maintain a profile with their professional expertise and areas of interest; interest areas are shown on student-facing advisor cards.
- Public landing page keeps live counts, advisor and opportunity data, while administrators control its editable content and which sections appear on the home page versus their standalone views.
- Responsive navigation, keyboard menu controls, semantic labels and lazy loading of notification/admin tools.

## Authentication decision retained from the user

Bearer tokens in sessionStorage, eight-hour expiry, no login cookies or CSRF bootstrap. Email verification is deferred (`CCP_REQUIRE_EMAIL_VERIFICATION=false`), including existing accounts; no fake verified timestamp is added. Password reset, role mismatch rejection, throttling, permissions, suspension and forced temporary-password changes remain. Logging out revokes the current token; password reset revokes all tokens. This development choice should be reviewed before public deployment.

## Requirements and XAMPP

PHP 8.2+ with pdo_mysql, mbstring, openssl, fileinfo, zip and XML/DOM; Composer; Node 22.12+ and npm; MySQL/MariaDB (XAMPP). Start XAMPP MySQL. Run `php.exe --ini` and ensure the CLI PHP has `extension=pdo_mysql` enabled in the **loaded** php.ini. Do not assume XAMPP Apache and CLI use the same PHP.

Create `career_counselling_placement` using `ccp-api/database/sql/create_database.sql` in phpMyAdmin. Default configuration is host 127.0.0.1, port 3307, root with blank password; use the actual credentials/port of your local MySQL. The helper creates **ccp-api/.env**, not `.env.example`. Never paste a password into a public issue.

Fresh install: run `scripts/setup.bat` from the project folder. It installs locked dependencies, prepares .env and APP_KEY, and migrates/seeds the skill catalog; it does not create demo accounts. In `ccp-api`, run `php.exe artisan ccp:bootstrap-admin` to securely configure the first active administrator. Then run `scripts/start.bat` to launch API, frontend, queue worker and scheduler in four windows. Open http://localhost:5173. Keep both browser hosts as localhost, not a mixture of localhost and 127.0.0.1.

Existing install: copy your old `ccp-api/.env` and `ccp-api/storage/app` into this version, preserving APP_KEY and private CVs/photos. Back up your database, then run `scripts/update.bat`. It runs additive migrations, soft-deletes existing `@demo.test` accounts and revokes their tokens while preserving their work/history data; it does **not** reset the database. Never use migrate:fresh or import the demo SQL over an existing database.

The SQL dump is a historical alternative for a **fresh empty** database and may contain demo fixture records. If importing it, run the setup/update migrations before allowing sign-in; the cleanup migration disables those demo accounts. Prefer migrations for upgrades. phpMyAdmin is only a UI for the same SQL import; native Windows/phpMyAdmin interaction was not executed in this environment.

## Accounts

No demo accounts or shared demo passwords are created by normal setup. The first active administrator is created with `ccp:bootstrap-admin`; thereafter, an active administrator can create accounts for any role or promote an existing account from **Users**. Public self-registration cannot grant administrator access. Demo fixtures are limited to local/testing environments.

## Background work

`php.exe artisan queue:work --tries=3` sends queued notifications. `php.exe artisan schedule:work` checks reminders every minute and closes expired opportunities hourly. `scripts/start.bat` starts both. Do not start duplicate scheduler instances. Mail defaults to `log`: messages are written to `ccp-api/storage/logs/laravel.log`, **not delivered to Gmail**. Real SMTP remains an operator configuration task. In-app notices appear immediately even before the mail worker runs.

Session/interview notices are deduplicated per scheduled timestamp at the 24-hour and 1-hour windows. A changed schedule can notify again. Deadline notices go to students who saved/applied to that opportunity, at three and one days before its Dhaka deadline. Missed windows while the scheduler is stopped are not replayed. Follow-ups repeat every 1–90 days and stop for final application states.

## Tests

Backend: `php.exe artisan test`; `php.exe vendor/bin/pint --test`.
Frontend: `npm test`; `npm run lint`; `npm run build`.
Browser scripts in `ccp-web/tests/steps3-5.smoke.cjs`, `steps5-10.smoke.cjs`, `steps11-15.smoke.cjs` require Playwright and both servers on 8000/5173, **using a disposable seeded test database**. For browser tests only, install Playwright with `npm install --no-save --package-lock=false playwright@1.51.1` and `npx playwright install chromium` in ccp-web. Run the three scripts in order. They create and change demo records. Do not run them against personal data. Coverage can be collected with PCOV and PHPUnit `--coverage-clover coverage.xml`.

See `docs/FINAL_RESULTS.md` for measured evidence, `docs/QA_CHECKLIST.md` for completed checks and environment limitations, and `docs/USER_MANUAL_NOTES.md` for screen instructions. Earlier results in docs/history, STEP_1_10_RESULTS and SIMPLE_LOGIN_RESULTS describe earlier snapshots.

## Troubleshooting

- `Could not open input file: artisan`: change into ccp-api first. Frontend commands run in ccp-web, which is inside career-counselling-placement.
- MySQL shows port 3306 / database laravel: .env is missing or cached. Run `php.exe ../scripts/prepare-env.php`, edit the actual .env, then `php.exe artisan config:clear`; restart API.
- Missing APP_KEY: the preparation helper generates one only if absent, preserving existing nonempty keys.
- `could not find driver`: enable pdo_mysql in the loaded CLI php.ini, then restart the terminal.
- Access denied: use your actual MySQL password and port. An empty DB_PASSWORD is correct only if that MySQL account has no password.
- Git Bash `stdin/stdout is not a tty`: use `php.exe`, avoid PowerShell `Copy-Item`; use `cp` in Bash or the .bat scripts from cmd.
- npm ci fails: cd into ccp-web where package.json/package-lock.json exist. Use Node 22.12+.
- 500 error: read the latest `.ERROR:` line in storage/logs/laravel.log, verify DB credentials/migrations, then config:clear. No need to reset your database.
- Pending staff account: sign in, request approval from your active Admin, then **Check approval status**. This is independent of email verification.
- Notifications have no mail: start queue worker; log mail is not a Gmail sender. No reminders: start scheduler.

## Limits

No payments, in-app video calls, AI recommendations, native mobile app, SMS gateway, scraping or external company integrations. Tests ran on Linux with SQLite, MariaDB and Chromium; native XAMPP/Windows, Edge, Firefox, screen-reader hardware and real SMTP delivery are not certified. See the explicit QA matrix instead of assuming every browser/device was tested.
# Career-Counselling-Placement
