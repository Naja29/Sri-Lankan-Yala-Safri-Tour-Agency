--  YalaSafari Database Setup

-- Admin users table
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50)  NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `full_name`  VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contact form submissions
CREATE TABLE IF NOT EXISTS `messages` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `first_name`   VARCHAR(100) NOT NULL,
  `last_name`    VARCHAR(100) NOT NULL,
  `email`        VARCHAR(150) NOT NULL,
  `phone`        VARCHAR(30),
  `package`      VARCHAR(100),
  `safari_date`  DATE,
  `message`      TEXT NOT NULL,
  `is_read`      TINYINT(1) DEFAULT 0,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `first_name`   VARCHAR(100) NOT NULL,
  `last_name`    VARCHAR(100) NOT NULL,
  `email`        VARCHAR(150) NOT NULL,
  `phone`        VARCHAR(30)  NOT NULL,
  `package`      VARCHAR(150) NOT NULL,
  `safari_date`  DATE,
  `guests`       INT DEFAULT 1,
  `message`      TEXT,
  `status`       ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
  `is_read`      TINYINT(1) DEFAULT 0,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Packages table
CREATE TABLE IF NOT EXISTS `packages` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(150) NOT NULL,
  `category`    VARCHAR(50),
  `duration`    VARCHAR(50),
  `price`       DECIMAL(10,2) DEFAULT 0,
  `price_per`   VARCHAR(50)  DEFAULT 'Per Person',
  `badge_label` VARCHAR(50),
  `description` TEXT,
  `features`    TEXT,
  `image`       VARCHAR(255),
  `status`      ENUM('active','inactive') DEFAULT 'active',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Services table
CREATE TABLE IF NOT EXISTS `services` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(150) NOT NULL,
  `description` TEXT,
  `image`       VARCHAR(255),
  `status`      ENUM('active','inactive') DEFAULT 'active',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gallery table
CREATE TABLE IF NOT EXISTS `gallery` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(150),
  `description` VARCHAR(255),
  `image`       VARCHAR(255) NOT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample package data
INSERT INTO `packages` (`name`, `category`, `duration`, `price`, `status`) VALUES
('Morning Safari Adventure', 'Half Day', '4-5 Hours', 3500, 'active'),
('Full Day Wildlife Experience', 'Full Day', '9-10 Hours', 7500, 'active'),
('2-Day Safari Package', 'Multi Day', '2 Days / 1 Night', 22500, 'active');

-- Default credentials: username = admin | password = Admin@1234
INSERT INTO `admins` (`username`, `password`, `full_name`, `email`) VALUES (
  'admin',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Site Administrator',
  'admin@yalasafari.lk'
);
