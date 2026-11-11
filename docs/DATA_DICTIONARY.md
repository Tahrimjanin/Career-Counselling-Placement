# Data dictionary — generated from migrated schema

42 tables, including framework and retained compatibility tables. Types below are SQLite representations; migrations define MySQL unsigned types, decimal precision, foreign-key delete behavior, and the optional FULLTEXT index.

## advisor_assignments

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `student_id` | INTEGER | No | — |
| `advisor_id` | INTEGER | No | — |
| `assigned_by` | INTEGER | Yes | — |
| `status` | varchar | No | 'active' |
| `started_at` | datetime | No | — |
| `ended_at` | datetime | Yes | — |
| `active_student_id` (generated) | INTEGER | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `assigned_by` → `users.id`; delete: SET NULL.
- `advisor_id` → `users.id`; delete: CASCADE.
- `student_id` → `users.id`; delete: CASCADE.
- Index: `status`.
- Unique index: `active_student_id`.

## advisor_expertise

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `advisor_id` | INTEGER | No | — |
| `tag` | varchar | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `advisor_id` → `users.id`; delete: CASCADE.
- Unique index: `advisor_id, tag`.

## advisor_notes

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `advisor_id` | INTEGER | No | — |
| `student_id` | INTEGER | No | — |
| `body` | TEXT | No | — |
| `is_private` | tinyint(1) | No | '1' |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `student_id` → `users.id`; delete: CASCADE.
- `advisor_id` → `users.id`; delete: CASCADE.

## advisor_preferences

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `student_id` | INTEGER | No | — |
| `advisor_id` | INTEGER | No | — |
| `rank` | INTEGER | No | — |
| `status` | varchar | No | 'pending' |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `advisor_id` → `users.id`; delete: CASCADE.
- `student_id` → `users.id`; delete: CASCADE.
- Index: `status`.
- Unique index: `student_id, advisor_id`.
- Unique index: `student_id, rank`.

## advisor_profiles

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `title` | varchar | Yes | — |
| `bio` | TEXT | Yes | — |
| `capacity` | INTEGER | No | '20' |
| `is_accepting` | tinyint(1) | No | '1' |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `user_id` → `users.id`; delete: CASCADE.
- Unique index: `user_id`.

## alerts

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `title` | varchar | No | — |
| `body` | TEXT | Yes | — |
| `read` | tinyint(1) | No | '0' |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `user_id` → `users.id`; delete: CASCADE.

## application_status_history

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `application_id` | INTEGER | No | — |
| `from_status` | varchar | Yes | — |
| `to_status` | varchar | No | — |
| `actor_id` | INTEGER | Yes | — |
| `reason` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `actor_id` → `users.id`; delete: SET NULL.
- `application_id` → `applications.id`; delete: CASCADE.

## applications

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `opportunity_id` | INTEGER | No | — |
| `cv_id` | INTEGER | No | — |
| `status` | varchar | No | 'Applied' |
| `admin_review` | varchar | No | 'pending' |
| `forwarded_at` | datetime | Yes | — |
| `cover_note` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `student_id` | INTEGER | Yes | — |
| `eligibility_snapshot` | TEXT | Yes | — |
| `submitted_at` | datetime | Yes | — |
| `workflow_status` | varchar | Yes | — |
| `admin_decision` | varchar | Yes | — |
| `admin_reason` | TEXT | Yes | — |
| `admin_reviewed_by` | INTEGER | Yes | — |
| `admin_reviewed_at` | datetime | Yes | — |
| `forwarded_by` | INTEGER | Yes | — |
| `withdrawn_at` | datetime | Yes | — |

- `forwarded_by` → `users.id`; delete: SET NULL.
- `admin_reviewed_by` → `users.id`; delete: SET NULL.
- `student_id` → `users.id`; delete: CASCADE.
- `user_id` → `users.id`; delete: CASCADE.
- `opportunity_id` → `opportunities.id`; delete: CASCADE.
- `cv_id` → `cvs.id`; delete: NO ACTION.
- Index: `workflow_status`.
- Unique index: `student_id, opportunity_id`.
- Index: `status`.
- Unique index: `user_id, opportunity_id`.

