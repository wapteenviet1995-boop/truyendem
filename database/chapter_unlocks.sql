-- Mo khoa chuong bang Xu cho Truyen Dem
-- Sao luu database truoc khi chay file nay trong phpMyAdmin.
ALTER TABLE chapters
  ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER content,
  ADD COLUMN unlock_price INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_locked;

ALTER TABLE wallet_transactions
  MODIFY type ENUM('daily','purchase','gift_sent','gift_received','admin','post_reward','chapter_unlock','chapter_earning') NOT NULL;

CREATE TABLE IF NOT EXISTS chapter_unlocks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  chapter_id INT UNSIGNED NOT NULL,
  author_id INT UNSIGNED NOT NULL,
  price_paid INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_member_chapter_unlock (member_id, chapter_id),
  KEY idx_chapter_unlock_author (author_id, created_at),
  CONSTRAINT fk_chapter_unlock_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_chapter_unlock_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
  CONSTRAINT fk_chapter_unlock_author FOREIGN KEY (author_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
