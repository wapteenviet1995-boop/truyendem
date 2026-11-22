-- Truyện Đêm: lưu tiến độ đọc chương theo tài khoản để đồng bộ Tủ sách.
-- Chạy một lần trong phpMyAdmin trên database website đang sử dụng.
ALTER TABLE story_reading_history
 ADD COLUMN progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER chapter_id;
