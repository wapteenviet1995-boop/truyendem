-- He thong truyen chuong cho website
-- Sao luu database truoc khi chay file nay trong phpMyAdmin.
CREATE TABLE IF NOT EXISTS stories (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 title VARCHAR(220) NOT NULL,
 slug VARCHAR(240) NOT NULL,
 author VARCHAR(120) NOT NULL DEFAULT 'Khuyết danh',
 description TEXT NULL,
 short_content LONGTEXT NULL,
 cover_url VARCHAR(500) NULL,
 category VARCHAR(100) NOT NULL DEFAULT 'Truyện dài',
 story_type ENUM('text','audio','both') NOT NULL DEFAULT 'text',
 story_kind ENUM('serial','short') NOT NULL DEFAULT 'serial',
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uq_story_slug(slug), KEY idx_story_status(status,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chapters (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 story_id INT UNSIGNED NOT NULL,
 chapter_number INT UNSIGNED NOT NULL,
 title VARCHAR(220) NOT NULL,
 content LONGTEXT NOT NULL,
 audio_url VARCHAR(500) NULL,
 duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uq_story_chapter(story_id,chapter_number),
 KEY idx_chapter_status(story_id,status,chapter_number),
 CONSTRAINT fk_chapter_story FOREIGN KEY(story_id) REFERENCES stories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_favorites (
 member_id INT UNSIGNED NOT NULL,
 story_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(member_id,story_id),
 CONSTRAINT fk_story_fav_member FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE,
 CONSTRAINT fk_story_fav_story FOREIGN KEY(story_id) REFERENCES stories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_reading_history (
 member_id INT UNSIGNED NOT NULL,
 story_id INT UNSIGNED NOT NULL,
 chapter_id INT UNSIGNED NOT NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(member_id,story_id),
 KEY idx_read_history_updated(member_id,updated_at),
 CONSTRAINT fk_read_member FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE,
 CONSTRAINT fk_read_story FOREIGN KEY(story_id) REFERENCES stories(id) ON DELETE CASCADE,
 CONSTRAINT fk_read_chapter FOREIGN KEY(chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
