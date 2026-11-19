-- =============================================================
--  Phase 9 — customer accounts: address book, saved measurements, wishlist
--  Customers live in `users` with role = 'customer' (staff use the other roles).
--  Safe on an existing database: only adds tables/indexes, never drops data.
-- =============================================================
USE co_phuc_viet;
SET NAMES utf8mb4;

-- ---------- Address book ----------
CREATE TABLE IF NOT EXISTS customer_addresses (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Saved body measurements (for made-to-measure orders) ----------
CREATE TABLE IF NOT EXISTS customer_measurements (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  name       VARCHAR(60)  NOT NULL,                      -- "Của tôi", "Mẹ", "Con gái"…
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Wishlist ----------
CREATE TABLE IF NOT EXISTS wishlists (
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  CONSTRAINT fk_wish_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_wish_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_wish_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- "My orders" lists orders by account ----------
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_order_user (user_id, created_at);
