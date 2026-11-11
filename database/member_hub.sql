-- Mo rong Tủ sách, thông báo và chuỗi điểm danh cho Truyện Đêm.
-- Chọn đúng database trong phpMyAdmin và chạy file này MỘT LẦN.
-- Không xóa dữ liệu thành viên, truyện hoặc số Xu hiện có.

CREATE TABLE IF NOT EXISTS member_notifications (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 recipient_id INT UNSIGNED NOT NULL,
 actor_id INT UNSIGNED DEFAULT NULL,
 notification_type VARCHAR(40) NOT NULL,
 title VARCHAR(160) NOT NULL,
 message VARCHAR(255) NOT NULL,
 target_url VARCHAR(500) NOT NULL DEFAULT '/notifications.php',
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 KEY idx_member_notifications_inbox (recipient_id,is_read,created_at),
 KEY idx_member_notifications_actor (actor_id),
 CONSTRAINT fk_member_notification_recipient FOREIGN KEY (recipient_id) REFERENCES members(id) ON DELETE CASCADE,
 CONSTRAINT fk_member_notification_actor FOREIGN KEY (actor_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_daily_streaks (
 member_id INT UNSIGNED NOT NULL,
 last_claim_date DATE DEFAULT NULL,
 current_streak INT UNSIGNED NOT NULL DEFAULT 0,
 best_streak INT UNSIGNED NOT NULL DEFAULT 0,
 total_days INT UNSIGNED NOT NULL DEFAULT 0,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (member_id),
 CONSTRAINT fk_daily_streak_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO member_daily_streaks(member_id) SELECT id FROM members;
