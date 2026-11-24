-- Nap 9 mau khung rong vao danh muc avatar.
-- Chay file nay SAU database/avatar_frames.sql trong phpMyAdmin.
-- Dung duong dan tu goc website de hien thi on dinh.
INSERT INTO avatar_frames (name, image_url, rarity, description, sort_order)
SELECT 'Rồng Tím', '/uploads/avatar-frames/khung-rong-1-tim-rong.png', 'epic', 'Khung rồng sắc tím huyền bí.', 1
WHERE NOT EXISTS (SELECT 1 FROM avatar_frames WHERE image_url='/uploads/avatar-frames/khung-rong-1-tim-rong.png');

INSERT INTO avatar_frames (name, image_url, rarity, description, sort_order)
SELECT 'Rồng Lửa', '/uploads/avatar-frames/khung-rong-2-lua-rong.png', 'legendary', 'Khung rồng mang sức mạnh lửa.', 2
WHERE NOT EXISTS (SELECT 1 FROM avatar_frames WHERE image_url='/uploads/avatar-frames/khung-rong-2-lua-rong.png');

INSERT INTO avatar_frames (name, image_url, rarity, description, sort_order)
SELECT 'Rồng Băng', '/uploads/avatar-frames/khung-rong-3-bang-rong.png', 'legendary', 'Khung rồng băng giá.', 3
WHERE NOT EXISTS (SELECT 1 FROM avatar_frames WHERE image_url='/uploads/avatar-frames/khung-rong-3-bang-rong.png');

INSERT INTO avatar_frames (name, image_url, rarity, description, sort_order)
SELECT 'Rồng Lá', '/uploads/avatar-frames/khung-rong-4-la-rong.png', 'rare', 'Khung rồng mang sắc xanh thiên nhiên.', 4
WHERE NOT EXISTS (SELECT 1 FROM avatar_frames WHERE image_url='/uploads/avatar-frames/khung-rong-4-la-rong.png');

INSERT INTO avatar_frames (name, image_url, rarity, description, sort_order)
SELECT 'Rồng Cánh', '/uploads/avatar-frames/khung-rong-5-canh-rong.png', 'epic', 'Khung rồng với đôi cánh nổi bật.', 5
WHERE NOT EXISTS (SELECT 1 FROM avatar_frames WHERE image_url='/uploads/avatar-frames/khung-rong-5-canh-rong.png');

INSERT INTO avatar_frames (name, image_url, rarity, description, sort_order)
SELECT 'Rồng Âm', '/uploads/avatar-frames/khung-rong-6-am-rong.png', 'epic', 'Khung rồng phong cách huyền bí.', 6
WHERE NOT EXISTS (SELECT 1 FROM avatar_frames WHERE image_url='/uploads/avatar-frames/khung-rong-6-am-rong.png');

INSERT INTO avatar_frames (name, image_url, rarity, description, sort_order)
SELECT 'Rồng Sét', '/uploads/avatar-frames/khung-rong-7-set-rong.png', 'legendary', 'Khung rồng mang sức mạnh sấm sét.', 7
WHERE NOT EXISTS (SELECT 1 FROM avatar_frames WHERE image_url='/uploads/avatar-frames/khung-rong-7-set-rong.png');

INSERT INTO avatar_frames (name, image_url, rarity, description, sort_order)
SELECT 'Rồng Nước', '/uploads/avatar-frames/khung-rong-8-nuoc-rong.png', 'rare', 'Khung rồng mang sắc nước.', 8
WHERE NOT EXISTS (SELECT 1 FROM avatar_frames WHERE image_url='/uploads/avatar-frames/khung-rong-8-nuoc-rong.png');

INSERT INTO avatar_frames (name, image_url, rarity, description, sort_order)
SELECT 'Rồng Lửa Băng', '/uploads/avatar-frames/khung-rong-9-lua-bang.png', 'mythic', 'Khung rồng kết hợp hai sức mạnh đối lập.', 9
WHERE NOT EXISTS (SELECT 1 FROM avatar_frames WHERE image_url='/uploads/avatar-frames/khung-rong-9-lua-bang.png');
