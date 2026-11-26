-- Truyện Đêm: phòng trò chuyện chung. Chạy một lần trong phpMyAdmin.
CREATE TABLE IF NOT EXISTS community_chat_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  message VARCHAR(1000) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_chat_created (created_at,id),
  KEY idx_chat_member_created (member_id,created_at),
  CONSTRAINT fk_community_chat_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
