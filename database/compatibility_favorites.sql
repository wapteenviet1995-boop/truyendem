-- Chạy file này một lần trong phpMyAdmin, chọn đúng database website.
-- Lưu các cặp cung tương hợp mà thành viên yêu thích.
CREATE TABLE IF NOT EXISTS member_compatibility_favorites (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  sign_one TINYINT UNSIGNED NOT NULL,
  sign_two TINYINT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_member_pair (member_id, sign_one, sign_two),
  KEY idx_member_favorites (member_id, created_at),
  CONSTRAINT fk_compat_favorite_member
    FOREIGN KEY (member_id) REFERENCES members(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
