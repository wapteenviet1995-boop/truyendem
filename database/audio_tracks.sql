-- Chạy file này một lần trong phpMyAdmin để tạo danh sách audio riêng.
CREATE TABLE IF NOT EXISTS audio_tracks (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 story_id INT UNSIGNED NOT NULL,
 chapter_id INT UNSIGNED NULL,
 title VARCHAR(220) NOT NULL,
 audio_url VARCHAR(700) NOT NULL,
 source_type ENUM('upload','link') NOT NULL DEFAULT 'link',
 duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
 track_order INT UNSIGNED NOT NULL DEFAULT 0,
 status ENUM('draft','published') NOT NULL DEFAULT 'published',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 KEY idx_audio_story(story_id,status,track_order),
 KEY idx_audio_chapter(chapter_id),
 CONSTRAINT fk_audio_story FOREIGN KEY(story_id) REFERENCES stories(id) ON DELETE CASCADE,
 CONSTRAINT fk_audio_chapter FOREIGN KEY(chapter_id) REFERENCES chapters(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
