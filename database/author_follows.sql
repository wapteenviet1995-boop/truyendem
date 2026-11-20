-- Theo doi tac gia CTV tren Truyen Dem. Chay mot lan trong phpMyAdmin.
CREATE TABLE IF NOT EXISTS author_follows (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 follower_member_id INT UNSIGNED NOT NULL,
 author_member_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 UNIQUE KEY uq_author_follow_pair (follower_member_id,author_member_id),
 KEY idx_author_follows_author (author_member_id,created_at),
 CONSTRAINT fk_author_follow_follower FOREIGN KEY (follower_member_id) REFERENCES members(id) ON DELETE CASCADE,
 CONSTRAINT fk_author_follow_author FOREIGN KEY (author_member_id) REFERENCES members(id) ON DELETE CASCADE,
 CONSTRAINT chk_author_follow_not_self CHECK (follower_member_id <> author_member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
