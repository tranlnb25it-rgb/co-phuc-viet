-- =============================================================
--  Phase 7b — settings, stock movements, activity log, staff roles
--  Safe on an existing database: only adds tables/columns, never drops data.
-- =============================================================
USE co_phuc_viet;
SET NAMES utf8mb4;

-- ---------- Staff roles ----------
ALTER TABLE users MODIFY role ENUM('customer','admin','sales','warehouse') NOT NULL DEFAULT 'customer';
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER is_active;

-- ---------- Store settings (key → JSON value), edited in /admin/cai-dat ----------
CREATE TABLE IF NOT EXISTS settings (
  `key`      VARCHAR(64) PRIMARY KEY,
  value      TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Every stock change, with the reason and who made it ----------
CREATE TABLE IF NOT EXISTS stock_movements (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Staff activity log ----------
CREATE TABLE IF NOT EXISTS activity_log (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opening balance so the stock history starts from today's numbers
INSERT INTO stock_movements (variant_id, product_id, sku, product_name, variant_label, qty_change, stock_after, reason, note)
SELECT v.id, v.product_id, v.sku, p.name_vi,
       CONCAT_WS(' · ', f.name_vi, v.color_vi, IF(v.size = 'FREE', NULL, CONCAT('Size ', v.size))),
       v.stock, v.stock, 'initial', 'Số dư đầu kỳ'
  FROM product_variants v
  JOIN products p ON p.id = v.product_id
  LEFT JOIN fabrics f ON f.id = v.fabric_id
 WHERE NOT EXISTS (SELECT 1 FROM stock_movements m WHERE m.variant_id = v.id);
