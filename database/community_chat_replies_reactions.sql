-- Truyện Đêm: bổ sung trả lời tin nhắn và thả tim.
-- Chạy một lần trong phpMyAdmin sau khi sao lưu database.
ALTER TABLE community_chat_messages
  ADD COLUMN reply_to_id BIGINT UNSIGNED NULL AFTER message,
  ADD KEY idx_chat_reply_to (reply_to_id),
  ADD CONSTRAINT fk_chat_reply_message
    FOREIGN KEY (reply_to_id) REFERENCES community_chat_messages(id)
    ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS community_chat_reactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id BIGINT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  reaction VARCHAR(20) NOT NULL DEFAULT '❤️',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_chat_reaction_member (message_id, member_id),
  KEY idx_chat_reaction_message (message_id),
  CONSTRAINT fk_chat_reaction_message
    FOREIGN KEY (message_id) REFERENCES community_chat_messages(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_chat_reaction_member
    FOREIGN KEY (member_id) REFERENCES members(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
