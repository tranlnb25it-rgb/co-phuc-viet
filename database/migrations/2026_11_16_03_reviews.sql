-- =============================================================
--  Phase 10 — product reviews (verified purchases only), photos, "helpful" votes
--  Safe on an existing database: only adds tables/columns, never drops data.
-- =============================================================
USE co_phuc_viet;
SET NAMES utf8mb4;

-- ---------- Cached rating on the product (recomputed whenever a review is approved / hidden / deleted) ----------
ALTER TABLE products ADD COLUMN IF NOT EXISTS rating_avg   DECIMAL(3,2) NOT NULL DEFAULT 0 AFTER is_featured;
ALTER TABLE products ADD COLUMN IF NOT EXISTS rating_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER rating_avg;

-- ---------- Reviews ----------
CREATE TABLE IF NOT EXISTS product_reviews (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS review_images (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  review_id  INT UNSIGNED NOT NULL,
  url        VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_rimg_review FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
  INDEX idx_rimg_review (review_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- one "helpful" vote per voter (u:<user id> or s:<session hash>)
CREATE TABLE IF NOT EXISTS review_votes (
  review_id  INT UNSIGNED NOT NULL,
  voter      VARCHAR(48) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id, voter),
  CONSTRAINT fk_vote_review FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
