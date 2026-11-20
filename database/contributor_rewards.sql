CREATE TABLE IF NOT EXISTS contributor_rewards (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 member_id INT UNSIGNED NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 reward_type ENUM('royalty','bonus','adjustment') NOT NULL DEFAULT 'royalty',
 description VARCHAR(255) NOT NULL,
 period_label VARCHAR(80) DEFAULT NULL,
 payment_status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
 paid_at DATETIME DEFAULT NULL,
 created_by_admin_id INT UNSIGNED DEFAULT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_reward_member(member_id,created_at), KEY idx_reward_status(payment_status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;