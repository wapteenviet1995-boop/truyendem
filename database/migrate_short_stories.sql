-- Chay mot lan trong phpMyAdmin de them ho tro truyen ngan va truyen dai nhieu chuong.
ALTER TABLE stories
  ADD COLUMN story_kind ENUM('serial','short') NOT NULL DEFAULT 'serial' AFTER story_type,
  ADD COLUMN short_content LONGTEXT NULL AFTER description;
