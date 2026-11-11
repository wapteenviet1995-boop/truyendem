-- He thong EXP va cap do rieng, tach biet hoan toan voi Xu.
-- Chay file nay mot lan trong phpMyAdmin.
CREATE TABLE IF NOT EXISTS member_experience (
 member_id INT UNSIGNED NOT NULL,
 total_xp BIGINT UNSIGNED NOT NULL DEFAULT 0,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (member_id),
 CONSTRAINT fk_member_experience_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_xp_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 member_id INT UNSIGNED NOT NULL,
 event_key VARCHAR(120) NOT NULL,
 amount INT UNSIGNED NOT NULL,
 description VARCHAR(180) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 UNIQUE KEY uq_member_xp_event (member_id,event_key),
 KEY idx_member_xp_events_member (member_id,created_at),
 CONSTRAINT fk_member_xp_event_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO member_experience(member_id,total_xp) SELECT id,0 FROM members;
