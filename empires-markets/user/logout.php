<?php
// User Logout
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Logout user
Auth::logout();

// Redirect to login page
redirect('index.php');
?>
