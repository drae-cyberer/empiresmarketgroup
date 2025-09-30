<?php
// Admin Logout
require_once '../includes/config.php';

// Destroy admin session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

// Redirect to login page
header('Location: index.php');
exit();
?>
