-- CHUAN HOA BANG THEO DOI: author_follows -> member_follows
-- Sao luu database truoc khi chay.
-- Chay sau khi code moi da duoc deploy.
-- Du lieu cu duoc copy sang bang chung, cap nhat theo cap follower/author.
INSERT IGNORE INTO member_follows (follower_id, following_id, created_at)
SELECT follower_member_id, author_member_id, created_at
FROM author_follows;

-- Chi xoa bang cu sau khi da copy du lieu thanh cong.
DROP TABLE author_follows;
