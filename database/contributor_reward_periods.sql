-- Truyen Dem: ky chot va chi tiet tinh nhuan but tu dong.
-- Chay sau contributor_rewards.sql, contributor_reward_settings.sql va story_read_events.sql.
CREATE TABLE IF NOT EXISTS contributor_reward_periods (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 period_key CHAR(7) NOT NULL,
 period_start DATE NOT NULL,
 period_end DATE NOT NULL,
 net_revenue DECIMAL(14,2) NOT NULL DEFAULT 0,
 allocation_percent DECIMAL(5,2) NOT NULL,
 total_fund DECIMAL(14,2) NOT NULL,
 publication_pool DECIMAL(14,2) NOT NULL,
 reading_pool DECIMAL(14,2) NOT NULL,
 achievement_pool DECIMAL(14,2) NOT NULL,
 status ENUM('generated','paid','cancelled') NOT NULL DEFAULT 'generated',
 created_by_admin_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 UNIQUE KEY uq_reward_period(period_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contributor_reward_period_items (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 period_id BIGINT UNSIGNED NOT NULL,
 member_id INT UNSIGNED NOT NULL,
 approved_works INT UNSIGNED NOT NULL DEFAULT 0,
 qualified_reads BIGINT UNSIGNED NOT NULL DEFAULT 0,
 publication_raw DECIMAL(14,2) NOT NULL DEFAULT 0,
 reading_raw DECIMAL(14,2) NOT NULL DEFAULT 0,
 publication_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
 reading_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
 total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
 reward_id BIGINT UNSIGNED DEFAULT NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_period_member(period_id,member_id),
 KEY idx_reward_item_member(member_id),
 KEY idx_reward_item_reward(reward_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
