-- Upgrade an existing security_guard_system database to the secured version.
-- Run once in phpMyAdmin (SQL tab) or: mysql -u root < database/migrate.sql

USE security_guard_system;

-- "Remember me" tokens replace the old cookie that stored the raw user id.
CREATE TABLE IF NOT EXISTS remember_tokens (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    selector       CHAR(24) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expires_at     DATETIME NOT NULL,
    INDEX (user_id)
) ENGINE=InnoDB;

-- Clients can now cancel pending bookings. If your bookings.status column is an ENUM,
-- make sure it allows 'cancelled' and 'rejected' (no effect if it is VARCHAR):
-- ALTER TABLE bookings MODIFY status ENUM('pending','confirmed','rejected','cancelled') NOT NULL DEFAULT 'pending';

-- Old remember-me cookies (raw user ids) stop working automatically: users simply log in again.
