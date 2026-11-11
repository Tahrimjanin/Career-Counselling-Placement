# Career Counselling and Placement — Steps 1–10 checked

This release updates the supplied project and the previous Steps 5–10 ZIP. The canonical folders are now `career-counselling-placement/ccp-api` and `career-counselling-placement/ccp-web`. The old Windows folders `Job_placement/Web-api` and `Job_placement/frontend` have been renamed.

## What changed

- Original navy/teal vector logo on login, registration, password/email pages and every role’s sidebar; consistent Lucide outline icons.
- Four independent account roles: Student, Career Advisor, Admin and Recruiter. Login checks the selected role against the account. Changing the dropdown never changes account permissions.
- Any valid personal email is accepted; the university-domain restriction was removed as requested. Students self-register; an Admin creates Advisor/Admin/Recruiter accounts using **Users → Create an account**. This avoids public creation of privileged accounts. New staff accounts must change their temporary password and verify email.
- Step 5: eight-step profile and student verification, completion separate from readiness.
- Step 6: advisor directory, two preferences, Admin allocation with capacity locking, advisor availability/expertise, counselling requests/confirmation/rescheduling/cancellation/feedback, career plans and milestones, assigned student profiles.
- Step 7: structured CV builder with live preview and print-to-PDF, private PDF/DOCX upload (5 MB), version history, submission, assigned-advisor review and placement recommendation. Saving or copying produces a new version. Submitted/approved content is immutable.
- Step 8: verified-company recruiter posts → Admin approval/rejection → student search/filter/save/estimated skill match. Deadline eligibility uses Asia/Dhaka. `ccp:close-opportunities` closes expired posts hourly when the scheduler runs.
- Step 9: eligibility checklist, exact CV selection, Submitted to Admin, correction/resubmission, university rejection, approval/forwarding, withdrawal, history and audit events. Old mutation endpoints that bypassed these rules are retired.
- Step 10: own-company forwarded candidates and candidate profiles/CVs, interview scheduling/rescheduling/cancellation/feedback, offers, accept/decline and transactional placement records.

## Update your existing installation (VS Code Git Bash)

1. Stop both development servers with Ctrl+C. Keep a backup of your current folder and database.
2. Extract this ZIP to a new folder. Copy your existing `Job_placement/Web-api/.env` and `Job_placement/Web-api/storage/app/private` into the new backend. Keep the existing APP_KEY and database connection. Do not copy old `bootstrap/cache` files.
3. Open the new `career-counselling-placement` folder in VS Code, then run:

```bash
cd ccp-api
composer install
php.exe artisan config:clear
php.exe artisan route:clear
php.exe artisan migrate
php.exe artisan serve
```

In a second VS Code terminal, starting from `career-counselling-placement`:

```bash
cd ccp-web
npm ci
cp -n .env.example .env
npm run dev
```

Open **http://localhost:5173** in a normal browser. Backend uses **http://localhost:8000**. Use `localhost` consistently. Existing accounts and data stay in the database; `migrate` applies outstanding additive migrations. Do **not** run `migrate:fresh` on your current database.

## Fresh local installation

Requirements: PHP 8.2+ with pdo_mysql, mbstring, openssl, fileinfo, zip, DOM/XML; Composer; Node 22.12+; XAMPP MySQL/MariaDB.

Start MySQL in XAMPP. Create `career_counselling_placement` using phpMyAdmin with `utf8mb4_unicode_ci`. The supplied `.env.example` uses port **3307**, matching this project’s Windows setup; change it if your server uses another port.

From `career-counselling-placement/ccp-api`:

```bash
composer install
cp .env.example .env
php.exe artisan key:generate
php.exe artisan migrate --seed
php.exe artisan serve
```

Then run the frontend commands above in a separate terminal. Private CVs do not require a public storage symlink. Set PHP `upload_max_filesize=8M` and `post_max_size=10M` to allow a 5 MB CV.

## Four demo accounts (local development only)

| Workspace | Email | Password |
|---|---|---|
| Student | student@demo.test | DemoPass123! |
| Career Advisor | advisor@demo.test | DemoPass123! |
| Admin | admin@demo.test | DemoPass123! |
| Recruiter | recruiter@demo.test | DemoPass123! |

`student@demo.test` is pending university approval so the verification workflow can be exercised. `stage1@demo.test` has a completed, verified profile, allocated advisor and an approved CV. Stage 1–11 accounts illustrate workflow statuses. All sample identities are fictional.

Use Admin → Users to create real local test accounts with your chosen email and temporary password. A recruiter must belong to a company. Use the seeded company locally; extended company/user administration remains a later phase.

## Email verification without the old terminal error

`MAIL_MAILER=log` writes mail locally and does not deliver to an inbox. From `ccp-api`, run this with the registered email:

```bash
php.exe artisan ccp:verification-link "your-email@example.com"
```

Copy the **entire URL** into the same browser. Sign in to that account if necessary, then click **Verify email**. The helper only prints a signed 60-minute link; it does not mark the account verified or approve the student. It is disabled outside local mode/log mailer. Use `php.exe` in Git Bash to avoid a `winpty` alias causing `stdout is not a tty`.

For real delivery configure Laravel MAIL_MAILER=smtp plus MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS and the provider’s transport settings, then clear configuration. Password reset links are in `storage/logs/laravel.log` in local mode.

