-- Góc Hoàng Đạo: trạng thái online/offline và tin nhắn riêng.
-- Chạy MỘT LẦN trong phpMyAdmin, chọn đúng database website.

ALTER TABLE members ADD COLUMN last_seen_at DATETIME NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS member_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sender_id INT UNSIGNED NOT NULL,
  recipient_id INT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  read_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_message_sender_recipient (sender_id, recipient_id, created_at),
  KEY idx_message_recipient_read (recipient_id, read_at, created_at),
  CONSTRAINT fk_message_sender FOREIGN KEY (sender_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_message_recipient FOREIGN KEY (recipient_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
