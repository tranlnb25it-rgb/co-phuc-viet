-- =============================================================
--  Phase 7a — order timeline (status/payment changes, internal notes, edits)
--  Safe to run on an existing database: only adds things, never drops data.
-- =============================================================
USE co_phuc_viet;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS order_events (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NULL,                     -- admin who did it; NULL = customer/system
  type       ENUM('created','status','payment','shipping','note','stock') NOT NULL,
  message    VARCHAR(1000) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_event_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE SET NULL,
  INDEX idx_event_order (order_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard queries filter by date
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_order_created (created_at);

-- Give orders placed before this migration a "created" entry
INSERT INTO order_events (order_id, type, message, created_at)
SELECT o.id, 'created',
       CONCAT('Khách đặt hàng — ', IF(o.payment_method = 'COD', 'thanh toán khi nhận hàng', 'chuyển khoản')),
       o.created_at
  FROM orders o
 WHERE NOT EXISTS (SELECT 1 FROM order_events e WHERE e.order_id = o.id);
