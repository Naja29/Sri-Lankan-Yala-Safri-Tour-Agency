--  YalaSafari — Complete Database Setup

-- Admins 
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50)  NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Packages 
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

-- Services 
CREATE TABLE IF NOT EXISTS `services` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(150) NOT NULL,
  `description` TEXT,
  `features`    TEXT,
  `image`       VARCHAR(255),
  `status`      ENUM('active','inactive') DEFAULT 'active',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gallery 
CREATE TABLE IF NOT EXISTS `gallery` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(150),
  `category`    VARCHAR(50),
  `description` TEXT,
  `image`       VARCHAR(255) NOT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages 
CREATE TABLE IF NOT EXISTS `messages` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `first_name`  VARCHAR(100) NOT NULL,
  `last_name`   VARCHAR(100) NOT NULL,
  `email`       VARCHAR(150) NOT NULL,
  `phone`       VARCHAR(30),
  `package`     VARCHAR(100),
  `safari_date` DATE,
  `message`     TEXT NOT NULL,
  `booking_ref` VARCHAR(20),
  `is_read`     TINYINT(1) DEFAULT 0,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings 
CREATE TABLE IF NOT EXISTS `settings` (
  `key`   VARCHAR(100) PRIMARY KEY,
  `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Business Hours 
CREATE TABLE IF NOT EXISTS `business_hours` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `day`        VARCHAR(20) NOT NULL,
  `open_time`  TIME DEFAULT '05:00:00',
  `close_time` TIME DEFAULT '18:00:00',
  `is_closed`  TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Business Hours 
INSERT INTO `business_hours` (`day`, `open_time`, `close_time`, `is_closed`) VALUES
('Monday',          '05:00:00', '18:00:00', 0),
('Tuesday',         '05:00:00', '18:00:00', 0),
('Wednesday',       '05:00:00', '18:00:00', 0),
('Thursday',        '05:00:00', '18:00:00', 0),
('Friday',          '05:00:00', '18:00:00', 0),
('Saturday',        '05:00:00', '18:00:00', 0),
('Sunday',          '05:00:00', '18:00:00', 0),
('Public Holidays', '05:00:00', '18:00:00', 0);

-- Default Admin Account 
INSERT INTO `admins` (`username`, `password`, `name`, `email`) VALUES (
  'admin',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Site Administrator',
  'admin@yalasafari.lk'
);

-- Sample Packages 
INSERT INTO `packages` (`name`, `category`, `duration`, `price`, `price_per`, `badge_label`, `description`, `features`, `status`) VALUES
('Morning Safari Adventure',     'Half Day',  '4-5 Hours',        3500,  'Per Person', 'Popular',    'Experience the magic of Yala at dawn with expert guides.',          'Professional Guide\n4x4 Safari Jeep\nPark Entry Fees\nRefreshments',                       'active'),
('Full Day Wildlife Experience', 'Full Day',  '9-10 Hours',       7500,  'Per Person', 'Best Value', 'A full day exploring every zone of Yala National Park.',            'Professional Guide\n4x4 Safari Jeep\nPark Entry Fees\nLunch Included\nRefreshments',  'active'),
('2-Day Safari Package',         'Multi Day', '2 Days / 1 Night', 22500, 'Per Group',  'Best Seller','The ultimate Yala experience with overnight stay and two safaris.', 'Professional Guide\n4x4 Safari Jeep\nPark Entry Fees\nAccommodation\nAll Meals',     'active');

-- Sample Services 
INSERT INTO `services` (`name`, `description`, `features`, `status`) VALUES
('Wildlife Safari Tours',  'Experience thrilling safari adventures through Yala National Park.',        'Morning & Evening Safaris\nFull Day Expeditions\nExpert Naturalist Guides\nComfortable Safari Vehicles', 'active'),
('Photography Tours',      'Specialized tours for wildlife photographers with prime location access.', 'Photography Expert Guides\nPrime Location Access\nExtended Wait Times\nPhoto Editing Tips',              'active'),
('Bird Watching Tours',    'Discover over 200 bird species with expert ornithologist guides.',         'Expert Ornithologist Guides\nHigh-Quality Binoculars\nBird Checklist Provided\nBest Birding Spots',     'active'),
('Private Safari Tours',   'Exclusive private tours tailored to your preferences.',                    'Private Safari Vehicle\nDedicated Guide\nFlexible Itinerary\nCustomized Experience',                   'active');

-- Hero Slides 
CREATE TABLE IF NOT EXISTS `hero_slides` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `image`      VARCHAR(255) NOT NULL,
  `sort_order` INT DEFAULT 0,
  `status`     ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default hero slides (use existing images) 
INSERT INTO `hero_slides` (`image`, `sort_order`, `status`) VALUES
('images/hero/hero-1.jpg', 1, 'active'),
('images/hero/hero-2.jpg', 2, 'active'),
('images/hero/hero-3.jpg', 3, 'active');

-- Testimonials 
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `location`   VARCHAR(100),
  `rating`     TINYINT DEFAULT 5,
  `message`    TEXT NOT NULL,
  `photo`      VARCHAR(255),
  `source`     ENUM('website','manual') DEFAULT 'website',
  `status`     ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Testimonials 
INSERT INTO `testimonials` (`name`, `location`, `rating`, `message`, `source`, `status`) VALUES
('Sarah Mitchell',  'United Kingdom', 5, 'Absolutely breathtaking experience! We spotted a leopard within the first hour. Our guide was incredibly knowledgeable and made the whole trip unforgettable.', 'manual', 'approved'),
('James Tanaka',    'Japan',          5, 'The best safari experience I have ever had. The team was professional, punctual and truly passionate about wildlife. Highly recommend the full day package!', 'manual', 'approved'),
('Priya Fernandez', 'Australia',      4, 'Amazing trip with wonderful guides. The sunrise safari was magical — seeing elephants at dawn was a memory I will cherish forever. Will definitely be back!', 'manual', 'approved');
