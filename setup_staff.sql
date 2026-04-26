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
