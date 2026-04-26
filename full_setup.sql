-- ============================================================
--  ROYALE VISTA v2 — Complete Database Setup (FIXED)
--  Run in phpMyAdmin: Create DB "royalevista" → Import this file
--  Login:  admin@royalevista.com / password
--  Guest:  john@example.com / password
-- ============================================================
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS=0;



-- ── Drop all tables in dependency order ────────────────────
DROP TABLE IF EXISTS `loyalty_transactions`;
DROP TABLE IF EXISTS `loyalty_points`;
DROP TABLE IF EXISTS `payment_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `review_media`;
DROP TABLE IF EXISTS `review_images`;
DROP TABLE IF EXISTS `room_ratings`;
DROP TABLE IF EXISTS `wishlists`;
DROP TABLE IF EXISTS `room_assignments`;
DROP TABLE IF EXISTS `booked_rooms`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `price_overrides`;
DROP TABLE IF EXISTS `room_availability`;
DROP TABLE IF EXISTS `room_facilities`;
DROP TABLE IF EXISTS `room_images`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `room_types`;
DROP TABLE IF EXISTS `user_memberships`;
DROP TABLE IF EXISTS `membership_features`;
DROP TABLE IF EXISTS `memberships`;
DROP TABLE IF EXISTS `offers`;
DROP TABLE IF EXISTS `newsletter_subscribers`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `chat_sessions`;
DROP TABLE IF EXISTS `room_cart`;
DROP TABLE IF EXISTS `gallery_images`;
DROP TABLE IF EXISTS `concierge_requests`;
DROP TABLE IF EXISTS `gift_card_usage`;
DROP TABLE IF EXISTS `gift_cards`;
DROP TABLE IF EXISTS `dining_reservations`;
DROP TABLE IF EXISTS `spa_appointments`;
DROP TABLE IF EXISTS `event_bookings`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `virtual_tours`;
DROP TABLE IF EXISTS `property_amenities`;
DROP TABLE IF EXISTS `property_images`;
DROP TABLE IF EXISTS `properties`;
DROP TABLE IF EXISTS `hotel_properties`;
DROP TABLE IF EXISTS `experiences`;
DROP TABLE IF EXISTS `services_catalog`;
DROP TABLE IF EXISTS `users`;

-- ── users ──────────────────────────────────────────────────
CREATE TABLE `users` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `name`          varchar(200) NOT NULL,
  `email`         varchar(191) UNIQUE NOT NULL,
  `password`      varchar(255) NOT NULL,
  `phone`         varchar(30) DEFAULT NULL,
  `address`       text DEFAULT NULL,
  `profile_img`   varchar(500) DEFAULT NULL,
  `id_type`       varchar(50) DEFAULT 'passport',
  `id_number`     varchar(100) DEFAULT NULL,
  `dob`           date DEFAULT NULL,
  `country`       varchar(100) DEFAULT NULL,
  `gender`        varchar(20) DEFAULT NULL,
  `language`      varchar(10) DEFAULT 'en',
  `currency`      varchar(10) DEFAULT 'USD',
  `role`          varchar(20) DEFAULT 'user',
  `is_admin`      tinyint(1) DEFAULT 0,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── room_types ──────────────────────────────────────────────
CREATE TABLE `room_types` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `name`          varchar(100) UNIQUE NOT NULL,
  `slug`          varchar(100) UNIQUE NOT NULL,
  `description`   text DEFAULT NULL,
  `price_usd`     decimal(10,2) NOT NULL,
  `max_guests`    int DEFAULT 2,
  `has_breakfast` tinyint(1) DEFAULT 0,
  `avg_rating`    decimal(3,2) DEFAULT 4.50,
  `review_count`  int DEFAULT 0,
  `sort_order`    int DEFAULT 0,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── rooms ───────────────────────────────────────────────────
CREATE TABLE `rooms` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `room_number`   varchar(20) UNIQUE NOT NULL,
  `room_type_id`  int NOT NULL,
  `floor`         int DEFAULT 1,
  `view_type`     varchar(80) DEFAULT NULL,
  `status`        enum('available','occupied','maintenance') DEFAULT 'available',
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── room_images ─────────────────────────────────────────────
CREATE TABLE `room_images` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `room_type_id`  int NOT NULL,
  `image_url`     varchar(600) NOT NULL,
  `is_primary`    tinyint(1) DEFAULT 0,
  `sort_order`    int DEFAULT 0,
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── room_facilities ─────────────────────────────────────────
CREATE TABLE `room_facilities` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `room_type_id`  int NOT NULL,
  `name`          varchar(100) NOT NULL,
  `icon`          varchar(60) DEFAULT NULL,
  `sort_order`    int DEFAULT 0,
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── memberships ─────────────────────────────────────────────
CREATE TABLE `memberships` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `name`          varchar(100) NOT NULL,
  `price_usd`     decimal(10,2) NOT NULL,
  `discount_pct`  int DEFAULT 0,
  `duration_days` int DEFAULT 365,
  `description`   text DEFAULT NULL,
  `badge_text`    varchar(80) DEFAULT NULL,
  `badge_color`   varchar(20) DEFAULT '#d4af37',
  `gradient_from` varchar(7) DEFAULT '#d4af37',
  `gradient_to`   varchar(7) DEFAULT '#b89628',
  `icon`          varchar(10) DEFAULT '⭐',
  `is_popular`    tinyint(1) DEFAULT 0,
  `sort_order`    int DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── membership_features ─────────────────────────────────────
CREATE TABLE `membership_features` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `membership_id` int NOT NULL,
  `feature`       varchar(200) NOT NULL,
  `icon`          varchar(10) DEFAULT NULL,
  `is_highlight`  tinyint(1) DEFAULT 0,
  `sort_order`    int DEFAULT 0,
  FOREIGN KEY (`membership_id`) REFERENCES `memberships`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── user_memberships ────────────────────────────────────────
CREATE TABLE `user_memberships` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `user_id`       int NOT NULL,
  `membership_id` int NOT NULL,
  `member_number` varchar(50) UNIQUE NOT NULL,
  `expires_at`    timestamp NULL DEFAULT NULL,
  `status`        enum('active','expired','cancelled') DEFAULT 'active',
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`membership_id`) REFERENCES `memberships`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── offers ──────────────────────────────────────────────────
CREATE TABLE `offers` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `code`          varchar(50) UNIQUE NOT NULL,
  `type`          enum('percent','fixed') DEFAULT 'percent',
  `value`         decimal(10,2) NOT NULL,
  `description`   text DEFAULT NULL,
  `valid_from`    date DEFAULT NULL,
  `valid_to`      date DEFAULT NULL,
  `is_active`     tinyint(1) DEFAULT 1,
  `uses_max`      int DEFAULT NULL,
  `uses_count`    int DEFAULT 0,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── bookings ────────────────────────────────────────────────
CREATE TABLE `bookings` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `booking_ref`   varchar(30) UNIQUE NOT NULL,
  `invoice_no`    varchar(30) DEFAULT NULL,
  `user_id`       int DEFAULT NULL,
  `guest_name`    varchar(200) NOT NULL,
  `guest_email`   varchar(191) NOT NULL,
  `guest_phone`   varchar(30) DEFAULT NULL,
  `check_in`      date NOT NULL,
  `check_out`     date NOT NULL,
  `nights`        int NOT NULL,
  `adults`        int DEFAULT 1,
  `children`      int DEFAULT 0,
  `special_req`   text DEFAULT NULL,
  `total_usd`     decimal(10,2) NOT NULL,
  `discount_usd`  decimal(10,2) DEFAULT 0.00,
  `taxes_usd`     decimal(10,2) DEFAULT 0.00,
  `final_usd`     decimal(10,2) NOT NULL,
  `currency`      varchar(10) DEFAULT 'USD',
  `offer_code`    varchar(50) DEFAULT NULL,
  `member_number` varchar(50) DEFAULT NULL,
  `pay_method`    enum('card','upi','paypal','hotel') DEFAULT 'hotel',
  `pay_status`    enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(200) DEFAULT NULL,
  `paid_at`       timestamp NULL DEFAULT NULL,
  `status`        enum('confirmed','cancelled','checked_in','checked_out') DEFAULT 'confirmed',
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── booked_rooms ────────────────────────────────────────────
CREATE TABLE `booked_rooms` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `booking_ref`   varchar(30) NOT NULL,
  `room_type_id`  int NOT NULL,
  `room_type_name` varchar(100) NOT NULL,
  `room_number`   varchar(20) DEFAULT NULL,
  `quantity`      int DEFAULT 1,
  `price_usd`     decimal(10,2) NOT NULL,
  `nights`        int NOT NULL,
  `total_usd`     decimal(10,2) NOT NULL,
  FOREIGN KEY (`booking_ref`) REFERENCES `bookings`(`booking_ref`) ON DELETE CASCADE,
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── room_availability ───────────────────────────────────────
CREATE TABLE `room_availability` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `room_type_id`  int NOT NULL,
  `date`          date NOT NULL,
  `available`     tinyint(1) DEFAULT 1,
  UNIQUE KEY `rt_date` (`room_type_id`,`date`),
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── room_ratings ────────────────────────────────────────────
CREATE TABLE `room_ratings` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `room_type_id`  int NOT NULL,
  `user_id`       int DEFAULT NULL,
  `booking_ref`   varchar(30) DEFAULT NULL,
  `guest_name`    varchar(100) DEFAULT NULL,
  `rating`        tinyint DEFAULT 5,
  `title`         varchar(200) DEFAULT NULL,
  `review`        text DEFAULT NULL,
  `is_verified`   tinyint(1) DEFAULT 0,
  `is_approved`   tinyint(1) DEFAULT 0,
  `helpful_count` int DEFAULT 0,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── review_media ────────────────────────────────────────────
CREATE TABLE `review_media` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `review_id`   int NOT NULL,
  `type`        enum('image','video') DEFAULT 'image',
  `filename`    varchar(200) DEFAULT NULL,
  `url`         varchar(600) NOT NULL,
  `created_at`  timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`review_id`) REFERENCES `room_ratings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── review_images ───────────────────────────────────────────