## audit_logs

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `actor_id` | INTEGER | Yes | — |
| `action` | varchar | No | — |
| `entity_type` | varchar | No | — |
| `entity_id` | INTEGER | No | — |
| `old_values` | TEXT | Yes | — |
| `new_values` | TEXT | Yes | — |
| `ip` | varchar | Yes | — |
| `user_agent` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `actor_id` → `users.id`; delete: SET NULL.
- Index: `action`.
- Index: `entity_type, entity_id`.

## cache

| Column | Type | Nullable | Default |
|---|---|---|---|
| `key` (PK) | varchar | No | — |
| `value` | TEXT | No | — |
| `expiration` | INTEGER | No | — |

- Index: `expiration`.
- Unique index: `key`.

## cache_locks

| Column | Type | Nullable | Default |
|---|---|---|---|
| `key` (PK) | varchar | No | — |
| `owner` | varchar | No | — |
| `expiration` | INTEGER | No | — |

- Index: `expiration`.
- Unique index: `key`.

## career_plans

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `student_id` | INTEGER | No | — |
| `advisor_id` | INTEGER | No | — |
| `target_role` | varchar | No | — |
| `strengths` | TEXT | Yes | — |
| `skill_gaps` | TEXT | Yes | — |
| `advisor_comment` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `advisor_id` → `users.id`; delete: CASCADE.
- `student_id` → `users.id`; delete: CASCADE.
- Unique index: `student_id`.

## companies

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `name` | varchar | No | — |
| `website` | varchar | Yes | — |
| `verified` | tinyint(1) | No | '0' |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `industry` | varchar | Yes | — |
| `description` | TEXT | Yes | — |
| `logo_path` | varchar | Yes | — |
| `verified_by` | INTEGER | Yes | — |
| `verified_at` | datetime | Yes | — |

- `verified_by` → `users.id`; delete: SET NULL.

## counselling_sessions

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `student_id` | INTEGER | No | — |
| `advisor_id` | INTEGER | No | — |
| `type` | varchar | No | — |
| `scheduled_at` | datetime | No | — |
| `ends_at` | datetime | No | — |
| `mode` | varchar | No | — |
| `location_or_link` | varchar | Yes | — |
| `status` | varchar | No | 'requested' |
| `student_note` | TEXT | Yes | — |
| `cancelled_by` | INTEGER | Yes | — |
| `cancel_reason` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `cancelled_by` → `users.id`; delete: SET NULL.
- `advisor_id` → `users.id`; delete: CASCADE.
- `student_id` → `users.id`; delete: CASCADE.
- Index: `status`.
- Index: `scheduled_at`.
- Index: `advisor_id, scheduled_at`.

## cv_reviews

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `cv_id` | INTEGER | No | — |
| `advisor_id` | INTEGER | No | — |
| `decision` | varchar | No | — |
| `strengths` | TEXT | No | — |
| `improvements` | TEXT | No | — |
| `placement_recommendation` | varchar | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `advisor_id` → `users.id`; delete: CASCADE.
- `cv_id` → `cvs.id`; delete: CASCADE.

## cvs

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `version` | INTEGER | No | — |
| `path` | varchar | No | — |
| `original_name` | varchar | Yes | — |
| `status` | varchar | No | 'pending' |
| `feedback` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `title` | varchar | Yes | — |
| `source` | varchar | No | 'uploaded' |
| `content` | TEXT | Yes | — |
| `file_path` | varchar | Yes | — |
| `mime` | varchar | Yes | — |
| `size` | INTEGER | Yes | — |
| `submitted_at` | datetime | Yes | — |
| `reviewed_by` | INTEGER | Yes | — |
| `reviewed_at` | datetime | Yes | — |
| `deleted_at` | datetime | Yes | — |

