-- Chạy một lần trong phpMyAdmin trên đúng database Truyện Đêm.
-- Bình luận gắn với truyện; parent_id NULL là bình luận gốc, có giá trị là câu trả lời.
CREATE TABLE IF NOT EXISTS story_comments (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 story_id INT UNSIGNED NOT NULL,
 member_id INT UNSIGNED NOT NULL,
 parent_id INT UNSIGNED DEFAULT NULL,
 content TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 KEY idx_story_comments_story (story_id, created_at),
 KEY idx_story_comments_parent (parent_id),
 KEY idx_story_comments_member (member_id),
 CONSTRAINT fk_story_comment_story FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
 CONSTRAINT fk_story_comment_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
 CONSTRAINT fk_story_comment_parent FOREIGN KEY (parent_id) REFERENCES story_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_comment_likes (
 member_id INT UNSIGNED NOT NULL,
 comment_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (member_id, comment_id),
 KEY idx_story_comment_likes_comment (comment_id),
 CONSTRAINT fk_story_comment_like_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
 CONSTRAINT fk_story_comment_like_comment FOREIGN KEY (comment_id) REFERENCES story_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
