<?php
// Empires Markets Configuration File

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'empiresm_db');
define('DB_PASS', 'neyQ559mAshFKBQ7Jmcn');
define('DB_NAME', 'empiresm_db');

// Site Configuration
define('SITE_URL', 'https://empiresmarketgroup.com/empires-markets');
define('SITE_NAME', 'Empires Markets Group');
define('SITE_EMAIL', 'support@empiresmarketgroup.com');

// Security Configuration
define('ENCRYPT_KEY', 'EmpireMarkets2025SecretKey!@#');
define('SESSION_LIFETIME', 3600); // 1 hour

// File Upload Configuration
define('UPLOAD_PATH', '../assets/images/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', array('jpg', 'jpeg', 'png', 'gif', 'pdf'));

// Trading Configuration
define('MIN_DEPOSIT', 10.00);
define('MIN_WITHDRAWAL', 20.00);
define('WITHDRAWAL_FEE', 0.02); // 2%

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
