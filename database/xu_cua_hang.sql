-- He thong Xu va Cua hang - Goc Hoang Dao
-- Sao luu database truoc khi chay. Chay file nay mot lan.
CREATE TABLE IF NOT EXISTS member_wallets (
  member_id INT UNSIGNED NOT NULL,
  balance INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id),
  CONSTRAINT fk_wallet_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wallet_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  amount INT NOT NULL,
  type ENUM('daily','purchase','gift_sent','gift_received','admin','post_reward') NOT NULL,
  description VARCHAR(255) NOT NULL,
  reference_id BIGINT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_wallet_history (member_id, created_at),
  CONSTRAINT fk_wallet_tx_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wallet_daily_claims (
  member_id INT UNSIGNED NOT NULL,
  claim_date DATE NOT NULL,
  amount INT UNSIGNED NOT NULL DEFAULT 20,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id,claim_date),
  CONSTRAINT fk_daily_claim_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shop_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  item_type ENUM('title','frame','effect') NOT NULL,
  icon VARCHAR(30) NOT NULL DEFAULT '✦',
  price INT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_inventory (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  is_equipped TINYINT(1) NOT NULL DEFAULT 0,
  purchased_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_member_item (member_id,item_id),
  KEY idx_inventory_member (member_id,is_equipped),
  CONSTRAINT fk_inventory_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_inventory_item FOREIGN KEY (item_id) REFERENCES shop_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO shop_items (id,name,description,item_type,icon,price) VALUES
(1,'Người kể chuyện','Danh hiệu dành cho người yêu những câu chuyện dưới ánh sao.','title','📜',80),
(2,'Tâm hồn vũ trụ','Danh hiệu dành cho thành viên yêu thích chiêm tinh.','title','🌌',120),
(3,'Khung Ánh Trăng','Khung trang trí hồ sơ ánh trăng.','frame','🌙',150),
(4,'Khung Tinh Tú','Khung hồ sơ lấp lánh sắc sao.','frame','✨',200),
(5,'Hào quang Bọ Cạp','Hiệu ứng hồ sơ huyền bí.','effect','♏',250),
(6,'Hào quang Hoàng Đạo','Hiệu ứng ánh sao dành cho hồ sơ.','effect','💫',300);

INSERT IGNORE INTO member_wallets(member_id,balance)
SELECT id,0 FROM members;
