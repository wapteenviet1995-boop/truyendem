-- Truyen Dem: bao cao vi pham, cong tac vien va kiem duyet vien
-- Chay mot lan trong phpMyAdmin, chon dung database website.

ALTER TABLE members
  ADD COLUMN account_type ENUM('member','contributor') NOT NULL DEFAULT 'member' AFTER is_active;

CREATE TABLE IF NOT EXISTS content_reports (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  story_id INT UNSIGNED NOT NULL,
  chapter_id INT UNSIGNED DEFAULT NULL,
  reporter_member_id INT UNSIGNED DEFAULT NULL,
  reporter_name VARCHAR(100) DEFAULT NULL,
  reporter_email VARCHAR(190) DEFAULT NULL,
  reason ENUM('spam','copyright','harassment','illegal','other') NOT NULL DEFAULT 'other',
  details TEXT NOT NULL,
  ip_hash CHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  status ENUM('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
  admin_note TEXT DEFAULT NULL,
  assigned_admin_id INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_content_reports_status_date (status, created_at),
  KEY idx_content_reports_story (story_id),
  KEY idx_content_reports_chapter (chapter_id),
  KEY idx_content_reports_reporter (reporter_member_id),
  KEY idx_content_reports_ip_date (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