- `reviewed_by` → `users.id`; delete: SET NULL.
- `user_id` → `users.id`; delete: CASCADE.
- Unique index: `user_id, version`.
- Index: `status`.

## failed_jobs

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `uuid` | varchar | No | — |
| `connection` | TEXT | No | — |
| `queue` | TEXT | No | — |
| `payload` | TEXT | No | — |
| `exception` | TEXT | No | — |
| `failed_at` | datetime | No | CURRENT_TIMESTAMP |

- Unique index: `uuid`.

## interview_feedback

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `interview_id` | INTEGER | No | — |
| `recruiter_id` | INTEGER | No | — |
| `rating` | INTEGER | No | — |
| `recommendation` | varchar | No | — |
| `comments` | TEXT | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `recruiter_id` → `users.id`; delete: CASCADE.
- `interview_id` → `interviews.id`; delete: CASCADE.
- Unique index: `interview_id`.

## interviews

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `application_id` | INTEGER | No | — |
| `scheduled_at` | datetime | No | — |
| `mode` | varchar | No | 'Video call' |
| `link` | varchar | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `scheduled_by` | INTEGER | Yes | — |
| `duration_min` | INTEGER | No | '30' |
| `type` | varchar | No | 'Technical' |
| `interviewer` | varchar | Yes | — |
| `link_or_location` | varchar | Yes | — |
| `prep_notes` | TEXT | Yes | — |
| `status` | varchar | No | 'scheduled' |

- `scheduled_by` → `users.id`; delete: SET NULL.
- `application_id` → `applications.id`; delete: CASCADE.
- Index: `status`.
- Index: `scheduled_at`.
- Unique index: `application_id`.

## job_batches

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | varchar | No | — |
| `name` | varchar | No | — |
| `total_jobs` | INTEGER | No | — |
| `pending_jobs` | INTEGER | No | — |
| `failed_jobs` | INTEGER | No | — |
| `failed_job_ids` | TEXT | No | — |
| `options` | TEXT | Yes | — |
| `cancelled_at` | INTEGER | Yes | — |
| `created_at` | INTEGER | No | — |
| `finished_at` | INTEGER | Yes | — |

- Unique index: `id`.

## jobs

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `queue` | varchar | No | — |
| `payload` | TEXT | No | — |
| `attempts` | INTEGER | No | — |
| `reserved_at` | INTEGER | Yes | — |
| `available_at` | INTEGER | No | — |
| `created_at` | INTEGER | No | — |

- Index: `queue`.

## migrations

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `migration` | varchar | No | — |
| `batch` | INTEGER | No | — |


## notes

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `student_id` | INTEGER | No | — |
| `body` | TEXT | No | — |
| `private` | tinyint(1) | No | '0' |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `student_id` → `users.id`; delete: CASCADE.
- `user_id` → `users.id`; delete: CASCADE.

## notifications

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | varchar | No | — |
| `type` | varchar | No | — |
| `notifiable_type` | varchar | No | — |
| `notifiable_id` | INTEGER | No | — |
| `data` | TEXT | No | — |
| `read_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- Index: `notifiable_type, notifiable_id`.
- Unique index: `id`.

## offers

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `application_id` | INTEGER | No | — |
| `details` | TEXT | No | — |
| `expires_at` | datetime | No | — |
| `status` | varchar | No | 'pending' |
| `responded_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `application_id` → `applications.id`; delete: CASCADE.
- Index: `status`.
- Index: `expires_at`.
- Unique index: `application_id`.

## opportunities

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `company_id` | INTEGER | No | — |
| `title` | varchar | No | — |
| `type` | varchar | No | 'Internship' |
| `location` | varchar | No | — |
| `description` | TEXT | No | — |
| `skills` | TEXT | Yes | — |
| `deadline` | date | Yes | — |
| `status` | varchar | No | 'open' |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `posted_by` | INTEGER | Yes | — |
| `work_mode` | varchar | No | 'On-site' |
| `is_paid` | tinyint(1) | No | '0' |
| `stipend_min` | INTEGER | Yes | — |
| `stipend_max` | INTEGER | Yes | — |
| `stipend_label` | varchar | Yes | — |
| `responsibilities` | TEXT | Yes | — |
| `requirements` | TEXT | Yes | — |
| `min_cgpa` | numeric | Yes | — |
| `approved_by` | INTEGER | Yes | — |
| `rejection_reason` | TEXT | Yes | — |
| `deleted_at` | datetime | Yes | — |

