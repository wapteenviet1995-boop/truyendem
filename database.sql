-- DATABASE BLOG GOC HOANG DAO
-- Chon dung database if0_43395839_blog trong phpMyAdmin truoc khi import.
-- Bo qua dong CREATE DATABASE vi InfinityFree da tao san database.

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE TABLE IF NOT EXISTS blog_admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(120) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'admin',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_admin_username (username),
  UNIQUE KEY uq_blog_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  description TEXT DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_category_name (name),
  UNIQUE KEY uq_blog_category_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(220) NOT NULL,
  slug VARCHAR(240) NOT NULL,
  excerpt TEXT DEFAULT NULL,
  content MEDIUMTEXT NOT NULL,
  image_url VARCHAR(500) DEFAULT NULL,
  category VARCHAR(100) NOT NULL DEFAULT 'Góc Hoàng Đạo',
  category_id INT UNSIGNED DEFAULT NULL,
  author_id INT UNSIGNED DEFAULT NULL,
  status ENUM('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft',
  meta_title VARCHAR(220) DEFAULT NULL,
  meta_description VARCHAR(320) DEFAULT NULL,
  view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  published_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_posts_slug (slug),
  KEY idx_posts_status_date (status, published_at),
  KEY idx_posts_category (category_id),
  KEY idx_posts_author (author_id),
  CONSTRAINT fk_posts_category FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES blog_admins(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_comments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id INT UNSIGNED NOT NULL,
  display_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) DEFAULT NULL,
  comment TEXT NOT NULL,
  status ENUM('pending','approved','spam','rejected') NOT NULL DEFAULT 'pending',
  ip_hash CHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comments_post_status (post_id, status, created_at),
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO blog_categories (name, slug, description, sort_order) VALUES
('12 cung hoàng đạo','12-cung-hoang-dao','Thông tin và khám phá về 12 cung hoàng đạo',1),
('Tình yêu','tinh-yeu','Góc nhìn về tình yêu và các mối quan hệ',2),
('Tử vi hàng ngày','tu-vi-hang-ngay','Nội dung tử vi hàng ngày',3),
('Khám phá bản thân','kham-pha-ban-than','Tính cách và phát triển bản thân',4),
('Kiến thức chiêm tinh','kien-thuc-chiem-tinh','Kiến thức chiêm tinh cơ bản',5);

INSERT IGNORE INTO blog_settings (setting_key, setting_value) VALUES
('site_name','Góc Hoàng Đạo'),
('site_description','Khám phá câu chuyện của các vì sao'),
('posts_per_page','10'),
('comments_enabled','0');
