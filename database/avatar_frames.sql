-- Khung avatar thành viên Truyện Đêm
-- Chọn database hiện tại trong phpMyAdmin và chạy file này một lần.
CREATE TABLE IF NOT EXISTS avatar_frames (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  image_url VARCHAR(500) NOT NULL,
  rarity ENUM('common','rare','epic','legendary','mythic') NOT NULL DEFAULT 'common',
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_avatar_frames_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_avatar_frames (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT NOT NULL,
  frame_id INT UNSIGNED NOT NULL,
  granted_by INT NULL,
  acquired_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_member_avatar_frame (member_id, frame_id),
  KEY idx_member_avatar_frames_frame (frame_id),
  KEY idx_member_avatar_frames_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_avatar_equipped (
  member_id INT NOT NULL,
  frame_id INT UNSIGNED NOT NULL,
  equipped_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id),
  KEY idx_avatar_equipped_frame (frame_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
