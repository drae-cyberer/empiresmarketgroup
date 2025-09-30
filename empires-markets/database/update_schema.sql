-- New tables for direct trading
CREATE TABLE `live_trades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `instrument` varchar(20) NOT NULL,
  `trade_type` enum('BUY','SELL') NOT NULL,
  `volume` decimal(10,4) NOT NULL,
  `open_price` decimal(12,6) NOT NULL,
  `current_price` decimal(12,6) DEFAULT NULL,
  `stop_loss` decimal(12,6) DEFAULT NULL,
  `take_profit` decimal(12,6) DEFAULT NULL,
  `swap` decimal(10,2) DEFAULT 0.00,
  `commission` decimal(10,2) DEFAULT 0.00,
  `profit_loss` decimal(10,2) DEFAULT 0.00,
  `status` enum('OPEN','CLOSED','PENDING') DEFAULT 'OPEN',
  `opened_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `live_trades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
);

CREATE TABLE `trading_instruments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `symbol` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('FOREX','CRYPTO','STOCKS','COMMODITIES','INDICES') NOT NULL,
  `base_currency` varchar(10) NOT NULL,
  `quote_currency` varchar(10) NOT NULL,
  `pip_value` decimal(8,6) NOT NULL,
  `min_volume` decimal(8,4) DEFAULT 0.0100,
  `max_volume` decimal(8,4) DEFAULT 100.0000,
  `spread` decimal(5,2) DEFAULT 0.00,
  `leverage` int(11) DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `symbol` (`symbol`)
);

-- Enhanced plans table
ALTER TABLE `plans`
ADD COLUMN `max_leverage` int(11) DEFAULT 100,
ADD COLUMN `spread_reduction` decimal(3,2) DEFAULT 0.00,
ADD COLUMN `instruments_count` int(11) DEFAULT 28,
ADD COLUMN `support_level` enum('EMAIL','PRIORITY_EMAIL','PHONE_CHAT','DEDICATED') DEFAULT 'EMAIL',
ADD COLUMN `analysis_access` tinyint(1) DEFAULT 0,
ADD COLUMN `copy_trading_access` tinyint(1) DEFAULT 0,
ADD COLUMN `api_access` tinyint(1) DEFAULT 0;

-- Plan features junction table
CREATE TABLE `plan_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `feature_name` varchar(100) NOT NULL,
  `feature_value` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `plan_features_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
);