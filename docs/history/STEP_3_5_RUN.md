# Run and inspect Steps 3–5

Original folder names are intentional: Step 2 was excluded. This file supersedes conflicting legacy setup instructions for this incremental delivery. It is not the final Step 15 installation guide.

## Backend
From `career-counselling-placement/ccp-api`:
```bat
composer install
copy .env.example .env
php artisan key:generate
```
Set `APP_URL=http://localhost:8000`, `FRONTEND_URL=http://localhost:5173`, `SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173`, `SESSION_DRIVER=database`, `MAIL_MAILER=log`. Keep both browser URLs on **localhost**. The existing SQLite default is unchanged because Step 2 was excluded. For SQLite create `database/database.sqlite`; for an existing MySQL/MariaDB installation set DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD for an empty local test database. Use InnoDB and utf8mb4_unicode_ci. Then:
```bat
php artisan migrate --seed
php artisan serve
```
Do not seed production data. On a **disposable demo database only**, use `php artisan migrate:fresh --seed` to reset.

## Frontend
From `career-counselling-placement/frontend`:
```bat
npm ci --legacy-peer-deps
copy .env.example .env
npm run dev
```
Open `http://localhost:5173`. `VITE_API_URL` is the backend origin, `http://localhost:8000` (old `/api` suffix is also normalized). Node 20.19+ or 22.12+ is required by the supplied Vite 8 dependency. The peer-dependency workaround and Vite warnings belong to the pre-existing Step 2 blocker; no dependency upgrade was silently made.

## Demo accounts
All are fictional; password **DemoPass123!**.

| Email | What to inspect |
|---|---|
| student@demo.test | Profile wizard, pending university verification |
| admin@demo.test | Student verification queue, approve/reject |
| advisor@demo.test | Own contact profile |
| recruiter@demo.test | Own contact + linked company profile |
| unverified@demo.test | Verify-email gate, resend |
| temporary@demo.test | Advisor temporary-password change gate |
| rejected@demo.test | Rejected profile with reason |
| stage1@demo.test through stage11@demo.test | Schema fixtures for future workflow stages; not full implemented workflows |

Registration requires a domain in `UNIVERSITY_EMAIL_DOMAINS`. Local email messages are written to `storage/logs/laravel.log`. Find the latest verification/reset link, remove quoted-printable line wrapping if your log viewer shows it, and open it in the **same browser**. For verification, sign in as that account, reopen the link, then press Verify email. Verification alone leaves university status pending. Admin → Student verification → Review student → Approve student or enter a reason and Reject student.

## Tests
```bat
php artisan test
vendor\bin\pint --test --dirty
php artisan route:list
php artisan config:cache
php artisan route:cache
php artisan optimize:clear
```
PHPUnit defaults to in-memory SQLite. To test MySQL, export DB_CONNECTION=mysql and DB_DATABASE to a **separate disposable test database**, plus credentials, before running PHPUnit. Never point tests at a live database. It creates/drops tables.

Frontend: `npm run build`. Browser smoke: install Playwright in your testing environment, install Chromium, start both servers against a fresh demo database, then `node tests/steps3-5.smoke.cjs`. The smoke test edits demo student data and approves that student, so reset the demo database before a repeat. It checks all eight steps, persistence after reload, one-shot 419 retry, admin verification and 320/768/1440 widths.

## Upgrade existing data
Back up the original database and private CV storage. Point the upgraded backend at a copy and run **php artisan migrate**, without seed or fresh. New migrations preserve legacy columns and copy profiles, skills, assignments, CV paths, statuses and advisor notes. Source emails are not automatically verified. Correct any unknown role or duplicate CV version reported by preflight, then retry. Check counts and role access before using the upgraded copy. An SQLite database is not automatically converted into MySQL by these migrations; migrating between engines requires a separately validated export/import.

`tests/fixtures/legacy_seed.php` and `legacy_assert.php` are executable fixtures for an isolated database: apply only pre-2026_10_29 migrations, seed the fixture, run the new migrations, then assert preservation. Never run the fixture on a real database.

## Deliberately pending
Step 2 rename/full XAMPP setup/dependency correction; Steps 6–15 feature workflows, global visual polish, full policy conversion, final SQL dump, Windows setup/reset scripts and whole-platform QA. Existing future-module screens remain prototype screens. This archive is an incremental Step 3–5 delivery, not production completion.
