-- Truyen Dem: cau hinh quy tac tinh nhuan but CTV.
-- Chay mot lan trong phpMyAdmin sau khi da tao story_read_events.
CREATE TABLE IF NOT EXISTS contributor_reward_settings (
 id TINYINT UNSIGNED NOT NULL,
 allocation_percent DECIMAL(5,2) NOT NULL DEFAULT 30.00,
 publication_pool_percent DECIMAL(5,2) NOT NULL DEFAULT 40.00,
 reading_pool_percent DECIMAL(5,2) NOT NULL DEFAULT 40.00,
 achievement_pool_percent DECIMAL(5,2) NOT NULL DEFAULT 20.00,
 short_story_fee DECIMAL(12,2) NOT NULL DEFAULT 10000.00,
 serial_story_fee DECIMAL(12,2) NOT NULL DEFAULT 20000.00,
 audio_story_fee DECIMAL(12,2) NOT NULL DEFAULT 30000.00,
 qualified_reads_per_bonus INT UNSIGNED NOT NULL DEFAULT 1000,
 reading_bonus_amount DECIMAL(12,2) NOT NULL DEFAULT 10000.00,
 updated_by_admin_id INT UNSIGNED DEFAULT NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO contributor_reward_settings
(id,allocation_percent,publication_pool_percent,reading_pool_percent,achievement_pool_percent,short_story_fee,serial_story_fee,audio_story_fee,qualified_reads_per_bonus,reading_bonus_amount)
VALUES (1,30.00,40.00,40.00,20.00,10000.00,20000.00,30000.00,1000,10000.00);