## Background deadline closure

In another backend terminal:

```bash
php.exe artisan schedule:work
```

Applications always re-check the deadline even if the scheduler is not running. Full notification-centre/reminder/email scheduling is Step 11 and is not claimed in this release.

## Tests

Backend (SQLite in memory by default; does not reset your normal database):

```bash
php.exe artisan test
php.exe vendor/bin/pint --test
```

Frontend:

```bash
npm test
npm run lint
npm run build
```

Browser tests: install Playwright for your test environment, start both servers against a **dedicated seeded test database**, then run `node tests/steps3-5.smoke.cjs` and `node tests/steps5-10.smoke.cjs`. The second script invokes `php artisan ccp:verification-link`, so PHP must be on PATH. These scripts create and mutate fictional fixtures; do not use them against your real database.

See `docs/STEP_1_10_RESULTS.md` for exact evidence and testing limits.

## Scope and limits

This release audits and fixes work-order Steps 1–10 (Section 15 of the supplied master prompt), including logo/icons/authentication fixes. Steps 11–15 are not declared complete: full notification centre and reminder delivery, reports/CSV/settings/expanded administration, landing page, broad accessibility/cross-browser QA and final production packaging remain separate work. Existing dashboard activity alerts and the opportunity-closure command support the modules shipped here.

CV PDF export uses the browser’s **Print → Save as PDF** dialog; there is no server PDF renderer. Date/time inputs use the device’s timezone; displayed appointment times are Asia/Dhaka. Set the device to Bangladesh time when entering local appointments.

Tests ran on Linux with SQLite, MariaDB and Chromium. Native Windows/XAMPP, Edge, Firefox, real SMTP, screen readers and service/policy coverage percentage were not measured. Passing these tests is evidence of the covered flows, not a guarantee that every possible bug is absent.

## Windows scripts and database import

Start Apache and MySQL in XAMPP, and create the database first. `ccp-api/database/sql/create_database.sql` can be imported in phpMyAdmin. Match `.env` port/password to your MySQL installation (3307 is the existing project default; stock XAMPP commonly uses 3306). A password error is a database credential problem; do not reset MySQL users as part of installation.

From `career-counselling-placement`, Command Prompt/PowerShell users can run `scripts\setup.bat` for a **fresh demo installation**. For an existing database, copy your old environment/private files first and run `scripts\update.bat`. Git Bash users can use the explicit commands above. `scripts\reset-db.bat` is deliberately destructive and requires typing RESET; never use it to upgrade. The obsolete create-project/overlay bootstrap has been replaced.

`ccp-api/database/sql/career_counselling_placement.sql` contains schema + fictional demo rows and migration history, generated from MariaDB after all migrations. Import it into a **new empty database** as an alternative to `migrate --seed`, not into an existing installation. After import, run `migrate` to check for later changes. The dump contains demo password hashes, no personal configuration, real data, session tokens or uploaded files.

## XAMPP troubleshooting and Apache alternative

- `Could not open input file: artisan`: enter `career-counselling-placement/ccp-api` first. `package.json` for the SPA is in `ccp-web`.
- Git Bash uses `cp .env.example .env`; `Copy-Item` is PowerShell only. Never overwrite an existing `.env` during an update.
- `No APP_KEY variable`: fresh setup includes `APP_KEY=`. `scripts/prepare-env.php` adds a missing variable and generates a key only when empty. Preserve every existing nonempty key.
- `could not find driver`: run `php.exe --ini`, edit the **Loaded Configuration File**, enable `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, DOM/XML, and verify `php.exe -m`. Restart terminals/Apache after configuration changes.
- Set upload_max_filesize=8M and post_max_size=10M. Never put CV files in public storage. `storage:link` is optional for future public assets and does not expose private CVs.
- If ports 80 or 3306 are occupied, change XAMPP's ports and the matching URL/DB_PORT. The recommended app servers use 8000 and 5173.
- Cookie/CSRF problems: use `localhost` for both servers, not a mix of localhost and 127.0.0.1. `.env.example` uses a host-only cookie (`SESSION_DOMAIN=null`). After edits: `php.exe artisan config:clear`, restart both servers and clear old site cookies.
- Production deployments need HTTPS, APP_DEBUG=false, secure cookies, real SMTP and deployment review; this is a local-development handoff.

Optional Apache virtual host (replace the directory with your extracted path):

```apache
<VirtualHost *:80>
  ServerName ccp.test
  DocumentRoot "D:/CCP/career-counselling-placement/ccp-api/public"
  <Directory "D:/CCP/career-counselling-placement/ccp-api/public">
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
```

Enable `mod_rewrite`, include `conf/extra/httpd-vhosts.conf`, and add `127.0.0.1 ccp.test` to the Windows hosts file. Keep frontend on the same site by starting Vite with `npm run dev -- --host ccp.test`; set APP_URL=http://ccp.test, FRONTEND_URL=http://ccp.test:5173, SANCTUM_STATEFUL_DOMAINS=ccp.test:5173, SESSION_DOMAIN=null and frontend VITE_API_URL=http://ccp.test. Clear configuration and restart. This alternative is documented; native Apache/Windows execution was not available for testing.
