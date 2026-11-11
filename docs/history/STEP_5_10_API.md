# Workflow API

Prefix `/api/v1`; all require session authentication, active account, verified email and completed temporary-password change. Mutations require the CSRF cookie/header. Errors use `message` and optional field `errors` (401/403/404/422/429). Collections use `data` (paginated endpoints include their paginator under `data`); opportunities return `data` + `meta`.

| Methods and path | Roles / behavior |
|---|---|
| GET advisors; POST advisor-preferences | Student directory, ranked request; Admin can read directory |
| GET allocations; POST/DELETE allocations/{student} | Admin confirm/reject, transaction and capacity guard |
| GET/PUT advisor-profile | Advisor’s own availability, title, biography and expertise |
| GET students/{student} | Student’s own, assigned Advisor, Admin |
| GET/POST sessions; PATCH sessions/{id} | Student requests; assigned Advisor confirms/reschedules/declines/completes; either participant cancels before start; Admin reads |
| GET/PUT plans/{student}; PATCH milestones/{id} | Own Student reads and starts tasks; assigned Advisor authors/confirms; Admin reads |
| GET/POST cvs; GET cvs/{cv} | Student’s versions; assigned Advisor review list; authorized details |
| POST cvs/{cv}/submit; POST cvs/{cv}/review | Owner draft submission; assigned Advisor review |
| GET cvs/{cv}/download | Owner, assigned Advisor, Admin, own-company Recruiter only after forwarding |
| GET/POST opportunities | Role-filtered directory; verified-company Recruiter creation |
| POST opportunities/{opportunity}/review | Admin approved/rejected (rejection reason required) |
| PUT opportunities/{opportunity}/save | Student sets `saved` boolean (idempotent) |
| GET opportunities/{opportunity}/eligibility?cv_id= | Own Student checklist |
| GET/POST applications; GET/PATCH applications/{application} | Role-filtered list/detail, Student submit, state-machine transitions |
| GET interviews; POST applications/{application}/interview | Student/own-company Recruiter/Admin lists; Recruiter schedules/reschedules |
| PATCH interviews/{id} | Recruiter cancel or post-interview feedback |
| POST applications/{application}/offer | Recruiter creates/updates pending offer with details, expires_at, start_date |
| POST offers/{id}/respond | Own Student accepted/declined; accepted creates placement exactly once |
| GET placements | Admin placement list |

Existing `/api/login`, register, me, logout, forgot-password, reset-password, change-password and verification endpoints remain. Legacy CV/application/interview mutation endpoints are retired; new UI uses v1. Existing profile and student-verification v1 endpoints remain.

Appointments accept ISO8601 with timezone; storage uses UTC. Deadlines are Dhaka dates. CV content is structured text, private upload maximum 5120 KiB. Approved application CV IDs cannot be changed. Corrections update cover note/profile; a superseded but previously approved attached CV remains identifiable.
