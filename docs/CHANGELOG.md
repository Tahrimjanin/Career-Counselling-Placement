# Changelog

## Steps 1–10 audited release

Canonical folder rename, safer environment setup, explicit workflow Gates/FormRequests/Resources, private-note fix, session state fix, allocation pagination, review filters, dedicated Offers history, Admin read-only views and regression tests. Includes MariaDB-generated demo SQL and non-destructive update instructions.

## Current simple-login revision

Simple login revision: no cookie/CSRF dependency, optional email verification off by default, expiring/revocable bearer tokens and updated setup/tests.


## Final release — 2026-10-29

Added four-role self-registration with safe staff approval; private profile images in header; notification center, queued mail, reminders and scheduler; admin reports/CSV/user management/company creation/audit/settings; public landing/legal pages; accessible mobile navigation and status colors. Added additive migration, regression/browser tests, UML/API/manual documents and combined startup script. Kept bearer-token auth and deferred email verification per user instruction. Existing database upgrade does not reset or reseed records.
