<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('Invalid CSRF token.', 'danger');
        redirect('trading.php');
    }

    $instrument = sanitize_input($_POST['instrument']);
    $trade_type = in_array($_POST['trade_type'], ['BUY', 'SELL']) ? $_POST['trade_type'] : null;
    $volume = filter_input(INPUT_POST, 'volume', FILTER_VALIDATE_FLOAT);

    if (!$instrument || !$trade_type || $volume === false || $volume <= 0) {
        set_flash_message('Invalid trade parameters provided.', 'danger');
        redirect('trading.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $result = place_live_trade($user_id, $instrument, $trade_type, $volume);

    if ($result['success']) {
        set_flash_message('Trade placed successfully! Trade ID: ' . $result['trade_id'], 'success');
        redirect('trade-history.php'); // Redirect to a new page to see trade history
    } else {
        set_flash_message('Error placing trade: ' . ($result['message'] ?? 'An unknown error occurred.'), 'danger');
        redirect('trading.php');
    }
} else {
    redirect('trading.php');
}
?>