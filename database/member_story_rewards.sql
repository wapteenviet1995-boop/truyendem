-- Thanh vien dang truyen, thuong luot doc va tang Xu cho tac gia
-- Chay mot lan trong phpMyAdmin sau khi sao luu database.

ALTER TABLE stories
  ADD COLUMN member_id INT UNSIGNED NULL AFTER author,
  ADD KEY idx_stories_member (member_id);

CREATE TABLE IF NOT EXISTS story_view_rewards (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  viewer_id INT UNSIGNED NOT NULL,
  author_id INT UNSIGNED NOT NULL,
  target_type ENUM('short_story','chapter') NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  amount INT UNSIGNED NOT NULL DEFAULT 5,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_story_view_reward (viewer_id,target_type,target_id),
  KEY idx_story_reward_author (author_id,created_at),
  CONSTRAINT fk_story_reward_viewer FOREIGN KEY (viewer_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_story_reward_author FOREIGN KEY (author_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
