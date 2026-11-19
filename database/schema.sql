-- Security Provider: full database for a new install (MySQL / MariaDB, e.g. XAMPP).
-- Import in phpMyAdmin (Import tab) or run: mysql -u root < database/schema.sql
-- Already have the database from the earlier version? Run migrate.sql instead.

CREATE DATABASE IF NOT EXISTS security_guard_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE security_guard_system;

-- Login accounts for every role.
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,                -- password_hash() output
    role       ENUM('admin', 'guard', 'client') NOT NULL,
    recovery_hash VARCHAR(255) NULL,                 -- admins only: hash of the recovery key
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Client profiles.
CREATE TABLE IF NOT EXISTS clients (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL UNIQUE,
    full_name         VARCHAR(100) NOT NULL,
    phone             VARCHAR(15)  NOT NULL,
    address           VARCHAR(255) NULL,
    organization_type ENUM('home', 'industry', 'individual') NOT NULL DEFAULT 'individual',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Guard profiles; a guard must be approved by an admin before clients can see them.
CREATE TABLE IF NOT EXISTS guards (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL UNIQUE,
    full_name           VARCHAR(100) NOT NULL,
    phone               VARCHAR(15)  NOT NULL,
    location            VARCHAR(100) NOT NULL,
    experience          INT NOT NULL DEFAULT 0,
    skills              TEXT NULL,
    hourly_rate         DECIMAL(10,2) NOT NULL DEFAULT 0,
    specialties         VARCHAR(255) NULL,
    profile_image       VARCHAR(255) NULL,
    verification_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    availability_status VARCHAR(20) NOT NULL DEFAULT 'available',
    rating              DECIMAL(2,1) NULL,
    reviews_count       INT NOT NULL DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Bookings of guards by clients.
CREATE TABLE IF NOT EXISTS bookings (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    client_id    INT NOT NULL,
    guard_id     INT NOT NULL,
    booking_date DATETIME NOT NULL,
    hours        INT NOT NULL DEFAULT 1,
    status       ENUM('pending', 'confirmed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (guard_id)  REFERENCES guards(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- "Remember me" tokens: the cookie holds selector:validator; only a hash of the validator is stored.
CREATE TABLE IF NOT EXISTS remember_tokens (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    selector       CHAR(24) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expires_at     DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Failed "forgot password" identity checks, used to block repeated guessing.
CREATE TABLE IF NOT EXISTS password_reset_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(255) NOT NULL,
    ip           VARCHAR(45)  NOT NULL,
    attempted_at DATETIME NOT NULL,
    INDEX (email),
    INDEX (ip, attempted_at)
) ENGINE=InnoDB;

-- First admin: open Admin/admin_registration.php once. After the first admin exists,
-- only a logged-in admin can create more admins.
