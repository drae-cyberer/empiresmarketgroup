-- Empires Markets Database Structure
-- Investment Platform with Copy Trading

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: empires_markets

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `total_deposits` decimal(10,2) DEFAULT 0.00,
  `total_withdrawals` decimal(10,2) DEFAULT 0.00,
  `total_trades` int(11) DEFAULT 0,
  `account_type` enum('DEMO','LIVE') DEFAULT 'DEMO',
  `account_status` enum('ACTIVE','INACTIVE','SUSPENDED') DEFAULT 'ACTIVE',
  `signal_strength` decimal(5,2) DEFAULT 25.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `admin_users`
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMIN','SUPER_ADMIN') DEFAULT 'ADMIN',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `traders`
CREATE TABLE `traders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trader_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `level` int(11) NOT NULL,
  `level_amount` decimal(10,2) NOT NULL,
  `processed_amount` decimal(12,2) DEFAULT 0.00,
  `active_connections` int(11) DEFAULT 0,
  `rating` int(11) DEFAULT 0,
  `percentage_rating` decimal(5,2) DEFAULT 0.00,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trader_id` (`trader_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `plans`
CREATE TABLE `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `level` int(11) NOT NULL,
  `min_amount` decimal(10,2) NOT NULL,
  `max_amount` decimal(10,2) DEFAULT NULL,
  `features` text,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `deposits`
CREATE TABLE `deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `deposit_type` varchar(50) NOT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `admin_note` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `withdrawals`
CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `withdrawal_method` varchar(50) NOT NULL,
  `wallet_address` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `admin_note` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `trades`
CREATE TABLE `trades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `trader_id` int(11) DEFAULT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `asset` varchar(20) NOT NULL,
  `trade_type` enum('BUY','SELL') NOT NULL,
  `open_amount` decimal(10,2) NOT NULL,
  `close_amount` decimal(10,2) DEFAULT NULL,
  `return_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('OPEN','CLOSED','CANCELLED') DEFAULT 'OPEN',
  `is_copy_trade` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `trader_id` (`trader_id`),
  CONSTRAINT `trades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `trades_ibfk_2` FOREIGN KEY (`trader_id`) REFERENCES `traders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `copy_trades`
CREATE TABLE `copy_trades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `trader_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('ACTIVE','INACTIVE','CANCELLED') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `trader_id` (`trader_id`),
  CONSTRAINT `copy_trades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `copy_trades_ibfk_2` FOREIGN KEY (`trader_id`) REFERENCES `traders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `transactions`
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `type` enum('DEPOSIT','WITHDRAWAL','TRADE','BONUS','COMMISSION') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `status` enum('PENDING','COMPLETED','FAILED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `market_data`
CREATE TABLE `market_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `symbol` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(12,6) NOT NULL,
  `change_percent` decimal(5,2) DEFAULT 0.00,
  `volume` bigint(20) DEFAULT 0,
  `market_cap` bigint(20) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `symbol` (`symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO `admin_users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SUPER_ADMIN');

-- Insert default investment plans
INSERT INTO `plans` (`name`, `level`, `min_amount`, `max_amount`, `features`) VALUES
('Level 1', 1, 500.00, 999.99, 'Basic copy trading access'),
('Level 2', 2, 1000.00, 2999.99, 'Enhanced copy trading features'),
('Level 3', 3, 3000.00, 4999.99, 'Advanced trading tools'),
('Level 4', 4, 5000.00, 9999.99, 'Premium trading features'),
('Level 5', 5, 10000.00, NULL, 'VIP trading access - Currently Unavailable');

-- Insert sample traders
INSERT INTO `traders` (`trader_id`, `name`, `avatar`, `category`, `level`, `level_amount`, `processed_amount`, `active_connections`, `rating`, `percentage_rating`) VALUES
('MIA2VYW', 'LAIMI HELFERN', 'avatar1.jpg', 'HUMAN', 1, 500.00, 500.00, 150, 10, 60.00),
('NAIDRIL', 'CORINX AFFILIATE', 'avatar2.jpg', 'MINING BOT', 1, 500.00, 220.00, 106, 8, 70.00),
('VIZRCY', 'ZENECA', 'avatar3.jpg', 'NFT', 1, 500.00, 500.00, 70, 8, 62.00),
('WHISKASC', 'APPLE', 'avatar4.jpg', 'TRADING BOT', 1, 500.00, 500.00, 1500, 10, 75.00),
('TRADER_5', 'SAMPLE TRADER', 'avatar5.jpg', 'HUMAN', 2, 1000.00, 800.00, 45, 9, 68.00),
('TRADER_6', 'CRYPTO EXPERT', 'avatar6.jpg', 'HUMAN', 3, 3000.00, 3150.00, 560, 10, 82.00),
('BEMOMY79', 'TRADER J', 'avatar7.jpg', 'HUMAN', 4, 5000.00, 5100.00, 750, 15, 85.00);

-- Insert sample market data
INSERT INTO `market_data` (`symbol`, `name`, `price`, `change_percent`, `volume`) VALUES
('AAPL', 'Apple Inc', 216.00, -0.12, 16310000),
('BTCUSDT', 'Bitcoin / TetherUS', 106902.46, -0.04, 24000000),
('FEDFUNC', 'Effective Federal Funds Rate', 4.33, 0.00, 0);

COMMIT;
