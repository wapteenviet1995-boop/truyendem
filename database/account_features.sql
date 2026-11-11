-- Tính năng mở rộng trang Tài khoản Góc Hoàng Đạo.
-- Chọn đúng database trong phpMyAdmin và chạy file này MỘT LẦN.
-- Sao lưu database trước khi chạy. File không xóa hoặc sửa dữ liệu thành viên hiện có.
ALTER TABLE members
  ADD COLUMN birth_date DATE NULL DEFAULT NULL,
  ADD COLUMN avatar_url VARCHAR(255) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS member_account_settings (
  member_id INT UNSIGNED NOT NULL,
  profile_visibility ENUM('public','friends','private') NOT NULL DEFAULT 'public',
  show_online TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id),
  CONSTRAINT fk_account_settings_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_favorite_signs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  zodiac_sign TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_member_favorite_sign (member_id,zodiac_sign),
  KEY idx_favorite_sign (zodiac_sign),
  CONSTRAINT fk_favorite_sign_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO member_account_settings(member_id,profile_visibility,show_online)
SELECT id,'public',1 FROM members;
