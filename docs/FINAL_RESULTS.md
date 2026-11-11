# Final release verification — 2026-10-29

## Scope and files

Work-order Steps 11–15 implemented: notifications/reminders/queue/scheduler; admin reports/CSV/audit/settings/users/companies; public landing/legal pages/mobile accessibility polish; regression tests; SQL/scripts/docs/UML/manual notes. Also added requested four-role self-registration and authenticated profile images beside header names. Earlier Steps 1–10 remain and their journeys were rerun. User-directed bearer login and deferred email verification remain intentional deviations from the original master prompt.

Key additions: PlatformNotice/PlatformMail, WorkflowActivity, ReminderScheduler, PlatformReports/PlatformSettings; Admin/Avatar/Notification/Public controllers; ApprovedAccount middleware; routes/platform.php; complete_platform migration; Avatar, Notifications, AdminTools, Landing and updated Layout/Login/Users/Companies; PlatformTest and platform/component/browser tests; scripts/start.bat and documentation.

## Automated checks

| Check | Result |
|---|---|
| PHPUnit SQLite | 82 tests, 817 assertions passed |
| PHPUnit MariaDB | Same suite, separate disposable database; final log retained |
| Vitest | 18 tests passed |
| ESLint | Passed |
| Vite production build | Passed; no Vite build warnings |
| Pint --test | Passed |
| Config/route cache and route:list | Passed; 81 route entries, no duplicate method/URI pairs |
| Composer audit | No advisories or abandoned packages reported |
| npm audit | Zero vulnerabilities reported |
| Repeated fresh migrate/seed | Passed twice on disposable MariaDB database |
| Additional seed, rollback and migrate | Passed |
| Concurrent advisor capacity race | Passed: one assignment at capacity one |
| SQL dump import | Passed into a second empty MariaDB database |
| Chromium journeys | Profile wizard, CV review, approval/forwarding, interview/offer/placement; new signup/approval/photo/notifications/reports/settings/audit |
| Queue worker and reminders | Executed, no failed mail jobs; MAIL_MAILER=log |

The npm launcher emits an environment-only unknown http-proxy configuration warning in this hosted environment; Vite itself emits no build warning. No production source console.log, dd or dump statements found. Test scripts intentionally print PASS evidence.

## Coverage

PCOV 1.0.11, PHPUnit Clover executable-line coverage over the application source:

- Services: 114/117 executable lines (97.44%)
- Policies: 9/9 executable lines (100.00%)

These are line-coverage measurements, not a claim of exhaustive security or branch coverage. Reminder tests cover repeated delivery, 24h/1h thresholds, rescheduling, cancellation, deadline days and follow-up completion; notification tests cover scope/read/filter/deduplication; reports test cohort counts and CSV formula escaping.

## Browser and interface checks

Chromium automated flows assert no uncaught JavaScript/console errors and no HTTP 5xx. Existing workflow scripts cover 22 role screens; the final script covers public landing, all four public signup paths, staff approvals and subsequent login, upload/reload persistence in the header, unread notifications, CSV download and settings reflected on the public page. Responsive checks use 320/375/768/1024/1440px; keyboard checks use Menu/Escape and navigation. Screenshots are inspected as temporary QA evidence, not shipped as user data.

WCAG contrast calculation (normal text target >=4.5:1):

| Pair | Foreground / background | Ratio |
|---|---|---|
| Primary button | #FFFFFF / #08766F | 5.48:1 |
| Navy body text | #10254F / #FFFFFF | 15.00:1 |
| Muted text | #556B83 / #FFFFFF | 5.50:1 |
| Sidebar navigation | #BFCCDB / #10254F | 9.20:1 |
| Positive status | #17613B / #DFF4E8 | 6.49:1 |
| Attention status | #704900 / #FFF0CD | 7.04:1 |
| Negative status | #912020 / #FCE3E3 | 7.09:1 |

## Acceptance checklist with actual evidence

- [x] Four-role signup/login and role-specific journeys, with safe pending staff approval.
- [x] Application says Submitted to Admin before forwarding.
- [x] Completion/readiness remain separate; preferences never auto-assign; application pins approved CV.
- [x] Additive migrations, repeatable seed and importable fictional SQL.
- [x] Backend/frontend tests, lint/build, browser journeys and coverage target pass in the available environment.
- [x] Photo displayed beside the name for all role workspaces after upload; authenticated storage/access.
- [x] Source contains no old branding or debug output.
- [x] Distribution excludes .env, vendor, node_modules, runtime logs, tokens and private uploads.
- [x] SQL, Windows scripts, API/Postman, UML, data dictionary and manual notes included.
- [ ] Clean native Windows/XAMPP/Apache/phpMyAdmin execution: scripts reviewed, not executed on Windows.
- [ ] Exhaustive every-screen/every-state × Chrome/Edge/Firefox manual matrix and real screen-reader testing: not performed.
- [ ] Real SMTP/Gmail delivery: intentionally not configured; log-mail queue verified.

These last environment checks cannot honestly be marked passed from Linux/Chromium. They do not represent unfinished implemented modules. Cookie auth and mandatory email verification remain deferred by the user's explicit request. No Canva manual design, real employer integration or production certification is claimed.