- `approved_by` → `users.id`; delete: SET NULL.
- `posted_by` → `users.id`; delete: SET NULL.
- `company_id` → `companies.id`; delete: CASCADE.
- Index: `work_mode`.
- Index: `location`.
- Index: `type`.
- Index: `status, deadline`.

## opportunity_skill

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `opportunity_id` | INTEGER | No | — |
| `skill_id` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `skill_id` → `skills.id`; delete: CASCADE.
- `opportunity_id` → `opportunities.id`; delete: CASCADE.
- Unique index: `opportunity_id, skill_id`.

## password_reset_tokens

| Column | Type | Nullable | Default |
|---|---|---|---|
| `email` (PK) | varchar | No | — |
| `token` | varchar | No | — |
| `created_at` | datetime | Yes | — |

- Unique index: `email`.

## personal_access_tokens

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `tokenable_type` | varchar | No | — |
| `tokenable_id` | INTEGER | No | — |
| `name` | TEXT | No | — |
| `token` | varchar | No | — |
| `abilities` | TEXT | Yes | — |
| `last_used_at` | datetime | Yes | — |
| `expires_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- Index: `expires_at`.
- Unique index: `token`.
- Index: `tokenable_type, tokenable_id`.

## placement_records

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `application_id` | INTEGER | No | — |
| `student_id` | INTEGER | No | — |
| `company_id` | INTEGER | No | — |
| `role_title` | varchar | No | — |
| `job_type` | varchar | No | — |
| `start_date` | date | No | — |
| `recorded_by` | INTEGER | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `recorded_by` → `users.id`; delete: SET NULL.
- `company_id` → `companies.id`; delete: RESTRICT.
- `student_id` → `users.id`; delete: CASCADE.
- `application_id` → `applications.id`; delete: CASCADE.
- Index: `start_date`.
- Unique index: `application_id`.

## plan_milestones

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `plan_id` | INTEGER | No | — |
| `title` | varchar | No | — |
| `status` | varchar | No | 'not_started' |
| `due_date` | date | Yes | — |
| `sort_order` | INTEGER | No | '0' |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `plan_id` → `career_plans.id`; delete: CASCADE.
- Index: `due_date`.
- Index: `status`.

## profile_certificates

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `title` | varchar | No | — |
| `org` | varchar | No | — |
| `start_date` | date | Yes | — |
| `end_date` | date | Yes | — |
| `description` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `user_id` → `users.id`; delete: CASCADE.

## profile_experiences

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `title` | varchar | No | — |
| `org` | varchar | No | — |
| `start_date` | date | Yes | — |
| `end_date` | date | Yes | — |
| `description` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `user_id` → `users.id`; delete: CASCADE.

## reminders

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `type` | varchar | No | — |
| `remindable_type` | varchar | No | — |
| `remindable_id` | INTEGER | No | — |
| `remind_at` | datetime | No | — |
| `sent_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `user_id` → `users.id`; delete: CASCADE.
- Index: `remind_at`.
- Index: `remindable_type, remindable_id`.

## saved_opportunities

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `opportunity_id` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `opportunity_id` → `opportunities.id`; delete: CASCADE.
- `user_id` → `users.id`; delete: CASCADE.
- Unique index: `user_id, opportunity_id`.

## session_feedback

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `session_id` | INTEGER | No | — |
| `advisor_id` | INTEGER | No | — |
| `body` | TEXT | No | — |
| `next_step` | TEXT | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `advisor_id` → `users.id`; delete: CASCADE.
- `session_id` → `counselling_sessions.id`; delete: CASCADE.
- Unique index: `session_id`.

