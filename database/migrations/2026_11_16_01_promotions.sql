-- =============================================================
--  Phase 8 — promotions: scheduled sale prices + coupon codes
--  Safe on an existing database: only adds tables/columns, never drops data.
-- =============================================================
USE co_phuc_viet;
SET NAMES utf8mb4;

-- ---------- Sale price on the base price (fabric surcharge / tailoring fee still added on top) ----------
ALTER TABLE products ADD COLUMN IF NOT EXISTS sale_price     DECIMAL(12,0) NULL AFTER base_price;
ALTER TABLE products ADD COLUMN IF NOT EXISTS sale_starts_at DATETIME NULL AFTER sale_price;
ALTER TABLE products ADD COLUMN IF NOT EXISTS sale_ends_at   DATETIME NULL AFTER sale_starts_at;

-- ---------- Coupon codes ----------
CREATE TABLE IF NOT EXISTS coupons (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Orders remember the coupon and the discount ----------
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(40) NULL AFTER shipping_fee;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount    DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER coupon_code;
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_order_coupon (coupon_code, phone);

-- ---------- Price before the sale, so invoices can show the saving ----------
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS original_price DECIMAL(12,0) NULL AFTER unit_price;
