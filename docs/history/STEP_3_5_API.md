# API added/changed in Steps 3–5

Always send `Accept: application/json`, credentials/cookies, and `Origin: http://localhost:5173`. First GET `/sanctum/csrf-cookie`, then send the XSRF header for writes. Axios is configured to do this. No bearer token is accepted. A 419 refreshes CSRF and retries once; 401 clears the frontend user and returns to login.

| Method/path | Access | Input / output |
|---|---|---|
| POST /api/register | Public, 3/minute/IP | name, university email, password + confirmation, department; 201 `{data: user}` |
| POST /api/login | Public, 5/minute/email+IP | email, password, role; `{data: user}` |
| GET /api/me | Signed in, active | Safe user resource |
| POST /api/logout | Signed in, active | 204, invalidate session |
| POST /api/forgot-password | Public, 3/minute/IP | email; non-enumerating message |
| POST /api/reset-password | Public, 3/minute/IP | email, token, password + confirmation |
| POST /api/change-password | Signed in, active | current_password, password + confirmation |
| POST /api/email/verification-notification | Signed in, active; 3/minute/user | Resend email |
| GET /api/email/verify/{id}/{hash} | Signed in, signed URL, own ID | Signed URL from email; expires after 60 minutes |
| POST /api/users | Verified Admin | Create account with temporary password; forced change + email verification |
| GET /api/v1/profile | Email verified, active, password changed | Own safe profile; student completion/missing/readiness data |
| PATCH /api/v1/profile/steps/{1..8} | Same; own account | Step fields below, optional boolean advance; saves incomplete progress |
| GET /api/v1/student-verifications | Admin | Optional status and page; paginated `{data,links,meta}` |
| GET /api/v1/student-verifications/{user} | Admin, student target | Student profile detail |
| PATCH /api/v1/student-verifications/{user} | Admin, email-verified student target | status=`verified|rejected`, reason required for rejection; audit row |

Step 1: name, phone; recruiter may send company{name,industry,website,description}. Step 2: student_no, department, batch, cgpa (0–4), ssc_/hsc_ institution, board, year, gpa (0–5). Step 3/4: skills array (technical/soft). Step 5/6: has_experience/has_certificates and entries array of title, org, start_date, end_date, description. Step 7: career_objective, interests array. Step 8: review/save wizard position.

Responses: 401 signed out, 403 wrong role/ownership/unverified email/forced password change/suspended, 404 missing resource/step, 419 CSRF/session, 422 validation or verification before email confirmation, 429 throttled. Form validation is `{message,errors:{field:[messages]}}`. Profile role/email/owner/verification cannot be changed by clients.

Pending or rejected students can edit profiles and browse opportunities, but POST /api/applications is blocked. Full application eligibility/state-machine work remains Step 9.