CREATE TABLE `review_images` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `review_id`     int NOT NULL,
  `image_url`     varchar(600) NOT NULL,
  FOREIGN KEY (`review_id`) REFERENCES `room_ratings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── wishlists ───────────────────────────────────────────────
CREATE TABLE `wishlists` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `user_id`       int NOT NULL,
  `room_type_id`  int NOT NULL,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uw` (`user_id`,`room_type_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── contact_messages ────────────────────────────────────────
CREATE TABLE `contact_messages` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `name`          varchar(200) NOT NULL,
  `email`         varchar(191) NOT NULL,
  `phone`         varchar(50) DEFAULT NULL,
  `subject`       varchar(300) DEFAULT NULL,
  `message`       text NOT NULL,
  `is_read`       tinyint(1) DEFAULT 0,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── newsletter_subscribers ──────────────────────────────────
CREATE TABLE `newsletter_subscribers` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `email`         varchar(191) UNIQUE NOT NULL,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── loyalty_points ──────────────────────────────────────────
CREATE TABLE `loyalty_points` (
  `id`              int AUTO_INCREMENT PRIMARY KEY,
  `user_id`         int NOT NULL UNIQUE,
  `total_points`    int DEFAULT 0,
  `lifetime_points` int DEFAULT 0,
  `tier`            enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `updated_at`      timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── loyalty_transactions ────────────────────────────────────
CREATE TABLE `loyalty_transactions` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `user_id`       int NOT NULL,
  `booking_ref`   varchar(30) DEFAULT NULL,
  `type`          enum('earn','redeem','bonus','expire','admin') DEFAULT 'earn',
  `points`        int NOT NULL,
  `balance_after` int NOT NULL,
  `description`   varchar(200) DEFAULT NULL,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── notifications ───────────────────────────────────────────
CREATE TABLE `notifications` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `user_id`     int DEFAULT NULL,
  `type`        enum('booking','cancellation','payment','review','system','offer','loyalty') DEFAULT 'system',
  `title`       varchar(200) NOT NULL,
  `message`     text NOT NULL,
  `is_read`     tinyint(1) DEFAULT 0,
  `link`        varchar(400) DEFAULT NULL,
  `created_at`  timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── payment_logs ────────────────────────────────────────────
CREATE TABLE `payment_logs` (
  `id`              int AUTO_INCREMENT PRIMARY KEY,
  `booking_ref`     varchar(30) NOT NULL,
  `user_id`         int DEFAULT NULL,
  `amount_usd`      decimal(10,2) NOT NULL,
  `method`          varchar(50) DEFAULT NULL,
  `status`          enum('pending','success','failed','refunded') DEFAULT 'pending',
  `transaction_id`  varchar(200) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `created_at`      timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── price_overrides ─────────────────────────────────────────
CREATE TABLE `price_overrides` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `room_type_id`  int NOT NULL,
  `date_from`     date NOT NULL,
  `date_to`       date NOT NULL,
  `price_usd`     decimal(10,2) NOT NULL,
  `reason`        varchar(200) DEFAULT NULL,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── gallery_images ──────────────────────────────────────────
CREATE TABLE `gallery_images` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `title`       varchar(200) DEFAULT NULL,
  `category`    varchar(80) DEFAULT 'hotel',
  `image_url`   varchar(600) NOT NULL,
  `is_local`    tinyint(1) DEFAULT 0,
  `filename`    varchar(200) DEFAULT NULL,
  `sort_order`  int DEFAULT 0,
  `is_active`   tinyint(1) DEFAULT 1,
  `uploaded_by` int DEFAULT NULL,
  `created_at`  timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── chat_sessions ───────────────────────────────────────────
CREATE TABLE `chat_sessions` (
  `id`          varchar(40) PRIMARY KEY,
  `user_id`     int DEFAULT NULL,
  `created_at`  timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── chat_messages ───────────────────────────────────────────
CREATE TABLE `chat_messages` (
  `id`         int AUTO_INCREMENT PRIMARY KEY,
  `session_id` varchar(40) NOT NULL,
  `role`       enum('user','assistant') DEFAULT 'user',
  `content`    text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `chat_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── room_cart ───────────────────────────────────────────────
CREATE TABLE `room_cart` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `session_key`   varchar(80) NOT NULL,
  `room_type_id`  int NOT NULL,
  `quantity`      int DEFAULT 1,
  `check_in`      date NOT NULL,
  `check_out`     date NOT NULL,
  `guests`        int DEFAULT 2,
  `added_at`      timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── concierge_requests ──────────────────────────────────────
CREATE TABLE `concierge_requests` (
  `id`             int AUTO_INCREMENT PRIMARY KEY,
  `ref`            varchar(30) UNIQUE NOT NULL,
  `user_id`        int DEFAULT NULL,
  `booking_ref`    varchar(30) DEFAULT NULL,
  `category`       enum('transport','restaurant','activity','shopping','medical','other') DEFAULT 'other',
  `request`        text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` varchar(20) DEFAULT NULL,
  `status`         enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `response`       text DEFAULT NULL,
  `is_read`        tinyint(1) DEFAULT 0,
  `created_at`     timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── gift_cards ──────────────────────────────────────────────
CREATE TABLE `gift_cards` (
  `id`           int AUTO_INCREMENT PRIMARY KEY,
  `code`         varchar(20) UNIQUE NOT NULL,
  `value_usd`    decimal(10,2) NOT NULL,
  `balance_usd`  decimal(10,2) NOT NULL,
  `purchased_by` int DEFAULT NULL,
  `for_name`     varchar(200) DEFAULT NULL,
  `for_email`    varchar(191) DEFAULT NULL,
  `message`      text DEFAULT NULL,
  `is_active`    tinyint(1) DEFAULT 1,
  `expires_at`   date DEFAULT NULL,
  `created_at`   timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`purchased_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── gift_card_usage ─────────────────────────────────────────
CREATE TABLE `gift_card_usage` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `card_id`     int NOT NULL,
  `booking_ref` varchar(30) DEFAULT NULL,
  `amount_used` decimal(10,2) NOT NULL,
  `used_at`     timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`card_id`) REFERENCES `gift_cards`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── dining_reservations ─────────────────────────────────────
CREATE TABLE `dining_reservations` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `ref`         varchar(30) UNIQUE NOT NULL,
  `user_id`     int DEFAULT NULL,
  `venue_name`  varchar(200) DEFAULT NULL,
  `date`        date NOT NULL,
  `time`        varchar(10) NOT NULL,
  `guests`      int DEFAULT 2,
  `occasion`    varchar(100) DEFAULT NULL,
  `requests`    text DEFAULT NULL,
  `name`        varchar(200) NOT NULL,
  `email`       varchar(191) NOT NULL,
  `phone`       varchar(50) DEFAULT NULL,
  `status`      enum('confirmed','cancelled','seated','completed') DEFAULT 'confirmed',
  `created_at`  timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── spa_appointments ────────────────────────────────────────
CREATE TABLE `spa_appointments` (
  `id`           int AUTO_INCREMENT PRIMARY KEY,
  `ref`          varchar(30) UNIQUE NOT NULL,
  `user_id`      int DEFAULT NULL,
  `treatment`    varchar(200) NOT NULL,
  `therapist`    varchar(100) DEFAULT NULL,
  `date`         date NOT NULL,
  `time`         varchar(10) NOT NULL,
  `duration_min` int DEFAULT 60,
  `price_usd`    decimal(10,2) DEFAULT 0,
  `guests`       int DEFAULT 1,
  `name`         varchar(200) NOT NULL,
  `email`        varchar(191) NOT NULL,
  `phone`        varchar(50) DEFAULT NULL,
  `requests`     text DEFAULT NULL,
  `status`       enum('confirmed','cancelled','completed') DEFAULT 'confirmed',
  `created_at`   timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── events ──────────────────────────────────────────────────
CREATE TABLE `events` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `type`        enum('wedding','corporate','birthday','gala','conference','private') DEFAULT 'corporate',
  `name`        varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `capacity`    int DEFAULT 100,
  `price_from`  decimal(10,2) DEFAULT 0,
  `hero_image`  varchar(600) DEFAULT NULL,
  `is_active`   tinyint(1) DEFAULT 1,
  `sort_order`  int DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── event_bookings ──────────────────────────────────────────
CREATE TABLE `event_bookings` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `ref`         varchar(30) UNIQUE NOT NULL,
  `event_id`    int DEFAULT NULL,
  `user_id`     int DEFAULT NULL,
  `name`        varchar(200) NOT NULL,
  `email`       varchar(191) NOT NULL,
  `phone`       varchar(50) DEFAULT NULL,
  `event_date`  date DEFAULT NULL,
  `guests`      int DEFAULT 50,
  `budget_usd`  decimal(10,2) DEFAULT NULL,
  `message`     text DEFAULT NULL,
  `status`      enum('enquiry','quoted','confirmed','cancelled') DEFAULT 'enquiry',
  `created_at`  timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── properties ──────────────────────────────────────────────
CREATE TABLE `properties` (
  `id`           int AUTO_INCREMENT PRIMARY KEY,
  `code`         varchar(10) UNIQUE NOT NULL,
  `name`         varchar(200) NOT NULL,
  `city`         varchar(100) NOT NULL,
  `country`      varchar(100) NOT NULL,
  `country_code` varchar(3) DEFAULT NULL,
  `continent`    varchar(50) DEFAULT NULL,
  `tagline`      varchar(300) DEFAULT NULL,
  `description`  text DEFAULT NULL,
  `address`      text DEFAULT NULL,
  `lat`          decimal(10,7) DEFAULT NULL,
  `lng`          decimal(10,7) DEFAULT NULL,
  `phone`        varchar(50) DEFAULT NULL,
  `email`        varchar(191) DEFAULT NULL,
  `hero_image`   varchar(600) DEFAULT NULL,
  `thumb_image`  varchar(600) DEFAULT NULL,
  `rooms_count`  int DEFAULT 0,
  `stars`        int DEFAULT 5,
  `year_opened`  int DEFAULT NULL,
  `is_flagship`  tinyint(1) DEFAULT 0,
  `is_active`    tinyint(1) DEFAULT 1,
  `sort_order`   int DEFAULT 0,
  `created_at`   timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── property_images ─────────────────────────────────────────
CREATE TABLE `property_images` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `property_id` int NOT NULL,
  `image_url`   varchar(600) NOT NULL,
  `caption`     varchar(200) DEFAULT NULL,
  `sort_order`  int DEFAULT 0,
  FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── property_amenities ──────────────────────────────────────
CREATE TABLE `property_amenities` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `property_id` int NOT NULL,
  `icon`        varchar(100) DEFAULT NULL,
  `name`        varchar(100) NOT NULL,
  FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── hotel_properties ────────────────────────────────────────
CREATE TABLE `hotel_properties` (
  `id`           int AUTO_INCREMENT PRIMARY KEY,
  `slug`         varchar(100) UNIQUE NOT NULL,
  `name`         varchar(200) NOT NULL,
  `city`         varchar(100) NOT NULL,
  `country`      varchar(100) NOT NULL,
  `continent`    varchar(50) DEFAULT NULL,
  `lat`          decimal(10,7) DEFAULT NULL,
  `lng`          decimal(10,7) DEFAULT NULL,
  `hero_image`   varchar(600) DEFAULT NULL,
  `description`  text DEFAULT NULL,
  `rooms_count`  int DEFAULT 0,
  `is_flagship`  tinyint(1) DEFAULT 0,
  `sort_order`   int DEFAULT 0,
  `amenities`    text DEFAULT NULL,
  `phone`        varchar(50) DEFAULT NULL,
  `email`        varchar(100) DEFAULT NULL,
  `since_year`   int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── experiences ─────────────────────────────────────────────
CREATE TABLE `experiences` (
  `id`           int AUTO_INCREMENT PRIMARY KEY,
  `title`        varchar(200) NOT NULL,
  `category`     varchar(100) DEFAULT NULL,
  `duration`     varchar(80) DEFAULT NULL,
  `price_usd`    decimal(10,2) DEFAULT 0,
  `max_guests`   int DEFAULT 10,
  `description`  text DEFAULT NULL,
  `image_url`    varchar(600) DEFAULT NULL,
  `sort_order`   int DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── services_catalog ─────────────────────────────────────────
CREATE TABLE `services_catalog` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `icon`        varchar(20) DEFAULT NULL,
  `category`    varchar(100) NOT NULL,
  `name`        varchar(200) NOT NULL,
  `image_url`   varchar(600) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `hours`       varchar(120) DEFAULT NULL,
  `cta_link`    varchar(300) DEFAULT NULL,
  `is_active`   tinyint(1) DEFAULT 1,
  `sort_order`  int DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── virtual_tours ───────────────────────────────────────────
CREATE TABLE `virtual_tours` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `room_type_id` int DEFAULT NULL,
  `title`       varchar(200) DEFAULT NULL,
  `embed_url`   varchar(600) DEFAULT NULL,
  `thumb`       varchar(600) DEFAULT NULL,
  `is_active`   tinyint(1) DEFAULT 1,
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── room_assignments ────────────────────────────────────────
CREATE TABLE `room_assignments` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `booking_ref` varchar(30) NOT NULL,
  `room_id`     int NOT NULL,
  `assigned_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Users (password = "password" for all)
INSERT INTO `users` (`name`,`email`,`password`,`phone`,`country`,`role`,`is_admin`) VALUES
('Admin User',   'admin@royalevista.com','$2y$10$jnZyKeaH3UOLdehuAF2l6eliE9pUNutFHiBO5EzrjMoaykBA/Ypqi','+1-555-0101','USA','admin',1),
('John Smith',   'john@example.com',    '$2y$10$jnZyKeaH3UOLdehuAF2l6eliE9pUNutFHiBO5EzrjMoaykBA/Ypqi','+1-555-0102','USA','user',0),
('Priya Patel',  'priya@example.com',   '$2y$10$jnZyKeaH3UOLdehuAF2l6eliE9pUNutFHiBO5EzrjMoaykBA/Ypqi','+91-98765-43210','India','user',0),
('James Wilson', 'james@example.com',   '$2y$10$jnZyKeaH3UOLdehuAF2l6eliE9pUNutFHiBO5EzrjMoaykBA/Ypqi','+44-7700-900123','UK','user',0);

-- Room Types
INSERT INTO `room_types` (`name`,`slug`,`description`,`price_usd`,`max_guests`,`has_breakfast`,`avg_rating`,`review_count`,`sort_order`) VALUES
('Deluxe Room',       'deluxe',       'Elegant room with garden or city views, premium bedding and marble bathroom.',      120.00, 2, 0, 4.60, 127, 1),
('Exclusive Room',    'exclusive',    'Premium room with executive lounge access, complimentary minibar and butler.',       300.00, 3, 1, 4.75,  89, 2),
('Family Room',       'family',       'Spacious room designed for families with separate sleeping areas and kids corner.',  250.00, 5, 1, 4.55,  64, 3),
('Presidential Suite','presidential', 'The pinnacle of luxury — private jacuzzi, panoramic views, full butler service.',   600.00, 4, 1, 4.92,  42, 4);

-- Room Images (Unsplash)
INSERT INTO `room_images` (`room_type_id`,`image_url`,`is_primary`,`sort_order`) VALUES
(1,'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=900&q=80',1,0),
(1,'https://images.unsplash.com/photo-1540518614846-7eded433c457?w=900&q=80',0,1),
(1,'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=900&q=80',0,2),
(2,'https://images.unsplash.com/photo-1590496865381-1ce091b5a43f?w=900&q=80',1,0),
(2,'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=900&q=80',0,1),
(3,'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?w=900&q=80',1,0),
(3,'https://images.unsplash.com/photo-1566195992011-5f6b21e539aa?w=900&q=80',0,1),
(4,'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=900&q=80',1,0),
(4,'https://images.unsplash.com/photo-1630660664869-c9d3cc676880?w=900&q=80',0,1);

-- Room Facilities
INSERT INTO `room_facilities` (`room_type_id`,`name`,`icon`,`sort_order`) VALUES
(1,'King Size Bed','fas fa-bed',1),(1,'Free WiFi','fas fa-wifi',2),(1,'City View','fas fa-city',3),(1,'Room Service','fas fa-concierge-bell',4),(1,'Smart TV','fas fa-tv',5),(1,'Mini Bar','fas fa-wine-glass',6),
(2,'King Bed','fas fa-bed',1),(2,'Executive Lounge','fas fa-door-open',2),(2,'Breakfast Included','fas fa-utensils',3),(2,'Butler Service','fas fa-user-tie',4),(2,'Work Desk','fas fa-laptop',5),(2,'Premium Bar','fas fa-wine-bottle',6),
(3,'Twin & Double Beds','fas fa-bed',1),(3,'Kids Play Area','fas fa-child',2),(3,'Living Room','fas fa-couch',3),(3,'Kitchenette','fas fa-utensils',4),(3,'Games & Toys','fas fa-dice',5),(3,'Breakfast Included','fas fa-utensils',6),
(4,'King Bed + Jacuzzi','fas fa-hot-tub',1),(4,'Private Butler','fas fa-user-tie',2),(4,'Private Terrace','fas fa-sun',3),(4,'Grand Piano','fas fa-music',4),(4,'Home Cinema','fas fa-film',5),(4,'Personal Chef','fas fa-utensils',6);

-- Memberships
INSERT INTO `memberships` (`name`,`price_usd`,`discount_pct`,`duration_days`,`description`,`badge_text`,`badge_color`,`gradient_from`,`gradient_to`,`icon`,`is_popular`,`sort_order`) VALUES
('Silver', 100.00,  5, 180,'5% off all bookings + free cancellation up to 24h',  'Entry Level', '#94a3b8','#94a3b8','#64748b','🥉',0,1),
('Gold',   200.00, 15, 365,'15% off + priority support + complimentary room upgrade','Best Value','#f6c343','#f59e0b','#d97706','🥇',1,2),
('Platinum',300.00,25, 365,'25% off + VIP treatment + complimentary breakfast daily','VIP Exclusive','#a78bfa','#8b5cf6','#7c3aed','👑',0,3);

-- Membership Features
INSERT INTO `membership_features` (`membership_id`,`feature`,`icon`,`is_highlight`,`sort_order`) VALUES
(1,'5% discount on all bookings','%',0,1),(1,'Free cancellation up to 24h','🔄',0,2),(1,'Member email support','📧',0,3),(1,'Monthly newsletter & deals','📰',0,4),
(2,'15% discount on all bookings','%',0,1),(2,'Free cancellation up to 48h','🔄',0,2),(2,'Priority 24/7 support','💬',1,3),(2,'Room upgrade on availability','⬆️',1,4),(2,'Late checkout 2PM','🕑',0,5),
(3,'25% discount on all bookings','%',0,1),(3,'Free cancellation anytime','🔄',0,2),(3,'Dedicated concierge 24/7','🤵',1,3),(3,'Guaranteed room upgrade','👑',1,4),(3,'Daily complimentary breakfast','🍳',1,5),(3,'Airport limousine pickup','🚗',1,6);

-- User Memberships
INSERT INTO `user_memberships` (`user_id`,`membership_id`,`member_number`,`expires_at`,`status`) VALUES
(2,1,'SILVER-JOHN-001', DATE_ADD(NOW(),INTERVAL 180 DAY),'active'),
(3,2,'GOLD-PRIYA-001',  DATE_ADD(NOW(),INTERVAL 365 DAY),'active'),
(4,3,'PLAT-JAMES-001',  DATE_ADD(NOW(),INTERVAL 365 DAY),'active');

-- Offer Codes
INSERT INTO `offers` (`code`,`type`,`value`,`description`,`valid_from`,`valid_to`,`is_active`) VALUES
('WELCOME10','percent',10,'Welcome gift — 10% off your first stay!',  '2024-01-01','2027-12-31',1),
('SUMMER20', 'percent',20,'Summer special — 20% off all rooms.',       '2024-06-01','2027-09-30',1),
('HOLIDAY25','percent',25,'Holiday magic — 25% off festive stays.',    '2024-11-01','2027-01-15',1),
('WEEKEND15','percent',15,'Weekend escape — 15% off Fri–Sun.',         '2024-01-01','2027-12-31',1),
('FAMILY30', 'percent',30,'Family fun — 30% off Family Rooms.',        '2024-01-01','2027-12-31',1),
('FLAT50',   'fixed',  50,'Flat $50 off any booking.',                 '2024-01-01','2027-12-31',1);

-- Rooms (200 rooms — 50 per type)
INSERT INTO `rooms` (`room_number`,`room_type_id`,`floor`,`view_type`,`status`)
SELECT CONCAT('D',LPAD(n,3,'0')),1,CEIL(n/10),IF(n%2=0,'City View','Garden View'),'available'
FROM (SELECT 1+u+t*10 n FROM (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a CROSS JOIN (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) b ORDER BY n LIMIT 50) x;

INSERT INTO `rooms` (`room_number`,`room_type_id`,`floor`,`view_type`,`status`)
SELECT CONCAT('E',LPAD(n,3,'0')),2,CEIL(n/10)+5,IF(n<=17,'Ocean View',IF(n<=34,'City View','Garden View')),'available'
FROM (SELECT 1+u+t*10 n FROM (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a CROSS JOIN (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) b ORDER BY n LIMIT 50) x;

INSERT INTO `rooms` (`room_number`,`room_type_id`,`floor`,`view_type`,`status`)
SELECT CONCAT('F',LPAD(n,3,'0')),3,CEIL(n/10)+10,'Garden View','available'
FROM (SELECT 1+u+t*10 n FROM (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a CROSS JOIN (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) b ORDER BY n LIMIT 50) x;

INSERT INTO `rooms` (`room_number`,`room_type_id`,`floor`,`view_type`,`status`)
SELECT CONCAT('P',LPAD(n,3,'0')),4,CEIL(n/10)+15,'Panoramic View','available'
FROM (SELECT 1+u+t*10 n FROM (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a CROSS JOIN (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) b ORDER BY n LIMIT 50) x;

-- Room availability (next 90 days)
INSERT INTO `room_availability` (`room_type_id`,`date`,`available`)
SELECT rt.id, DATE_ADD(CURDATE(),INTERVAL n DAY), IF(RAND()>0.12,1,0)
FROM `room_types` rt
CROSS JOIN (
  SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
  UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19
  UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
  UNION SELECT 30 UNION SELECT 35 UNION SELECT 40 UNION SELECT 45 UNION SELECT 50 UNION SELECT 55 UNION SELECT 60 UNION SELECT 65 UNION SELECT 70 UNION SELECT 75 UNION SELECT 80 UNION SELECT 85 UNION SELECT 89
) nums;

-- Sample ratings
INSERT INTO `room_ratings` (`room_type_id`,`guest_name`,`rating`,`title`,`review`,`is_verified`,`is_approved`) VALUES
(1,'Sarah M.',5,'Absolutely perfect','The room was impeccable. Stunning views and exceptional service.',1,1),
(1,'David L.',4,'Great value stay','Clean, beautifully appointed. Would definitely return.',1,1),
(2,'Emma R.',5,'Pure luxury','Executive lounge access was a game-changer. Worth every penny.',1,1),
(3,'The Kumar Family',5,'Kids had a blast!','Spacious, thoughtfully designed. Kids loved the play corner.',1,1),
(4,'Sheikh A.',5,'Beyond expectations','The Presidential Suite is extraordinary. Butler was amazing.',1,1),
(2,'Michael T.',5,'Best business trip ever','The work desk setup and lounge were perfect for my meetings.',1,1);

-- Sample bookings
INSERT INTO `bookings` (`booking_ref`,`invoice_no`,`user_id`,`guest_name`,`guest_email`,`guest_phone`,`check_in`,`check_out`,`nights`,`adults`,`total_usd`,`taxes_usd`,`final_usd`,`currency`,`pay_method`,`pay_status`,`status`) VALUES
('BK2026A1','INV2026A1',2,'John Smith','john@example.com','+1-555-0102',DATE_ADD(CURDATE(),INTERVAL 5 DAY),DATE_ADD(CURDATE(),INTERVAL 8 DAY),3,2,360.00,64.80,424.80,'USD','card','paid','confirmed'),
('BK2026B2','INV2026B2',3,'Priya Patel','priya@example.com','+91-98765-43210',DATE_ADD(CURDATE(),INTERVAL 12 DAY),DATE_ADD(CURDATE(),INTERVAL 15 DAY),3,3,900.00,162.00,1062.00,'INR','upi','paid','confirmed'),
('BK2026C3','INV2026C3',4,'James Wilson','james@example.com','+44-7700-900123',DATE_ADD(CURDATE(),INTERVAL 3 DAY),DATE_ADD(CURDATE(),INTERVAL 5 DAY),2,2,1200.00,216.00,1416.00,'GBP','card','paid','confirmed');

INSERT INTO `booked_rooms` (`booking_ref`,`room_type_id`,`room_type_name`,`room_number`,`quantity`,`price_usd`,`nights`,`total_usd`) VALUES
('BK2026A1',1,'Deluxe Room','D012',1,120.00,3,360.00),
('BK2026B2',2,'Exclusive Room','E023',1,300.00,3,900.00),
('BK2026C3',4,'Presidential Suite','P001',1,600.00,2,1200.00);

-- Loyalty points for sample users
INSERT IGNORE INTO `loyalty_points` (`user_id`,`total_points`,`lifetime_points`,`tier`) VALUES
(1,0,0,'bronze'),
(2,4248,4248,'silver'),
(3,10620,10620,'gold'),
(4,14160,14160,'platinum');

-- Gallery images
INSERT INTO `gallery_images` (`title`,`category`,`image_url`,`sort_order`) VALUES
('Grand Lobby',      'lobby',      'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=900&q=80',1),
('Infinity Pool',    'pool',       'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=900&q=80',2),
('Fine Dining',      'restaurant', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=900&q=80',3),
('Deluxe Room',      'rooms',      'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=900&q=80',4),
('Spa & Wellness',   'spa',        'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=900&q=80',5),
('Rooftop Bar',      'bar',        'https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=900&q=80',6),
('Garden Terrace',   'outdoor',    'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=900&q=80',7),
('Presidential Suite','rooms',     'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=900&q=80',8),
('Ballroom',         'events',     'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=900&q=80',9),
('Beach Club',       'outdoor',    'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=900&q=80',10),
('Gym & Fitness',    'facilities', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=900&q=80',11),
('Family Room',      'rooms',      'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?w=900&q=80',12);

-- Events
INSERT INTO `events` (`type`,`name`,`description`,`capacity`,`price_from`,`hero_image`,`sort_order`) VALUES
('wedding',   'Royal Wedding',       'The wedding of your dreams. Our dedicated wedding planners craft a ceremony and reception that is uniquely yours.',   400,25000,'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=900&q=80',1),
('corporate', 'Executive Conference','State-of-the-art conference facilities for the world''s most discerning corporate clients.',                          500, 8000,'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=900&q=80',2),
('gala',      'Black Tie Gala',      'For charity galas, anniversary celebrations, and exclusive social events.',                                           600,15000,'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=900&q=80',3),
('birthday',  'Milestone Celebration','From intimate dinners to lavish birthday parties, we create personalised celebrations.',                            100, 3000,'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=900&q=80',4),
('conference','Business Summit',     'Multi-day conference packages with breakout rooms, premium technology, and dedicated event managers.',               300, 5000,'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=900&q=80',5),
('private',   'Private Dining',     'Exclusive private dining experiences with personalised menus crafted by our Michelin-starred chefs.',                  24, 1200,'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=900&q=80',6);

-- Properties worldwide
INSERT INTO `properties` (`code`,`name`,`city`,`country`,`country_code`,`continent`,`tagline`,`description`,`address`,`lat`,`lng`,`phone`,`email`,`hero_image`,`thumb_image`,`rooms_count`,`stars`,`year_opened`,`is_flagship`,`sort_order`) VALUES
('DXB','Royale Vista Dubai',     'Dubai',    'United Arab Emirates','AE','Asia',    'The pinnacle of Arabian luxury',        'Our flagship property rising above the Palm Jumeirah.',                   'Palm Jumeirah, Dubai UAE',           25.1124,  55.1389,'+971 4 123 4567','dubai@royalevista.com',    'https://images.unsplash.com/photo-1548438294-1ad5d5f4f063?w=1400&q=80','https://images.unsplash.com/photo-1548438294-1ad5d5f4f063?w=600&q=80',400,5,2019,1,1),
('NYC','Royale Vista New York',  'New York', 'United States',       'US','Americas','Where the world comes to stay',         'A Midtown Manhattan landmark with unparalleled views of Central Park.',   '720 Fifth Avenue, New York NY 10019',40.7614, -73.9776,'+1 212 555 0100','newyork@royalevista.com',  'https://images.unsplash.com/photo-1534430480872-3498386e7856?w=1400&q=80','https://images.unsplash.com/photo-1534430480872-3498386e7856?w=600&q=80',350,5,2017,0,2),
('PAR','Royale Vista Paris',     'Paris',    'France',              'FR','Europe',  'L élégance parisienne, revisitée',     'Set on the legendary Avenue Montaigne, steps from the Eiffel Tower.',    '32 Avenue Montaigne, 75008 Paris',   48.8659,   2.3041,'+33 1 44 55 66 00','paris@royalevista.com',   'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=1400&q=80','https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&q=80',220,5,2015,0,3),
('LON','Royale Vista London',    'London',   'United Kingdom',      'GB','Europe',  'Classic British grandeur, reimagined', 'A historic Mayfair townhouse transformed into one of London most celebrated hotels.',  '45 Park Lane, Mayfair, London W1K',  51.5034,  -0.1540,'+44 20 7123 4567','london@royalevista.com',   'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=1400&q=80','https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600&q=80',180,5,2016,0,4),
('SIN','Royale Vista Singapore', 'Singapore','Singapore',           'SG','Asia',    'A jewel in the Lion City',              'Soaring above Marina Bay with panoramic views of the glittering harbour.','10 Marina Blvd, Singapore 018981',   1.2782,  103.8521,'+65 6123 4567','singapore@royalevista.com','https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=1400&q=80','https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=600&q=80',300,5,2020,0,5),
('TOK','Royale Vista Tokyo',     'Tokyo',    'Japan',               'JP','Asia',    'Harmony of tradition and modernity',    'In the heart of Marunouchi overlooking the Imperial Palace Gardens.',    '2-1 Marunouchi, Chiyoda, Tokyo',     35.6829, 139.7654,'+81 3 1234 5678','tokyo@royalevista.com',    'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=1400&q=80','https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=600&q=80',250,5,2021,0,6),
('MUM','Royale Vista Mumbai',    'Mumbai',   'India',               'IN','Asia',    'Gateway to Indian elegance',            'Overlooking the Arabian Sea in Nariman Point.',                          'Nariman Point, Marine Drive, Mumbai',18.9256,  72.8242,'+91 22 1234 5678','mumbai@royalevista.com',   'https://images.unsplash.com/photo-1567157577867-05ccb1388e66?w=1400&q=80','https://images.unsplash.com/photo-1567157577867-05ccb1388e66?w=600&q=80',280,5,2018,0,7),
('SYD','Royale Vista Sydney',    'Sydney',   'Australia',           'AU','Pacific', 'Iconic harbour, timeless luxury',       'Perched above Sydney Harbour with views of the Opera House.',            '61 Macquarie Street, Sydney NSW 2000',-33.8589,151.2101,'+61 2 9123 4567','sydney@royalevista.com',   'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1400&q=80','https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80',200,5,2022,0,8);

-- Hotel properties (for locations.php map)
INSERT INTO `hotel_properties` (`slug`,`name`,`city`,`country`,`continent`,`lat`,`lng`,`hero_image`,`description`,`rooms_count`,`is_flagship`,`sort_order`,`amenities`,`phone`,`email`,`since_year`) VALUES
('dubai',     'Royale Vista Dubai',     'Dubai',    'UAE',          'Asia',    25.1124, 55.1389,'https://images.unsplash.com/photo-1548438294-1ad5d5f4f063?w=800&q=80','Palm Jumeirah flagship rising above the Arabian Gulf.',400,1,1,'Infinity Pool,Private Beach,Butler Service,Helipad','+971 4 123 4567','dubai@royalevista.com',2019),
('new-york',  'Royale Vista New York',  'New York', 'USA',          'Americas',40.7614,-73.9776,'https://images.unsplash.com/photo-1534430480872-3498386e7856?w=800&q=80','Midtown Manhattan landmark overlooking Central Park.',350,0,2,'Rooftop Pool,Michelin Dining,Central Park View,Spa','+1 212 555 0100','newyork@royalevista.com',2017),
('paris',     'Royale Vista Paris',     'Paris',    'France',       'Europe',  48.8659,  2.3041,'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=800&q=80','Set on Avenue Montaigne, steps from the Eiffel Tower.',220,0,3,'Eiffel View,French Cuisine,Couture Concierge,Garden','+33 1 44 55 66 00','paris@royalevista.com',2015),
('london',    'Royale Vista London',    'London',   'UK',           'Europe',  51.5034, -0.1540,'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=800&q=80','Historic Mayfair townhouse overlooking Hyde Park.',180,0,4,'Hyde Park View,Afternoon Tea,Royal Butler,Drawing Room','+44 20 7123 4567','london@royalevista.com',2016),
('singapore', 'Royale Vista Singapore', 'Singapore','Singapore',    'Asia',     1.2782,103.8521,'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=800&q=80','Soaring above Marina Bay with harbour panoramas.',300,0,5,'Marina Bay View,Infinity Pool,Gardens by the Bay,Spa','+65 6123 4567','singapore@royalevista.com',2020),
('tokyo',     'Royale Vista Tokyo',     'Tokyo',    'Japan',        'Asia',    35.6829,139.7654,'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=800&q=80','Marunouchi elegance overlooking Imperial Palace.',250,0,6,'Imperial View,Kaiseki Dining,Onsen Spa,Tea Ceremony','+81 3 1234 5678','tokyo@royalevista.com',2021),
('mumbai',    'Royale Vista Mumbai',    'Mumbai',   'India',        'Asia',    18.9256, 72.8242,'https://images.unsplash.com/photo-1567157577867-05ccb1388e66?w=800&q=80','Overlooking the Arabian Sea at Nariman Point.',280,0,7,'Sea View,Bollywood Lounge,Pool Deck,Ayurveda Spa','+91 22 1234 5678','mumbai@royalevista.com',2018),
('sydney',    'Royale Vista Sydney',    'Sydney',   'Australia',    'Pacific', -33.8589,151.2101,'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&q=80','Opera House and Harbour Bridge views from every room.',200,0,8,'Harbour View,Rooftop Bar,Opera House Terrace,Yacht Club','+61 2 9123 4567','sydney@royalevista.com',2022);

-- Experiences
INSERT INTO `experiences` (`title`,`category`,`duration`,`price_usd`,`max_guests`,`description`,`image_url`,`sort_order`) VALUES
('Helicopter Sunset Tour','Adventure','90 min',850,4,'Soar above the skyline in a private helicopter as the sun sets. Includes champagne and canapés.','https://images.unsplash.com/photo-1538593273645-e5c1a440a73c?w=800&q=80',1),
('Private Truffle Hunt','Culinary','Half Day',1200,6,'Join our Executive Chef on an exclusive truffle hunt followed by a Michelin-starred cooking class.','https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80',2),
('Underwater Spa Journey','Wellness','3 Hours',480,2,'Glass-bottom treatment rooms suspended 3 metres below the surface. Marine-inspired treatments.','https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800&q=80',3),
('Private Art Gallery Tour','Culture','2 Hours',320,8,'Exclusive after-hours access to the city finest gallery with our resident art curator.','https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?w=800&q=80',4),
('Yacht Sunrise Champagne','Luxury','3 Hours',950,8,'Private yacht departure at dawn. Champagne breakfast and live music on the water.','https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=800&q=80',5),
('Desert Safari & Stargazing','Adventure','Full Day',680,4,'Dune bashing at sunset, Bedouin camp dinner under the stars.','https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=800&q=80',6),
('Caviar & Vodka Masterclass','Culinary','2 Hours',420,6,'Learn to distinguish Beluga from Ossetra with our sommelier, paired with vintage Krug.','https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&q=80',7),
('Private Royal Hammam','Wellness','2 Hours',380,2,'Authentic Moroccan hammam for two in our private marble suite with argan oil massage.','https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&q=80',8);

-- Services catalog
INSERT INTO `services_catalog` (`icon`,`category`,`name`,`image_url`,`description`,`hours`,`cta_link`,`is_active`,`sort_order`) VALUES
('🍽','Fine Dining','The Royale Table','https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=700&q=70','Michelin-starred restaurant featuring seasonal tasting menus. An extraordinary culinary journey.','Open 7AM–11PM','/dining.php',1,1),
('🧖','Spa & Wellness','Aria Spa','https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=700&q=70','Ancient healing traditions meet modern techniques across 18 treatment rooms.','Daily 8AM–10PM','/spa.php',1,2),
('🏊','Infinity Pool','Rooftop Oasis','https://images.unsplash.com/photo-1613977257363-707ba9348227?w=700&q=70','Breathtaking rooftop infinity pool with panoramic city views.','Daily 7AM–11PM','/contact.php',1,3),
('💪','Fitness','The Athletic Club','https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=700&q=70','State-of-the-art equipment, personal training, yoga and pilates studios.','24 Hours','/contact.php',1,4),
('🥂','Cocktail Bar','The Gold Lounge','https://images.unsplash.com/photo-1572116469696-31de0f17cc34?w=700&q=70','200+ premium spirits and bespoke cocktails by award-winning mixologists.','Daily 4PM–2AM','/contact.php',1,5),
('🎭','Events','Grand Occasions','https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=700&q=70','Seven versatile spaces from boardrooms to ballrooms for up to 800 guests.','By Appointment','/events.php',1,6);

COMMIT;

SELECT '✅ ROYALE VISTA DATABASE READY!' AS status;
SELECT CONCAT('  Rooms: ', COUNT(*)) info FROM rooms;
SELECT CONCAT('  Room Types: ', COUNT(*)) info FROM room_types;
SELECT CONCAT('  Users: ', COUNT(*)) info FROM users;
SELECT CONCAT('  Memberships: ', COUNT(*)) info FROM memberships;
SELECT '  Login: admin@royalevista.com / password' info;
SELECT '  Guest: john@example.com / password' info;
-- Staff Management Tables for Royale Vista Hotel

-- ── staff ───────────────────────────────────────────────
CREATE TABLE `staff` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `name`          varchar(200) NOT NULL,
  `email`         varchar(191) UNIQUE NOT NULL,
  `phone`         varchar(30) DEFAULT NULL,
  `password`      varchar(255) DEFAULT NULL,
  `position`      varchar(100) DEFAULT NULL,
  `department`    varchar(100) DEFAULT NULL,
  `salary`        decimal(10,2) DEFAULT 0.00,
  `hire_date`     date DEFAULT NULL,
  `status`        enum('active','inactive','on_leave') DEFAULT 'active',
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── attendance ─────────────────────────────────────────
CREATE TABLE `attendance` (
  `id`            int AUTO_INCREMENT PRIMARY KEY,
  `staff_id`      int NOT NULL,
  `date`          date NOT NULL,
  `checkin_time`  time DEFAULT NULL,
  `checkout_time` time DEFAULT NULL,
  `notes`         text DEFAULT NULL,
  `created_at`    timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_staff_date` (`staff_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample staff data with passwords (password: staff123)
INSERT INTO `staff` (`name`, `email`, `phone`, `password`, `position`, `department`, `salary`, `hire_date`, `status`) VALUES
('John Smith', 'john.smith@royalevista.com', '+1 212 555 0101', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Front Desk Manager', 'Front Office', 55000.00, '2023-01-15', 'active'),
('Sarah Johnson', 'sarah.johnson@royalevista.com', '+1 212 555 0102', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Housekeeping Supervisor', 'Housekeeping', 42000.00, '2023-02-01', 'active'),
('Michael Chen', 'michael.chen@royalevista.com', '+1 212 555 0103', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Executive Chef', 'Food & Beverage', 75000.00, '2023-01-20', 'active'),
('Emily Davis', 'emily.davis@royalevista.com', '+1 212 555 0104', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Spa Manager', 'Spa & Wellness', 48000.00, '2023-03-10', 'active'),
('Robert Wilson', 'robert.wilson@royalevista.com', '+1 212 555 0105', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maintenance Supervisor', 'Engineering', 45000.00, '2023-02-15', 'active');

-- Insert sample attendance data
INSERT INTO `attendance` (`staff_id`, `date`, `checkin_time`, `checkout_time`, `notes`) VALUES
(1, '2026-03-31', '09:00:00', '17:30:00', 'Regular shift'),
(2, '2026-03-31', '08:30:00', '16:45:00', 'Morning shift'),
(3, '2026-03-31', '10:00:00', '18:00:00', 'Kitchen supervision'),
(4, '2026-03-31', '12:00:00', '20:00:00', 'Evening appointments'),
(5, '2026-03-31', '07:00:00', '15:30:00', 'Facility maintenance');
