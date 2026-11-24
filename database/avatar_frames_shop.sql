-- Mo ban 9 khung avatar trong cua hang Xu Truyen Dem.
-- Sao luu database truoc khi chay. Chay SAU database/avatar_frames.sql va database/avatar_frames_seed.sql.
ALTER TABLE shop_items
  MODIFY item_type ENUM('title','frame','effect','avatar_frame') NOT NULL,
  ADD COLUMN avatar_frame_id INT UNSIGNED NULL AFTER item_type,
  ADD UNIQUE KEY uq_shop_avatar_frame (avatar_frame_id);

INSERT INTO shop_items (name,description,item_type,avatar_frame_id,icon,price,is_active)
SELECT f.name,COALESCE(f.description,'Khung avatar trang tri ho so.'),'avatar_frame',f.id,'🐉',
       CASE f.rarity
         WHEN 'common' THEN 200
         WHEN 'rare' THEN 250
         WHEN 'epic' THEN 300
         WHEN 'legendary' THEN 400
         WHEN 'mythic' THEN 500
         ELSE 250
       END,1
FROM avatar_frames f
WHERE f.is_active=1
  AND NOT EXISTS (SELECT 1 FROM shop_items s WHERE s.avatar_frame_id=f.id);
