> Historical snapshot. Current features and measured results: FINAL_RESULTS.md.

# Steps 1–10 audit and validation

Date: 29 October 2026. Scope: Section 15 work-order Steps 1–10; previous requested logo, icons and four-role login fixes included.

| Step | Result and evidence |
|---|---|
| 1 Audit | Reviewed current routes/controllers/services/policies/requests/resources, schema, auth/UI flows, setup and prior reports; concrete findings below and in AUDIT.md. Local work branch feature/ccp-upgrade. |
| 2 Rebrand/environment | Canonical folders and package names, MySQL default, corrected bootstrap, Windows setup/update scripts, SQL schema/demo import. Both servers ran together. |
| 3 Auth | Stateful cookies; verification/reset; role mismatch and all four logins; forced password change; throttles; account suspension; no localStorage bearer tokens; policy role matrix and IDOR tests. |
| 4 Database | Additive schema, repeatable seeders, ERD/data dictionary; MariaDB fresh+seed twice, repeat seed, rollback/remigrate; generated unique constraint; legacy conversion tests. |
| 5 Profile | Eight-step wizard, save/reload, separate completion/readiness, identity changes requeue verification, Admin approval. Browser and backend tests. |
| 6 Advising | Preferences are requests, allocation capacity/concurrency, private-note isolation, sessions/state/overlap/feedback, plan permissions/progress. Pagination keeps preferences paired. |
| 7 CV | Private PDF/DOCX validation, immutable versions, active assigned-advisor review, safe download, readiness recommendation. Browser submit/review and backend invalid-upload/access tests. |
| 8 Opportunities | Verified recruiter posts, Admin approve/reject, search/filter/save/skill estimate, deadline closure. Browser creation/approval and API isolation tests. |
| 9 Applications | Eight eligibility gates, state-machine transitions, corrections/forward/reject/withdraw, exact CV, history, admin queue filters and detail. API and browser flow. |
| 10 Recruiting | Company-scoped candidates, interview schedule/overlap/cancel/feedback, offer accept/decline, one placement record, answered-offer history. API/browser flow. |

## Executed checks

- PHPUnit: **62 tests, 681 assertions**, passing on SQLite and a dedicated MariaDB 10.11 test database.
- Concurrent allocation: two independent PHP workers compete for capacity 1; exactly one active assignment, other request rejected safely.
- Schema: fresh migrations+seed repeated, reseeding, rollback/remigrate, SQL dump imported into another empty database; all 12 migrations recorded.
- Frontend: **10 Vitest tests**, ESLint, production Vite build. Vite build has no warnings. npm reports zero vulnerabilities; Composer audit reports no security advisories.
- Browser: cookie auth, profile steps 1–8, reload, injected 419 recovery, Admin verification; four-role screens and CV → opportunity → submission → forwarding → interview → offer → accepted placement. Personal-email registration and signed verification after login; responsive widths 320, 375, 768, 1024, 1440.
- PHP formatting/syntax, route registration, config/route caching, dependency lockfile and archive hygiene checks recorded with the deliverable.

## Findings fixed in this audit

- Folder/package rename and old overlay bootstrap were incomplete.
- Old advisors' private notes could be visible to replacement advisors.
- Dead duplicate workflow controllers/UI pages remained; retired them.
- Workflow request validation and explicit Gate enforcement were inconsistent.
- Session completion accepted unconfirmed requests; missing action/invalid path types were not handled consistently.
- Application queues lacked useful review filters; Offers lacked a dedicated navigation entry; advisor opportunities and read-only Admin plan/interview links were missing.
- Allocation pagination counted preference rows and could split a student's pair; now paginates students.
- Career-plan student view unnecessarily requested the advisor-only directory. Admin plan view must not offer write buttons.
- Account creation now commits related profiles atomically; CV/plan decisions recheck current assignment under lock.
- Opportunity saved flags caused per-item queries; preloaded once. Soft-deleted CVs/opportunities are excluded by models.
- Session changes and CV submission now record supporting in-app activity. Full Step 11 notifications are still separate.

## Testing limits

Browser assertions detect uncaught JavaScript errors and HTTP 5xx. Expected unauthenticated /me responses (401) and the intentionally injected CSRF retry (419) appear as browser network-console messages; these are not concealed as a zero-console-error claim. Native Windows/XAMPP, Apache vhost, Edge/Firefox, real SMTP, screen readers, exhaustive UI state combinations and measured coverage percentage were not run. Windows scripts are supplied and reviewed, not claimed to have executed on Windows. Steps 11–15 and the master prompt's all-platform final acceptance are not declared complete.

No test used or reset the user's real database. Upgrade instructions use migrate, never migrate:fresh.
