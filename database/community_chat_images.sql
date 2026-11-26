-- Truyện Đêm: bổ sung ảnh đính kèm cho tin nhắn phòng trò chuyện.
-- Chạy một lần trong phpMyAdmin.
ALTER TABLE community_chat_messages ADD COLUMN image_path VARCHAR(255) NULL AFTER message;
