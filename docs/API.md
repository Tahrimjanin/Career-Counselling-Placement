# API reference

Use history/STEP_3_5_API.md for auth/profile payloads and history/STEP_5_10_API.md for workflow payloads. Current additions: GET /api/v1/applications supports review=pending|corrections|forwarded (Admin), offers=1 (offer history), status, q, page and per_page <=50. Workflow pagination parameters reject invalid values with 422. GET /api/v1/allocations paginates students and returns both preference rows plus meta. Numeric resource IDs reject nonnumeric path segments with 404. All workflow endpoints require an active approved account and completed temporary-password change; email verification is checked only when enabled.

## Current simple-login revision

Login/register responses now include token/token_type/expires_at alongside data. Send Authorization: Bearer on protected requests. Cookie/CSRF bootstrap is disabled. Email verification is optional and off by default.


## Final platform endpoints

Auth: `POST /api/register` now accepts role student/advisor/admin/recruiter. Student requires department, recruiter requires company_name. Staff returns status pending; provided status/privilege flags are ignored. Login returns a token even for pending users, but protected workspaces return 403 until approval. `GET /api/me` reports status and has_avatar/avatar_version.

| Method | Path | Access / body |
|---|---|---|
| GET | /api/public/overview | Public; counts, advisors, opportunities, contact/policy settings |
| GET/POST/DELETE | /api/v1/avatar | Own account; POST multipart image |
| GET | /api/v1/notifications | Own notices, filter all/unread/deadlines/interviews, page/per_page |
| POST | /api/v1/notifications/{uuid}/read | Own notice only |
| POST | /api/v1/notifications/read-all | Own unread notices |
| GET/POST | /api/v1/reminders | Own list; student POST application_id, interval_days |
| DELETE | /api/v1/reminders/{id} | Own reminder |
| GET | /api/v1/admin/users | Admin; q, role, status, page |
| PATCH | /api/v1/admin/users/{user} | Admin; status and/or temporary password |
| POST | /api/v1/admin/companies | Admin; name, optional industry/website/description |
| GET/PUT | /api/v1/admin/settings | Admin; validated settings |
| GET | /api/v1/admin/audit | Admin; q action filter, page |
| GET | /api/v1/admin/reports | Admin; optional from/to YYYY-MM-DD |
| GET | /api/v1/admin/reports.csv | Admin; same filter, CSV download |

Protected platform endpoints require active approved accounts, optional email-verification gate and completed forced password change. Avatar access is own-account authenticated. Errors use standard JSON 401/403/404/422/429. Pagination is under data with data/current_page/last_page; unread count is top-level on notification lists. The Postman collection uses bearerToken and apiBase variables; fill token from your own login response.
