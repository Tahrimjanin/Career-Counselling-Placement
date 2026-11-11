# Current decisions

## Final release: Steps 11–15

Public registration accepts all four roles. Student registration activates the account; staff registration creates a pending account and associated advisor/company record, with explicit administrator approval. This fulfills self-service account creation without allowing public privilege escalation. Existing accounts remain unchanged. An active administrator is the bootstrap authority. Recruiter account approval and company verification are separate.

User requested cookie/email verification deferral supersedes the master's original cookie/mandatory-verification requirement. Profile images are private authenticated resources, never exposed with token-bearing URLs. No external image host or invented person photograph is used; initials are the fallback.

In-app notifications are immediate and mail copies are queued after commit. Local mail remains log-only. Reminders use unique event keys per recipient and timestamp; they do not backfill missed time windows. Report dates select an application cohort, not placement event dates. Placement rate counts unique placed students over unique applicants; advisor workload is current irrespective of dates. CSV formula-like values receive an apostrophe prefix.

Settings are editable contact/policy text and new-advisor capacity; changing capacity does not silently change existing allocations. Admins cannot disable themselves. Password resets revoke tokens and require changing the temporary password. Audit logs exclude password values. Additive migrations preserve existing accounts, applications and private files.
