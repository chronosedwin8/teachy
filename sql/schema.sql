-- Esquema de base de datos del portal de licencias
CREATE DATABASE IF NOT EXISTS teachy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE teachy_db;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(40)  NULL,
    institution   VARCHAR(190) NULL,
    tax_id        VARCHAR(40)  NULL,
    city          VARCHAR(120) NULL,
    is_admin      TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id            INT UNSIGNED NOT NULL,
    plan_code          VARCHAR(40) NOT NULL,
    amount             DECIMAL(14,2) NOT NULL,
    currency           CHAR(3) NOT NULL DEFAULT 'COP',
    status             ENUM('pending','in_process','approved','rejected','cancelled','refunded') NOT NULL DEFAULT 'pending',
    external_reference VARCHAR(64) NOT NULL UNIQUE,
    mp_preference_id   VARCHAR(120) NULL,
    mp_payment_id      VARCHAR(60)  NULL,
    mp_status_detail   VARCHAR(120) NULL,
    payment_method     VARCHAR(60)  NULL,
    payment_type       VARCHAR(40)  NULL,
    mp_resource_url    VARCHAR(600) NULL,
    mp_expires_at      DATETIME     NULL,
    is_demo            TINYINT(1) NOT NULL DEFAULT 0,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at            DATETIME NULL,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_orders_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS licenses (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    order_id    INT UNSIGNED NOT NULL UNIQUE,
    plan_code   VARCHAR(40) NOT NULL,
    license_key VARCHAR(40) NOT NULL UNIQUE,
    seats       INT UNSIGNED NOT NULL,
    campuses    INT UNSIGNED NOT NULL DEFAULT 1,
    status      ENUM('active','suspended','expired') NOT NULL DEFAULT 'active',
    starts_at   DATETIME NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_licenses_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_licenses_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS license_members (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    role        VARCHAR(40)  NOT NULL DEFAULT 'docente',
    campus      VARCHAR(120) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_members_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    UNIQUE KEY uq_member (license_id, email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payment_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source     VARCHAR(30) NOT NULL,
    payload    MEDIUMTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plan_settings (
    code       VARCHAR(40) PRIMARY KEY,
    data       JSON NOT NULL,
    updated_by VARCHAR(190) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- La cuenta de administrador se crea con install.php a partir de ADMIN_EMAIL y
-- ADMIN_PASSWORD_HASH definidos en config.secrets.php (nunca se guardan en el repositorio).
