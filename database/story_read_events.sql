-- Truyen Dem: ghi nhan luot doc/nghe hop le de doi soat nhuan but.
-- Chay mot lan trong phpMyAdmin.
CREATE TABLE IF NOT EXISTS story_read_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 story_id INT UNSIGNED NOT NULL,
 target_type ENUM('short_story','chapter','audio') NOT NULL,
 target_id INT UNSIGNED NOT NULL,
 member_id INT UNSIGNED NULL,
 author_id INT UNSIGNED NOT NULL DEFAULT 0,
 visitor_key CHAR(64) NOT NULL,
 read_date DATE NOT NULL,
 active_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 qualified_at DATETIME NULL,
 started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 last_ping_at DATETIME NULL,
 PRIMARY KEY (id),
 UNIQUE KEY uq_read_daily_visitor (story_id,target_type,target_id,visitor_key,read_date),
 KEY idx_read_period (read_date,qualified_at,author_id),
 KEY idx_read_story (story_id,target_type,target_id,read_date),
 KEY idx_read_member (member_id,read_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
