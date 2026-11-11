# Current decisions — Steps 1–10

1. Scope means the numbered work order in Section 15 of the supplied master prompt. Steps 11–15 are not declared finished merely because their support tables exist. Testing and delivery documentation needed for this handoff are included.
2. Personal email registration is explicitly requested by the user and supersedes the original university-domain restriction. Only students self-register. Admin creates staff accounts; staff change temporary passwords and verify email. The workspace selector checks the account role and never grants a role.
3. Folder/package names now follow Step 2: career-counselling-placement, ccp-api, ccp-web; database name uses career_counselling_placement. Old paths appear only in upgrade instructions. Existing database, APP_KEY and private uploads must be retained.
4. Existing database writes are additive. Legacy user_id/status/admin_review columns remain for safe migration; ApplicationWorkflow is the sole transition service and synchronizes canonical workflow_status. Obsolete mutation controllers/routes are removed. Unknown legacy roles/duplicate CV versions stop conversion instead of silently deleting records.
5. Generated active_student_id unique constraint enforces one active advisor; row locks serialize capacity checks. Allocation pages paginate students, keeping their two preferences together.
6. Counselling requests must be confirmed before completion; future/overlap/state checks run transactionally. Session activity is stored in alerts/audit logs. Full queued notifications and reminders are Step 11.
7. Private advisor notes belong to their author; a replacement advisor can read shared student notes but cannot read the previous advisor's private notes. Active assignments are authoritative, not legacy advisor_id pointers.
8. CV contents are immutable versions; submitting, requesting changes, approving and superseding change lifecycle status, not content. New edits make new versions. Exact application CVs remain identifiable even when superseded. Uploads stay private; downloads require object authorization. PDF export uses browser print.
9. Profile completion is separate from career readiness. Identity/contact/academic changes invalidate student verification; unchanged saves do not. Zero numeric values are valid. Personal account email/role/company_id are not writable through profile forms.
10. Opportunity DB status `pending` represents Pending approval; CV source `builder` represents built content. Compatibility names are intentional. UTC timestamps are stored; appointment display/deadline rules use Asia/Dhaka. Input datetime-local uses device timezone, labelled in the UI.
11. Auth compatibility endpoints remain /api; new workflows are /api/v1. Named Gates, object policies, FormRequest validation and allowlisted profile/user/CV/opportunity/application resources enforce the current boundary. Existing small staff-directory and notes response shapes are retained for compatibility.
12. Offers have their own student/recruiter page, including answered history. Existing offers without start_date require recruiter correction before acceptance; no date is invented. Admin sees plans/interviews read-only, advisors can browse opportunities.
13. Existing vector branding is reused consistently; icons use Lucide outline glyphs. This task does not introduce raster or emoji icons.
14. Demo seeders are local/testing only and use fictional identities. Seeding is not an upgrade operation. SQL dump is an alternative fresh install, never an overwrite of existing data.
15. Tests were run on Linux/PHP 8.3/SQLite/MariaDB/Chromium, not native Windows, Apache, Edge, Firefox or SMTP. No measured 80% code-coverage or universal bug-free claim is made. Historical reports are under docs/history and describe prior snapshots only.

## Current simple-login revision

Token authentication and optional email verification now supersede the cookie/mandatory-email requirements by explicit user request. Default email verification is off; student approval remains separate. See SIMPLE_LOGIN_RESULTS.md.


