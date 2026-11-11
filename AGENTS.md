
## Mission
Maintain and improve this Career Counselling and Placement application.
Implement requested changes completely: inspect, edit, test, fix and report.
Prefer working code over lengthy plans. Never claim success without evidence.

## Project
- Backend: ccp-api — Laravel, PHP, MySQL/MariaDB, Sanctum bearer tokens.
- Frontend: ccp-web — React, Vite, Lucide icons.
- Development: Windows, VS Code, Git Bash, XAMPP.
- API: http://localhost:8000
- Frontend: http://localhost:5173
- Roles: student, advisor, admin, recruiter.

Verify the actual directory structure before running commands.
Read existing README, relevant docs and nested AGENTS.md files.
Treat current code and the user's latest request as authoritative.

## Working Style
- Respond briefly in Bangla; use English for code and identifiers.
- Start with a short action update, then do the work.
- Avoid repeated permission questions for ordinary reversible edits.
- Ask only when necessary information cannot be found or a decision is
  destructive, irreversible or materially changes the requested behavior.
- Inspect existing implementation before adding new files or dependencies.
- Use rg for searches and batch independent reads.
- Make focused changes; avoid unrelated rewrites.
- Reuse existing components, services, policies and styling.
- Investigate the root cause instead of hiding errors.
- Finish the requested task before suggesting additional features.
- Do not send messages, publish or deploy without authorization.

## Preserve Existing Behavior
Unless the user explicitly requests a change:
- Keep bearer-token authentication and sessionStorage.
- Keep cookie/CSRF login disabled.
- Keep email verification optional and disabled by default.
- Keep all four roles available for registration and login.
- Students register as active.
- Advisor, Recruiter and Admin self-signups require existing Admin approval.
- Never silently grant public registrations active administrator access.
- Keep recruiter account approval separate from company verification.
- Keep role checks and record ownership enforced on the backend.
- Preserve private CV files, profile photos and existing account data.
- Preserve the navy/teal design, shared logo and Lucide sidebar icons.
- Show the user's profile image beside their name in every workspace.
- Keep profile completion and career readiness as separate measures.

## Database and Secrets
- Read actual local configuration; never assume DB credentials or port.
- Never print, commit or package .env, APP_KEY, passwords or auth tokens.
- Preserve existing APP_KEY.
- Use additive migrations for upgrades.
- Never run migrate:fresh, db:wipe, reset-db scripts or demo SQL imports
  against the user's existing database.
- Never reseed an existing database merely to repair login.
- Run destructive database tests only against a verified disposable DB.
- Back up affected data before an authorized destructive operation.
- Keep demo identities separate from real records.

## Implementation Requirements
- Validate input and enforce authorization server-side.
- Prevent cross-user and cross-company access.
- Use transactions for related workflow writes.
- Preserve CV version references and application history.
- Show "Submitted to Admin" until the application is actually forwarded.
- Advisor preferences must not automatically allocate an advisor.
- Validate uploads by content/type, size and ownership.
- Store timestamps in UTC; display scheduling times in Asia/Dhaka.
- Preserve notification deduplication and queued mail behavior.
- Include loading, empty, error, success and disabled states where relevant.
- Prevent duplicate submissions.
- Keep forms labelled, keyboard accessible and responsive.
- Do not add fake statistics or pretend a button is functional.

## Commands
Run each command from the correct folder.

Backend, from ccp-api:
    composer install
    php.exe artisan config:clear
    php.exe artisan migrate
    php.exe artisan serve --host=localhost --port=8000

Frontend, from ccp-web:
    npm ci
    npm run dev -- --host localhost --port 5173

Background processes, from ccp-api:
    php.exe artisan queue:work --tries=3
    php.exe artisan schedule:work

Use php instead of php.exe on Linux.
In Git Bash use Bash commands; do not paste PowerShell-only syntax.
Install dependencies only when missing or changed.
Do not launch duplicate servers or workers.

## Testing
Select tests appropriate to the changed behavior.

Backend:
    php.exe artisan test
    php.exe vendor/bin/pint --test

Frontend:
    npm test
    npm run lint
    npm run build

For authentication, permissions or workflow changes:
- Test allowed and forbidden actions.
- Test wrong-role and cross-user access.
- Test validation errors and relevant edge cases.
- Test affected role journeys in the browser when available.
- Check browser errors and new backend log errors.
- Run migration checks on a disposable database when schema changes.

For UI changes:
- Check relevant desktop and mobile layouts.
- Check keyboard navigation, labels, overflow and image rendering.

Fix failures caused by the changes.
Do not weaken assertions or remove security checks just to pass tests.
Do not repeatedly rerun unchanged successful checks without a reason.
Clearly distinguish executed tests from checks unavailable in this environment.

## Completion and Delivery
Before finishing:
1. Confirm the requested behavior is implemented.
2. Review the diff for unintended changes and exposed secrets.
3. Run relevant tests and fix failures.
4. Update documentation if setup or behavior changed.
5. Report briefly:
   - What changed.
   - What was tested and the actual results.
   - Any remaining limitation.
   - Exact commands the user needs next, if any.

When a ZIP is requested:
- Include source, lockfiles, migrations, scripts and relevant docs.
- Exclude .env, vendor, node_modules, .git, caches, runtime logs,
  private uploads and real user data.
- Include a demo SQL dump only when requested or part of the agreed delivery.
- Validate the ZIP and provide the download link.
- Explain how to preserve the old .env and storage/app when upgrading.

Never describe the project as bug-free or fully tested on platforms
that were not actually tested.