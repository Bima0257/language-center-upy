CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "phone" varchar,
  "photo" varchar,
  "is_active" tinyint(1) not null default '1',
  "google_id" varchar,
  "google_avatar" varchar,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" varchar not null,
  "queue" varchar not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE INDEX "failed_jobs_connection_queue_failed_at_index" on "failed_jobs"(
  "connection",
  "queue",
  "failed_at"
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "roles_name_guard_name_unique" on "roles"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "model_has_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  primary key("permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "model_has_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "role_has_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "activity_log"(
  "id" integer primary key autoincrement not null,
  "log_name" varchar,
  "description" text not null,
  "subject_type" varchar,
  "subject_id" integer,
  "event" varchar,
  "causer_type" varchar,
  "causer_id" integer,
  "properties" text,
  "batch_uuid" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "subject" on "activity_log"("subject_type", "subject_id");
CREATE INDEX "causer" on "activity_log"("causer_type", "causer_id");
CREATE INDEX "activity_log_log_name_index" on "activity_log"("log_name");
CREATE TABLE IF NOT EXISTS "student_profiles"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "nim" varchar,
  "faculty" varchar,
  "department" varchar,
  "batch_year" integer,
  "identity_photo" varchar,
  "is_verified" tinyint(1) not null default '0',
  "verified_at" datetime,
  "verified_by" integer,
  "verification_note" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("verified_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "student_profiles_user_id_unique" on "student_profiles"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "exam_types"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "target_language" varchar not null,
  "section_config" text not null,
  "max_strikes" integer not null default '3',
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "passages"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "content_text" text,
  "audio_url" varchar,
  "image_url" varchar,
  "language" varchar not null,
  "word_count" integer,
  "source" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "exams"(
  "id" integer primary key autoincrement not null,
  "exam_type_id" integer,
  "title" varchar not null,
  "description" text,
  "mode" varchar check("mode" in('tryout', 'official')) not null default 'tryout',
  "duration_minutes" integer not null default '160',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_type_id") references "exam_types"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "exam_sections"(
  "id" integer primary key autoincrement not null,
  "exam_id" integer not null,
  "skill" varchar not null,
  "type" varchar check("type" in('reading', 'listening', 'speaking', 'writing')),
  "title" varchar not null,
  "order" integer not null,
  "duration_minutes" integer,
  "instructions" text,
  "total_questions" integer not null default '0',
  "navigation_enabled" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_id") references "exams"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "question_groups"(
  "id" integer primary key autoincrement not null,
  "exam_section_id" integer not null,
  "passage_id" integer,
  "title" varchar,
  "group_type" varchar check("group_type" in('passage', 'conversation', 'lecture', 'standalone', 'prompt')) not null default 'passage',
  "passage_text" text,
  "audio_file" varchar,
  "image" varchar,
  "topic" varchar,
  "word_count" integer,
  "order" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_section_id") references "exam_sections"("id") on delete cascade,
  foreign key("passage_id") references "passages"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "tags"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "type" varchar not null default 'general',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "tags_name_type_unique" on "tags"("name", "type");
CREATE TABLE IF NOT EXISTS "question_tag"(
  "question_id" integer not null,
  "tag_id" integer not null,
  foreign key("question_id") references "questions"("id") on delete cascade,
  foreign key("tag_id") references "tags"("id") on delete cascade,
  primary key("question_id", "tag_id")
);
CREATE TABLE IF NOT EXISTS "test_forms"(
  "id" integer primary key autoincrement not null,
  "exam_type_id" integer not null,
  "exam_id" integer,
  "name" varchar not null,
  "assembly_mode" varchar check("assembly_mode" in('manual', 'random_pool', 'rule_based')) not null default 'manual',
  "status" varchar check("status" in('draft', 'published', 'archived')) not null default 'draft',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_type_id") references "exam_types"("id") on delete cascade,
  foreign key("exam_id") references "exams"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "test_form_questions"(
  "id" integer primary key autoincrement not null,
  "test_form_id" integer not null,
  "question_id" integer not null,
  "order" integer not null default '0',
  foreign key("test_form_id") references "test_forms"("id") on delete cascade,
  foreign key("question_id") references "questions"("id") on delete cascade
);
CREATE UNIQUE INDEX "test_form_questions_test_form_id_question_id_unique" on "test_form_questions"(
  "test_form_id",
  "question_id"
);
CREATE TABLE IF NOT EXISTS "scoring_rules"(
  "id" integer primary key autoincrement not null,
  "exam_type_id" integer not null,
  "section_skill" varchar not null,
  "conversion_table" text not null,
  "scoring_method" varchar check("scoring_method" in('raw_to_scaled', 'rubric', 'weighted')) not null default 'raw_to_scaled',
  "rubric" text,
  "max_raw" integer not null,
  "max_scaled" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_type_id") references "exam_types"("id") on delete cascade
);
CREATE UNIQUE INDEX "scoring_rules_exam_type_id_section_skill_unique" on "scoring_rules"(
  "exam_type_id",
  "section_skill"
);
CREATE TABLE IF NOT EXISTS "exam_schedules"(
  "id" integer primary key autoincrement not null,
  "exam_id" integer not null,
  "title" varchar not null,
  "scheduled_start" datetime not null,
  "scheduled_end" datetime not null,
  "late_tolerance_minutes" integer not null default '15',
  "max_participants" integer not null default '30',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_id") references "exams"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "exam_sessions"(
  "id" integer primary key autoincrement not null,
  "exam_schedule_id" integer not null,
  "user_id" integer not null,
  "status" varchar check("status" in('pending', 'in_progress', 'submitted', 'terminated', 'reviewed')) not null default 'pending',
  "started_at" datetime,
  "submitted_at" datetime,
  "terminated_at" datetime,
  "termination_reason" varchar,
  "current_section_id" integer,
  "last_heartbeat_at" datetime,
  "review_status" varchar check("review_status" in('sah', 'ujian_ulang', 'dibatalkan')),
  "review_note" text,
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "score_reading" numeric,
  "score_listening" numeric,
  "score_speaking" numeric,
  "score_writing" numeric,
  "score_total" numeric,
  "is_flagged" tinyint(1) not null default '0',
  "flag_reason" varchar,
  "violation_strikes" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_schedule_id") references "exam_schedules"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("current_section_id") references "exam_sections"("id") on delete set null,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "exam_sessions_exam_schedule_id_user_id_unique" on "exam_sessions"(
  "exam_schedule_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "answers"(
  "id" integer primary key autoincrement not null,
  "exam_session_id" integer not null,
  "question_id" integer not null,
  "answer_text" text,
  "answer_json" text,
  "audio_file" varchar,
  "audio_duration" integer,
  "is_correct" tinyint(1),
  "score" numeric,
  "scorer_id" integer,
  "scored_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_session_id") references "exam_sessions"("id") on delete cascade,
  foreign key("question_id") references "questions"("id") on delete cascade,
  foreign key("scorer_id") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "answers_exam_session_id_question_id_unique" on "answers"(
  "exam_session_id",
  "question_id"
);
CREATE TABLE IF NOT EXISTS "flagged_questions"(
  "id" integer primary key autoincrement not null,
  "exam_session_id" integer not null,
  "question_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_session_id") references "exam_sessions"("id") on delete cascade,
  foreign key("question_id") references "questions"("id") on delete cascade
);
CREATE UNIQUE INDEX "flagged_questions_exam_session_id_question_id_unique" on "flagged_questions"(
  "exam_session_id",
  "question_id"
);
CREATE TABLE IF NOT EXISTS "violation_logs"(
  "id" integer primary key autoincrement not null,
  "exam_session_id" integer not null,
  "type" varchar not null,
  "severity" varchar check("severity" in('warning', 'minor', 'major', 'critical')) not null default 'minor',
  "description" text,
  "metadata" text,
  "strike_count" integer not null,
  "screenshot_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exam_session_id") references "exam_sessions"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "questions"(
  "id" integer primary key autoincrement not null,
  "type" varchar not null,
  "question_text" text not null,
  "options" text,
  "correct_answer" text,
  "correct_answers" text,
  "points" integer not null default('1'),
  "order" integer not null,
  "passage_reference" varchar,
  "difficulty" varchar not null default('medium'),
  "status" varchar not null default('draft'),
  "created_by" integer,
  "updated_by" integer,
  "time_estimate" integer,
  "explanation" text,
  "created_at" datetime,
  "updated_at" datetime,
  "skill" varchar not null,
  "passage_id" integer,
  "audio_file" varchar,
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "review_note" text,
  question_group_id INTEGER REFERENCES question_groups(id) ON DELETE SET NULL,
  foreign key("reviewed_by") references users("id") on delete set null on update no action,
  foreign key("passage_id") references passages("id") on delete set null on update no action,
  foreign key("created_by") references users("id") on delete set null on update no action,
  foreign key("updated_by") references users("id") on delete set null on update no action
);
CREATE INDEX "questions_question_group_id_index" on "questions"(
  "question_group_id"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2026_07_08_020606_create_permission_tables',1);
INSERT INTO migrations VALUES(5,'2026_07_08_020607_create_activity_log_table',1);
INSERT INTO migrations VALUES(6,'2026_07_10_000000_create_student_profiles_table',1);
INSERT INTO migrations VALUES(7,'2026_07_10_000001_create_exam_types_table',1);
INSERT INTO migrations VALUES(8,'2026_07_10_000002_create_passages_table',1);
INSERT INTO migrations VALUES(9,'2026_07_10_000003_create_exams_table',1);
INSERT INTO migrations VALUES(10,'2026_07_10_000004_create_exam_sections_table',1);
INSERT INTO migrations VALUES(11,'2026_07_10_000005_create_question_groups_table',1);
INSERT INTO migrations VALUES(12,'2026_07_10_000006_create_questions_table',1);
INSERT INTO migrations VALUES(13,'2026_07_10_000007_create_tags_table',1);
INSERT INTO migrations VALUES(14,'2026_07_10_000008_create_question_tag_table',1);
INSERT INTO migrations VALUES(15,'2026_07_10_000009_create_exam_section_question_table',1);
INSERT INTO migrations VALUES(16,'2026_07_10_000010_create_test_forms_table',1);
INSERT INTO migrations VALUES(17,'2026_07_10_000011_create_test_form_questions_table',1);
INSERT INTO migrations VALUES(18,'2026_07_10_000012_create_scoring_rules_table',1);
INSERT INTO migrations VALUES(19,'2026_07_10_000013_create_exam_schedules_table',1);
INSERT INTO migrations VALUES(20,'2026_07_10_000014_create_exam_sessions_table',1);
INSERT INTO migrations VALUES(21,'2026_07_10_000015_create_answers_table',1);
INSERT INTO migrations VALUES(22,'2026_07_10_000016_create_flagged_questions_table',1);
INSERT INTO migrations VALUES(23,'2026_07_10_000017_create_violation_logs_table',1);
INSERT INTO migrations VALUES(24,'2026_07_26_000001_modify_questions_for_bank_soal',1);
INSERT INTO migrations VALUES(25,'2026_07_26_000002_drop_exam_section_question_table',1);
