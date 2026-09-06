USE movie_ticket_booking;

-- Nếu database hiện tại đã có vouchers/voucher_usages, không chạy lại phần CREATE.
CREATE TABLE IF NOT EXISTS vouchers
(
    id                INT PRIMARY KEY AUTO_INCREMENT,
    code              VARCHAR(50) NOT NULL UNIQUE,
    discount_amount   DECIMAL(10,2) NOT NULL,
    min_order_amount  DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_quantity    INT NOT NULL DEFAULT 1,
    used_quantity     INT NOT NULL DEFAULT 0,
    per_user_limit    INT NOT NULL DEFAULT 1,
    valid_from        DATETIME NULL,
    expires_at        DATETIME NULL,
    status            ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS voucher_usages
(
    id               INT PRIMARY KEY AUTO_INCREMENT,
    voucher_id       INT NOT NULL,
    user_id          INT NOT NULL,
    booking_id       INT NULL,
    discount_amount  DECIMAL(10,2) NOT NULL DEFAULT 0,
    used_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_voucher_user (voucher_id, user_id),
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

INSERT INTO vouchers (code, discount_amount, min_order_amount, total_quantity, used_quantity, per_user_limit, status)
SELECT 'VIP1', 20000, 80000, 10, 0, 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM vouchers WHERE code = 'VIP1');
