-- =============================================================
--  Cổ Phục Việt — database schema (MySQL / MariaDB, XAMPP)
--  Re-runnable: drops and recreates all tables in co_phuc_viet.
-- =============================================================
CREATE DATABASE IF NOT EXISTS co_phuc_viet
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE co_phuc_viet;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS coupons, activity_log, stock_movements, settings, order_events, order_items, orders, product_variants, product_images,
                     products, fabrics, dynasties, categories, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Users ----------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone         VARCHAR(20)  NULL,
  address       VARCHAR(255) NULL,
  role          ENUM('customer','admin','sales','warehouse') NOT NULL DEFAULT 'customer',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Categories (garment types + accessories, 1 level of nesting) ----------
CREATE TABLE categories (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id      INT UNSIGNED NULL,
  slug           VARCHAR(120) NOT NULL UNIQUE,
  name_vi        VARCHAR(120) NOT NULL,
  name_en        VARCHAR(120) NOT NULL,
  description_vi TEXT NULL,
  description_en TEXT NULL,
  image_url      VARCHAR(255) NULL,
  sort_order     INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Dynasties / eras ----------
CREATE TABLE dynasties (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug       VARCHAR(60)  NOT NULL UNIQUE,
  name_vi    VARCHAR(80)  NOT NULL,
  name_en    VARCHAR(80)  NOT NULL,
  period     VARCHAR(40)  NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------- Fabrics / materials ----------
CREATE TABLE fabrics (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug           VARCHAR(60) NOT NULL UNIQUE,
  name_vi        VARCHAR(80) NOT NULL,
  name_en        VARCHAR(80) NOT NULL,
  price_modifier DECIMAL(12,0) NOT NULL DEFAULT 0   -- VND added to base price
) ENGINE=InnoDB;

-- ---------- Products ----------
CREATE TABLE products (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id            INT UNSIGNED NOT NULL,
  dynasty_id             INT UNSIGNED NULL,
  slug                   VARCHAR(160) NOT NULL UNIQUE,
  name_vi                VARCHAR(160) NOT NULL,
  name_en                VARCHAR(160) NOT NULL,
  short_description_vi   VARCHAR(500) NULL,
  short_description_en   VARCHAR(500) NULL,
  historical_context_vi  TEXT NULL,
  historical_context_en  TEXT NULL,
  base_price             DECIMAL(12,0) NOT NULL,
  sale_price             DECIMAL(12,0) NULL,
  sale_starts_at         DATETIME NULL,
  sale_ends_at           DATETIME NULL,
  gender                 ENUM('nam','nu','unisex') NOT NULL DEFAULT 'unisex',
  allow_custom_tailoring TINYINT(1) NOT NULL DEFAULT 1,
  custom_tailoring_fee   DECIMAL(12,0) NOT NULL DEFAULT 0,   -- VND surcharge for made-to-measure
  is_featured            TINYINT(1) NOT NULL DEFAULT 0,
  rating_avg             DECIMAL(3,2) NOT NULL DEFAULT 0,   -- cached from approved reviews
  rating_count           INT UNSIGNED NOT NULL DEFAULT 0,
  is_active              TINYINT(1) NOT NULL DEFAULT 1,
  created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_prod_cat     FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_prod_dynasty FOREIGN KEY (dynasty_id)  REFERENCES dynasties(id) ON DELETE SET NULL,
  INDEX idx_prod_filters (is_active, category_id, dynasty_id, gender, base_price)
) ENGINE=InnoDB;

-- ---------- Product images (gallery) ----------
CREATE TABLE product_images (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  url        VARCHAR(255) NOT NULL,
  view_type  ENUM('front','back','detail') NOT NULL DEFAULT 'front',
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_img_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Variants: stock per size × fabric × color ----------
-- Custom-tailored orders do NOT use a variant (size = 'CUSTOM' on order_items).
CREATE TABLE product_variants (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  size       ENUM('S','M','L','XL','FREE') NOT NULL,   -- FREE = one-size accessories
  fabric_id  INT UNSIGNED NULL,
  color_vi   VARCHAR(60) NOT NULL,
  color_en   VARCHAR(60) NOT NULL,
  color_hex  CHAR(7)     NOT NULL,
  stock      INT NOT NULL DEFAULT 0,
  sku        VARCHAR(80) NOT NULL UNIQUE,
  CONSTRAINT fk_var_prod   FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_var_fabric FOREIGN KEY (fabric_id)  REFERENCES fabrics(id)  ON DELETE SET NULL,
  UNIQUE KEY uq_variant (product_id, size, fabric_id, color_hex)
) ENGINE=InnoDB;

-- ---------- Orders (user_id NULL = guest checkout) ----------
CREATE TABLE orders (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED NULL,
  order_code       VARCHAR(20)  NOT NULL UNIQUE,
  customer_name    VARCHAR(120) NOT NULL,
  phone            VARCHAR(20)  NOT NULL,
  email            VARCHAR(190) NULL,
  shipping_address VARCHAR(255) NOT NULL,
  note             VARCHAR(500) NULL,
  payment_method   ENUM('COD','BANK_TRANSFER') NOT NULL,
  payment_status   ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  status           ENUM('pending','confirmed','tailoring','shipping','completed','cancelled')
                   NOT NULL DEFAULT 'pending',
  subtotal         DECIMAL(12,0) NOT NULL,
  shipping_fee     DECIMAL(12,0) NOT NULL DEFAULT 0,
  coupon_code      VARCHAR(40)  NULL,
  discount         DECIMAL(12,0) NOT NULL DEFAULT 0,
  total            DECIMAL(12,0) NOT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_order_status (status, created_at),
  INDEX idx_order_created (created_at),
  INDEX idx_order_coupon (coupon_code, phone),
  INDEX idx_order_user (user_id, created_at)
) ENGINE=InnoDB;

-- ---------- Order items (snapshots so later product edits don't alter history) ----------
CREATE TABLE order_items (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id            INT UNSIGNED NOT NULL,
  product_id          INT UNSIGNED NULL,
  variant_id          INT UNSIGNED NULL,
  product_name        VARCHAR(160) NOT NULL,
  size                ENUM('S','M','L','XL','FREE','CUSTOM') NOT NULL,
  fabric_name         VARCHAR(80) NULL,
  color               VARCHAR(60) NULL,
  unit_price          DECIMAL(12,0) NOT NULL,
  original_price      DECIMAL(12,0) NULL,
  quantity            INT UNSIGNED NOT NULL,
  custom_measurements JSON NULL,   -- {"height":160,"weight":50,"bust":84,"waist":64,"hip":90}
  CONSTRAINT fk_item_order   FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  CONSTRAINT fk_item_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Order timeline: status/payment changes, internal notes, edits ----------
CREATE TABLE order_events (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NULL,                     -- admin who did it; NULL = customer/system
  type       ENUM('created','status','payment','shipping','note','stock') NOT NULL,
  message    VARCHAR(1000) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_event_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE SET NULL,
  INDEX idx_event_order (order_id, created_at)
) ENGINE=InnoDB;

-- ---------- Store settings (key → JSON value), edited in /admin/cai-dat ----------
CREATE TABLE settings (
  `key`      VARCHAR(64) PRIMARY KEY,
  value      TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Every stock change, with the reason and who made it ----------
CREATE TABLE stock_movements (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  variant_id   INT UNSIGNED NULL,
  product_id   INT UNSIGNED NULL,
  sku          VARCHAR(80)  NOT NULL,          -- snapshots: history survives deletes
  product_name VARCHAR(160) NOT NULL,
  variant_label VARCHAR(160) NOT NULL DEFAULT '',
  qty_change   INT NOT NULL,
  stock_after  INT NOT NULL,
  reason       ENUM('initial','import','adjust','damage','order','cancel') NOT NULL,
  note         VARCHAR(255) NULL,
  order_id     INT UNSIGNED NULL,
  user_id      INT UNSIGNED NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mv_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
  CONSTRAINT fk_mv_product FOREIGN KEY (product_id) REFERENCES products(id)        ON DELETE SET NULL,
  CONSTRAINT fk_mv_order   FOREIGN KEY (order_id)   REFERENCES orders(id)          ON DELETE SET NULL,
  CONSTRAINT fk_mv_user    FOREIGN KEY (user_id)    REFERENCES users(id)           ON DELETE SET NULL,
  INDEX idx_mv_created (created_at),
  INDEX idx_mv_variant (variant_id, created_at)
) ENGINE=InnoDB;

-- ---------- Staff activity log ----------
CREATE TABLE activity_log (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  user_name  VARCHAR(120) NOT NULL,
  action     VARCHAR(40)  NOT NULL,           -- e.g. product.update, order.status, login
  entity     VARCHAR(40)  NULL,
  entity_id  VARCHAR(40)  NULL,
  message    VARCHAR(500) NOT NULL,
  ip         VARCHAR(45)  NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_log_created (created_at),
  INDEX idx_log_user (user_id, created_at)
) ENGINE=InnoDB;

-- ---------- Coupon codes ----------
CREATE TABLE coupons (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code            VARCHAR(40)  NOT NULL UNIQUE,          -- stored upper-case
  description     VARCHAR(255) NULL,                     -- shown to the customer when applied
  type            ENUM('percent','fixed','free_shipping') NOT NULL,
  value           DECIMAL(12,0) NOT NULL DEFAULT 0,      -- % (1–100) or VND
  max_discount    DECIMAL(12,0) NULL,                    -- cap for percent coupons
  min_order       DECIMAL(12,0) NOT NULL DEFAULT 0,      -- on the items subtotal
  starts_at       DATETIME NULL,
  ends_at         DATETIME NULL,
  usage_limit     INT UNSIGNED NULL,                     -- total uses, NULL = unlimited
  per_phone_limit INT UNSIGNED NULL,                     -- uses per customer phone, NULL = unlimited
  used_count      INT UNSIGNED NOT NULL DEFAULT 0,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Customer address book ----------
CREATE TABLE customer_addresses (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  label          VARCHAR(40)  NULL,                      -- "Nhà riêng", "Công ty"…
  recipient_name VARCHAR(120) NOT NULL,
  phone          VARCHAR(20)  NOT NULL,
  address        VARCHAR(180) NOT NULL,                  -- street, ward, district
  city           VARCHAR(70)  NOT NULL,
  is_default     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_addr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_addr_user (user_id, is_default)
) ENGINE=InnoDB;

-- ---------- Saved body measurements (made-to-measure) ----------
CREATE TABLE customer_measurements (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  name       VARCHAR(60)  NOT NULL,                      -- "Của tôi", "Mẹ"…
  height     DECIMAL(5,1) NOT NULL,
  weight     DECIMAL(5,1) NOT NULL,
  bust       DECIMAL(5,1) NOT NULL,
  waist      DECIMAL(5,1) NOT NULL,
  hip        DECIMAL(5,1) NOT NULL,
  note       VARCHAR(300) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_meas_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_meas_user (user_id)
) ENGINE=InnoDB;

-- ---------- Wishlist ----------
CREATE TABLE wishlists (
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  CONSTRAINT fk_wish_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_wish_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_wish_product (product_id)
) ENGINE=InnoDB;

-- ---------- Product reviews (verified purchases) ----------
CREATE TABLE product_reviews (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id    INT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NULL,
  order_id      INT UNSIGNED NULL,                    -- completed order that proves the purchase
  author_name   VARCHAR(120) NOT NULL,
  rating        TINYINT UNSIGNED NOT NULL,            -- 1..5
  title         VARCHAR(120) NULL,
  body          TEXT NOT NULL,
  variant_label VARCHAR(160) NULL,                    -- "Gấm · Đỏ son · Size M" from the order line
  fit           ENUM('small','true','large') NULL,    -- size feedback
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  helpful_count INT UNSIGNED NOT NULL DEFAULT 0,
  reply         TEXT NULL,                            -- public answer from the shop
  replied_at    DATETIME NULL,
  replied_by    INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review_user_product (user_id, product_id),
  CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_review_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE SET NULL,
  CONSTRAINT fk_review_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE SET NULL,
  CONSTRAINT fk_review_replier FOREIGN KEY (replied_by) REFERENCES users(id)    ON DELETE SET NULL,
  INDEX idx_review_product (product_id, status, created_at),
  INDEX idx_review_status (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE review_images (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  review_id  INT UNSIGNED NOT NULL,
  url        VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_rimg_review FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
  INDEX idx_rimg_review (review_id, sort_order)
) ENGINE=InnoDB;

-- one "helpful" vote per voter (u:<user id> or s:<session hash>)
CREATE TABLE review_votes (
  review_id  INT UNSIGNED NOT NULL,
  voter      VARCHAR(48) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id, voter),
  CONSTRAINT fk_vote_review FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB;
