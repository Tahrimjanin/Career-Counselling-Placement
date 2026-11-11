# QA matrix — final release

## Executed

- [x] SQLite and MariaDB backend suite; line coverage target on Services/Policies.
- [x] Correct/mismatched-role login, all-role signup, pending staff restriction/approval, suspension, reset and temporary-password flow.
- [x] Profile verification, completeness/readiness, private record/notes/CV IDOR restrictions.
- [x] Capacity/uniqueness race, sessions overlap/state validation, CV spoof/size/version checks.
- [x] Application transitions/eligibility/duplicate prevention; interviews/offers/placement transactions.
- [x] Notifications scope/filter/read/deduplication, reminder timing/reschedule/cancellation/follow-up final states.
- [x] Report cohort/CSV correctness and formula escaping; settings/audit permissions.
- [x] Private image validation, own-account fetch/removal, header reload persistence.
- [x] Frontend form/guard/loading/disabled state component tests, lint/build.
- [x] Chromium role journey screens at representative 320/375/768/1024/1440 widths; no page overflow in tested flows.
- [x] Mobile Menu keyboard focus/Escape; visible labels/focus and measured text contrast.
- [x] Repeated migrate/seed, rollback/reapply, SQL import, queue worker/reminder command, dependency audit.

## Screen / role coverage

| Area | Roles | Evidence |
|---|---|---|
| Login/signup/profile | All four | Component, API, Chromium; upload/reload |
| Advisor allocation/counselling/plan/CV | Student, advisor, admin | Policy/service tests and workflow smoke |
| Opportunities/applications | Student, admin, recruiter | Eligibility/state-machine tests and full journey |
| Interviews/offers/placements | Student, recruiter, admin | Overlap/transaction tests and full journey |
| Notifications/reminders | All roles / student follow-up | Scoped API tests, scheduler tests, Chromium read/filter |
| Users/company/reports/settings/audit | Admin | API authorization/data tests, Chromium approval/export/settings |
| Public landing/privacy/terms | Public | Browser landing/contact update, responsive checks |

## Not certified in this environment

- [ ] Native Windows/XAMPP/Apache/phpMyAdmin execution.
- [ ] Edge and Firefox; every screen × loading/empty/error/success/disabled × every width.
- [ ] Native screen-reader smoke and exhaustive keyboard-only navigation.
- [ ] Full slow-network/offline and expired-session-mid-form manual matrix.
- [ ] Real SMTP delivery (log mail is the configured development behavior).

See FINAL_RESULTS.md. Unticked items are explicit validation limits; they are not represented as passed.