## sessions

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | varchar | No | — |
| `user_id` | INTEGER | Yes | — |
| `ip_address` | varchar | Yes | — |
| `user_agent` | TEXT | Yes | — |
| `payload` | TEXT | No | — |
| `last_activity` | INTEGER | No | — |

- Index: `last_activity`.
- Index: `user_id`.
- Unique index: `id`.

## settings

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `key` | varchar | No | — |
| `value` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- Unique index: `key`.

## skills

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `name` | varchar | No | — |
| `type` | varchar | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- Index: `type`.
- Unique index: `name`.

## student_profiles

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `student_no` | varchar | Yes | — |
| `department` | varchar | Yes | — |
| `batch` | varchar | Yes | — |
| `cgpa` | numeric | Yes | — |
| `ssc_institution` | varchar | Yes | — |
| `ssc_board` | varchar | Yes | — |
| `hsc_institution` | varchar | Yes | — |
| `hsc_board` | varchar | Yes | — |
| `ssc_year` | INTEGER | Yes | — |
| `hsc_year` | INTEGER | Yes | — |
| `ssc_gpa` | numeric | Yes | — |
| `hsc_gpa` | numeric | Yes | — |
| `career_objective` | TEXT | Yes | — |
| `interests` | TEXT | Yes | — |
| `has_experience` | tinyint(1) | Yes | — |
| `has_certificates` | tinyint(1) | Yes | — |
| `completion_percent` | INTEGER | No | '0' |
| `wizard_step` | INTEGER | No | '1' |
| `verification_status` | varchar | No | 'pending' |
| `verified_by` | INTEGER | Yes | — |
| `verified_at` | datetime | Yes | — |
| `verification_reason` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `verified_by` → `users.id`; delete: SET NULL.
- `user_id` → `users.id`; delete: CASCADE.
- Index: `verification_status`.
- Index: `department`.
- Unique index: `student_no`.
- Unique index: `user_id`.

## student_skill

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `skill_id` | INTEGER | No | — |
| `level` | varchar | No | 'beginner' |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

- `skill_id` → `skills.id`; delete: CASCADE.
- `user_id` → `users.id`; delete: CASCADE.
- Unique index: `user_id, skill_id`.

## users

| Column | Type | Nullable | Default |
|---|---|---|---|
| `id` (PK) | INTEGER | No | — |
| `name` | varchar | No | — |
| `email` | varchar | No | — |
| `email_verified_at` | datetime | Yes | — |
| `password` | varchar | No | — |
| `remember_token` | varchar | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `role` | varchar | No | 'student' |
| `advisor_id` | INTEGER | Yes | — |
| `company_id` | INTEGER | Yes | — |
| `department` | varchar | Yes | — |
| `skills` | TEXT | Yes | — |
| `status` | varchar | No | 'active' |
| `phone` | varchar | Yes | — |
| `avatar_path` | varchar | Yes | — |
| `must_change_password` | tinyint(1) | No | '0' |
| `last_login_at` | datetime | Yes | — |
| `deleted_at` | datetime | Yes | — |

- `company_id` → `companies.id`; delete: SET NULL.
- `advisor_id` → `users.id`; delete: SET NULL.
- Index: `status`.
- Index: `role`.
- Unique index: `email`.


## Final platform additions

- users.avatar_path: nullable private local-storage path. User API exposes only has_avatar and avatar_version, not the path.
- users.status: active / pending / suspended; public staff accounts start pending.
- notifications: UUID, notifiable user, type, JSON payload (title/body/category/url), nullable read_at, timestamps.
- notification_events: unique event_key including recipient ID, timestamps; scheduler delivery deduplication.
- reminders: user, type=followup, polymorphic application reference, nullable title, repeat interval_days (1–90), remind_at, sent_at.
- settings: unique key/value; contact and policy text, academic_session, default_capacity.
- audit_logs: actor, action, entity type/id, JSON new_values, request IP/user agent when available; no plaintext passwords.
