-- Bang luu ket qua duyet truyen va chuong do thanh vien gui
CREATE TABLE IF NOT EXISTS story_moderation_reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  target_type ENUM('story','chapter') NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  story_id BIGINT UNSIGNED NOT NULL,
  decision ENUM('approved','rejected') NOT NULL,
  note VARCHAR(500) NOT NULL DEFAULT '',
  admin_id INT UNSIGNED NOT NULL,
  reviewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_story_moderation_target (target_type,target_id),
  KEY idx_story_moderation_story (story_id,reviewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
