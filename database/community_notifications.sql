-- Thong bao CMS va dam bao co hop thu thong bao thanh vien.
-- Chay mot lan trong phpMyAdmin sau cac migration cong dong truoc do.
CREATE TABLE IF NOT EXISTS admin_notifications (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 recipient_admin_id INT UNSIGNED NOT NULL,
 notification_type VARCHAR(40) NOT NULL,
 title VARCHAR(160) NOT NULL,
 message VARCHAR(255) NOT NULL,
 target_url VARCHAR(500) NOT NULL DEFAULT 'notifications.php',
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 KEY idx_admin_notifications_inbox (recipient_admin_id,is_read,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
 KEY idx_member_notifications_actor (actor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
