-- Truyện Đêm: bảng lưu trạng thái online trong phòng trò chuyện.
-- Chạy một lần trong phpMyAdmin sau khi cập nhật code.
CREATE TABLE IF NOT EXISTS community_chat_presence (
  member_id INT UNSIGNED NOT NULL,
  last_seen DATETIME NOT NULL,
  PRIMARY KEY (member_id),
  KEY idx_chat_presence_seen (last_seen),
  CONSTRAINT fk_community_chat_presence_member
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
