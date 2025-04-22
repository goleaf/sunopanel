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
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
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
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "genres"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "genres_slug_unique" on "genres"("slug");
CREATE TABLE IF NOT EXISTS "genre_track"(
  "id" integer primary key autoincrement not null,
  "genre_id" integer not null,
  "track_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("genre_id") references "genres"("id") on delete cascade,
  foreign key("track_id") references "tracks"("id") on delete cascade
);
CREATE UNIQUE INDEX "genre_track_genre_id_track_id_unique" on "genre_track"(
  "genre_id",
  "track_id"
);
CREATE TABLE IF NOT EXISTS "tracks"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "slug" varchar not null,
  "mp3_url" varchar not null,
  "image_url" varchar not null,
  "mp3_path" varchar,
  "image_path" varchar,
  "mp4_path" varchar,
  "genres_string" text,
  "status" varchar not null default 'pending',
  "progress" integer not null default '0',
  "error_message" text,
  "created_at" datetime,
  "updated_at" datetime,
  "youtube_video_id" varchar,
  "youtube_playlist_id" varchar,
  "youtube_uploaded_at" datetime,
  "youtube_views" integer,
  "youtube_stats_updated_at" datetime,
  "youtube_enabled" tinyint(1) not null default '1'
);
CREATE UNIQUE INDEX "tracks_new_slug_unique" on "tracks"("slug");
CREATE TABLE IF NOT EXISTS "youtube_credentials"(
  "id" integer primary key autoincrement not null,
  "access_token" varchar,
  "refresh_token" varchar,
  "token_created_at" integer,
  "token_expires_in" integer,
  "client_id" varchar,
  "client_secret" varchar,
  "redirect_uri" varchar,
  "use_oauth" tinyint(1) not null default '1',
  "user_email" varchar,
  "created_at" datetime,
  "updated_at" datetime
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2023_10_10_000000_create_genres_table',1);
INSERT INTO migrations VALUES(5,'2024_06_05_000001_create_tracks_table',1);
INSERT INTO migrations VALUES(6,'2024_06_11_000000_update_tracks_table_add_stopped_status',2);
INSERT INTO migrations VALUES(7,'2024_06_22_000000_add_youtube_fields_to_tracks_table',3);
INSERT INTO migrations VALUES(8,'2025_04_22_062629_create_youtube_credentials_table',4);
INSERT INTO migrations VALUES(9,'2023_06_01_add_youtube_views_to_tracks_table',5);
INSERT INTO migrations VALUES(10,'2024_06_24_create_youtube_toggle',5);
