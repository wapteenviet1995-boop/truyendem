-- Góc Hoàng Đạo: hồ sơ cung, kết bạn và nhật ký riêng tư.
-- Chạy một lần trong phpMyAdmin, chọn đúng database website.

CREATE TABLE IF NOT EXISTS member_profiles (
  member_id INT UNSIGNED NOT NULL,
  zodiac_sign TINYINT UNSIGNED DEFAULT NULL,
  bio VARCHAR(500) NOT NULL DEFAULT '',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id),
  KEY idx_member_profiles_sign (zodiac_sign),
  CONSTRAINT fk_member_profile_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS friend_requests (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  requester_id INT UNSIGNED NOT NULL,
  addressee_id INT UNSIGNED NOT NULL,
  status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_friend_request_pair (requester_id, addressee_id),
  KEY idx_friend_request_inbox (addressee_id, status, created_at),
  KEY idx_friend_request_outbox (requester_id, status, created_at),
  CONSTRAINT fk_friend_request_sender FOREIGN KEY (requester_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_friend_request_receiver FOREIGN KEY (addressee_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_diary (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  entry_date DATE NOT NULL,
  mood VARCHAR(30) NOT NULL DEFAULT 'Bình yên',
  title VARCHAR(150) NOT NULL DEFAULT '',
  content TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_member_diary_date (member_id, entry_date, id),
  CONSTRAINT fk_member_diary_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
