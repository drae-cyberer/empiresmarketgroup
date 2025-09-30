<?php
// Admin User Details - Edit All Balances
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$admin_username = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'];

// Get user ID from URL
$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    header('Location: users.php');
    exit();
}

// Get user details
$user = get_user_by_id($user_id);
if (!$user) {
    header('Location: users.php');
    exit();
}

// Helper functions for user details
function get_user_stats($user_id) {
    global $db;
    try {
        return $db->selectOne("
            SELECT 
                COUNT(DISTINCT d.id) as total_deposits,
                COALESCE(SUM(CASE WHEN d.status = 'APPROVED' THEN d.amount ELSE 0 END), 0) as approved_deposits,
                COUNT(DISTINCT w.id) as total_withdrawals,
                COALESCE(SUM(CASE WHEN w.status = 'APPROVED' THEN w.amount ELSE 0 END), 0) as approved_withdrawals,
                COUNT(DISTINCT t.id) as total_trades,
                COUNT(DISTINCT tr.id) as total_transactions,
                COUNT(DISTINCT st.id) as total_tickets
            FROM users u
            LEFT JOIN deposits d ON u.id = d.user_id
            LEFT JOIN withdrawals w ON u.id = w.user_id
            LEFT JOIN trades t ON u.id = t.user_id
            LEFT JOIN transactions tr ON u.id = tr.user_id
            LEFT JOIN support_tickets st ON u.id = st.user_id
            WHERE u.id = ?
        ", [$user_id]);
    } catch (Exception $e) {
        return [
            'total_deposits' => 0, 'approved_deposits' => 0,
            'total_withdrawals' => 0, 'approved_withdrawals' => 0,
            'total_trades' => 0, 'total_transactions' => 0, 'total_tickets' => 0
        ];
    }
}

function get_user_recent_deposits($user_id, $limit = 5) {
    global $db;
    try {
        return $db->select("
            SELECT * FROM deposits 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ", [$user_id, $limit]);
    } catch (Exception $e) {
        return [];
    }
}

function get_user_recent_withdrawals($user_id, $limit = 5) {
    global $db;
    try {
        return $db->select("
            SELECT * FROM withdrawals 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ", [$user_id, $limit]);
    } catch (Exception $e) {
        return [];
    }
}

function get_user_recent_transactions($user_id, $limit = 10) {
    global $db;
    try {
        return $db->select("
            SELECT * FROM transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ", [$user_id, $limit]);
    } catch (Exception $e) {
        return [];
    }
}

function get_user_recent_tickets($user_id, $limit = 5) {
    global $db;
    try {
        return $db->select("
            SELECT * FROM support_tickets 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ", [$user_id, $limit]);
    } catch (Exception $e) {
        return [];
    }
}

// Fixed function - matches your actual database schema
function get_user_selected_plan($user_id) {
    global $db;
    try {
        return $db->selectOne("
            SELECT p.id, p.name, p.level, p.min_amount, p.max_amount, p.features, 
                   p.max_leverage, p.commission_rate, p.withdrawal_limit_daily, 
                   p.withdrawal_limit_monthly, p.copy_trading_enabled, p.auto_trading_enabled, 
                   p.priority_support, p.status as plan_status, u.plan_subscribed_at
            FROM users u
            LEFT JOIN plans p ON u.plan_id = p.id
            WHERE u.id = ?
        ", [$user_id]);
    } catch (Exception $e) {
        error_log("Error getting user plan: " . $e->getMessage());
        return null;
    }
}

// Handle admin actions
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'update_status':
                $new_status = $_POST['status'] ?? '';
                if (in_array($new_status, ['ACTIVE', 'INACTIVE', 'SUSPENDED'])) {
                    $db->update("UPDATE users SET account_status = ? WHERE id = ?", [$new_status, $user_id]);
                    $user['account_status'] = $new_status;
                    $message = 'User status updated successfully.';
                    $message_type = 'success';
                }
                break;
                
            case 'update_account_type':
                $new_type = $_POST['account_type'] ?? '';
                if (in_array($new_type, ['DEMO', 'LIVE'])) {
                    $db->update("UPDATE users SET account_type = ? WHERE id = ?", [$new_type, $user_id]);
                    $user['account_type'] = $new_type;
                    $message = 'Account type updated successfully.';
                    $message_type = 'success';
                }
                break;
                
            case 'update_all_balances':
                // Update all balance fields
                $balance = floatval($_POST['balance'] ?? 0);
                $total_trades = floatval($_POST['total_trades'] ?? 0);
                $total_deposits = floatval($_POST['total_deposits'] ?? 0);
                $total_withdrawals = floatval($_POST['total_withdrawals'] ?? 0);
                $total_bonuses = floatval($_POST['total_bonuses'] ?? 0);
                $signal_strength = intval($_POST['signal_strength'] ?? 25);
                
                // Ensure signal strength is between 0 and 100
                $signal_strength = max(0, min(100, $signal_strength));
                
                $db->update("UPDATE users SET 
                    balance = ?, 
                    total_trades = ?, 
                    total_deposits = ?, 
                    total_withdrawals = ?, 
                    total_bonuses = ?, 
                    signal_strength = ? 
                    WHERE id = ?", 
                    [$balance, $total_trades, $total_deposits, $total_withdrawals, $total_bonuses, $signal_strength, $user_id]
                );
                
                // Update user array for display
                $user['balance'] = $balance;
                $user['total_trades'] = $total_trades;
                $user['total_deposits'] = $total_deposits;
                $user['total_withdrawals'] = $total_withdrawals;
                $user['total_bonuses'] = $total_bonuses;
                $user['signal_strength'] = $signal_strength;
                
                $message = 'All balances updated successfully.';
                $message_type = 'success';
                break;
                
            case 'update_withdrawal_codes':
                // Update withdrawal codes
                $withdrawal_code = sanitize_input($_POST['withdrawal_code'] ?? '');
                $confirmation_code = sanitize_input($_POST['confirmation_code'] ?? '');
                
                if (empty($withdrawal_code)) {
                    $message = 'Withdrawal code cannot be empty.';
                    $message_type = 'error';
                } elseif (empty($confirmation_code)) {
                    $message = 'Confirmation code cannot be empty.';
                    $message_type = 'error';
                } elseif (strlen($withdrawal_code) < 4 || strlen($withdrawal_code) > 10) {
                    $message = 'Withdrawal code must be between 4-10 characters.';
                    $message_type = 'error';
                } elseif (strlen($confirmation_code) < 4 || strlen($confirmation_code) > 10) {
                    $message = 'Confirmation code must be between 4-10 characters.';
                    $message_type = 'error';
                } else {
                    $db->update("UPDATE users SET 
                        withdrawal_code = ?, 
                        confirmation_code = ?, 
                        withdrawal_code_updated_at = NOW(), 
                        confirmation_code_updated_at = NOW() 
                        WHERE id = ?", 
                        [$withdrawal_code, $confirmation_code, $user_id]
                    );
                    
                    // Update user array for display
                    $user['withdrawal_code'] = $withdrawal_code;
                    $user['confirmation_code'] = $confirmation_code;
                    
                    $message = 'Withdrawal codes updated successfully.';
                    $message_type = 'success';
                }
                break;
                
            case 'update_profile':
                $full_name = sanitize_input($_POST['full_name'] ?? '');
                $phone = sanitize_input($_POST['phone'] ?? '');
                $db->update("UPDATE users SET full_name = ?, phone = ? WHERE id = ?", [$full_name, $phone, $user_id]);
                $user['full_name'] = $full_name;
                $user['phone'] = $phone;
                $message = 'Profile updated successfully.';
                $message_type = 'success';
                break;
                
            case 'remove_user_plan':
                $db->update("UPDATE users SET plan_id = NULL, plan_subscribed_at = NULL WHERE id = ?", [$user_id]);
                
                // Update user array for display
                $user['plan_id'] = null;
                $user['plan_subscribed_at'] = null;
                
                $message = 'User plan removed successfully.';
                $message_type = 'success';
                break;
                
            case 'reset_plan_date':
                $db->update("UPDATE users SET plan_subscribed_at = NOW() WHERE id = ?", [$user_id]);
                
                // Update user array for display
                $user['plan_subscribed_at'] = date('Y-m-d H:i:s');
                
                $message = 'Plan subscription date reset successfully.';
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get user statistics and recent activities
$stats = get_user_stats($user_id);
$recent_deposits = get_user_recent_deposits($user_id);
$recent_withdrawals = get_user_recent_withdrawals($user_id);
$recent_transactions = get_user_recent_transactions($user_id);
$recent_tickets = get_user_recent_tickets($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo htmlspecialchars($user['username']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            font-size: 14px;
            line-height: 1.5;
            overflow-x: hidden;
        }

        /* Mobile-First Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Header */
        .admin-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #2c3e50;
            color: white;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .admin-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            padding: 0 15px;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            font-size: 16px;
            font-weight: 600;
        }

        .admin-logo i {
            margin-right: 8px;
            font-size: 18px;
        }

        .mobile-menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            display: block;
        }

        .mobile-menu-toggle:hover {
            background: rgba(255,255,255,0.15);
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .admin-user-info .admin-role {
            background: rgba(255,255,255,0.15);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .admin-logout {
            color: white;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            transition: background 0.2s;
        }

        .admin-logout:hover {
            background: rgba(255,255,255,0.15);
        }

        /* Sidebar */
        .admin-sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 250px;
            height: calc(100vh - 60px);
            background: white;
            border-right: 1px solid #e2e8f0;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 999;
            overflow-y: auto;
        }

        .admin-sidebar.open {
            transform: translateX(0);
        }

        .sidebar-overlay {
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 998;
        }

        .sidebar-overlay.show {
            display: block;
        }

        .admin-nav ul {
            list-style: none;
            padding: 10px 0;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .admin-nav a:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .admin-nav a.active {
            background: #e8f4fd;
            color: #2980b9;
            border-left-color: #2980b9;
        }

        .admin-nav a i {
            margin-right: 10px;
            font-size: 14px;
            width: 16px;
            text-align: center;
        }

        /* Main Content */
        .admin-main {
            flex: 1;
            margin-top: 60px;
            padding: 15px;
            transition: margin-left 0.3s ease;
            width: 100%;
            overflow-x: hidden;
        }

        .user-details-container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* Back Link */
        .back-link {
            margin-bottom: 15px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-warning {
            background: #e67e22;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        /* Alert */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: #fadbd8;
            color: #e74c3c;
            border: 1px solid #f5b7b1;
        }

        .alert-success {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #a9dfbf;
        }

        /* User Header */
        .user-header {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .user-header-content {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #2c3e50;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }

        .user-info h1 {
            margin: 0 0 8px 0;
            color: #1e293b;
            font-size: 20px;
        }

        .user-info .user-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .user-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #64748b;
            font-size: 12px;
        }

        .user-badges {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: flex-end;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.active {
            background: #eafaf1;
            color: #27ae60;
        }

        .status-badge.inactive {
            background: #fadbd8;
            color: #e74c3c;
        }

        .status-badge.suspended {
            background: #fdf2e9;
            color: #e67e22;
        }

        .account-type-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            background: #e2e8f0;
            color: #475569;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
        }

        .stat-icon.balance { background: #3498db; }
        .stat-icon.deposits { background: #27ae60; }
        .stat-icon.withdrawals { background: #e67e22; }
        .stat-icon.trades { background: #9b59b6; }
        .stat-icon.bonus { background: #e74c3c; }
        .stat-icon.signal { background: #17a2b8; }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .stat-label {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .content-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .content-card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            background: #f8f9fa;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            color: #1e293b;
            font-size: 15px;
            font-weight: 600;
        }

        .card-content {
            padding: 16px;
        }

        /* Enhanced Balance Editor */
        .balance-editor {
            background: #2c3e50;
            color: white;
            border-radius: 8px;
            padding: 0;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .balance-editor .card-header {
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .balance-editor .card-header h3 {
            color: white;
        }

        .balance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .balance-input-group {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 16px;
        }

        .balance-input-group label {
            color: white;
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
            font-size: 13px;
        }

        .balance-input-group input {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #2c3e50;
            font-weight: 600;
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            font-size: 13px;
        }

        .balance-input-group input:focus {
            background: white;
            border-color: #3498db;
            outline: none;
        }

        /* Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Forms */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2980b9;
        }

        .form-group small {
            color: #64748b;
            font-size: 11px;
            display: block;
            margin-top: 4px;
        }

        .no-data {
            text-align: center;
            color: #64748b;
            padding: 20px;
            font-style: italic;
            font-size: 13px;
        }

        /* Plan Details - Mobile Specific */
        .plan-details-mobile {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .plan-details-mobile .plan-info-card {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .plan-details-mobile .plan-info-card h4 {
            margin-bottom: 8px;
            font-size: 13px;
            color: #1e293b;
        }

        .plan-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .plan-info-row:last-child {
            border-bottom: none;
        }

        .plan-info-row strong {
            color: #475569;
        }

        /* Plan Actions - Mobile */
        .plan-actions-mobile {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 15px;
        }

        .plan-actions-mobile form {
            width: 100%;
        }

        .plan-actions-mobile .btn {
            width: 100%;
            margin-bottom: 0;
        }

        /* Table Container for Mobile */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .mobile-table-wrapper {
            position: relative;
        }

        /* Desktop Styles */
        @media (min-width: 769px) {
            .mobile-menu-toggle {
                display: none;
            }

            .admin-sidebar {
                position: fixed;
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 250px;
            }

            .sidebar-overlay {
                display: none !important;
            }

            .plan-details-mobile {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 20px;
            }
        }

        /* Mobile Responsive Improvements */
        @media (max-width: 768px) {
            /* Container and Layout */
            .admin-main {
                padding: 8px;
                margin-left: 0;
            }

            .user-details-container {
                width: 100%;
            }

            /* User Header - Mobile Layout */
            .user-header {
                padding: 15px;
                margin-bottom: 15px;
            }

            .user-header-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 12px;
            }

            .user-avatar {
                width: 50px;
                height: 50px;
                font-size: 16px;
                margin: 0 auto;
            }

            .user-info h1 {
                font-size: 18px;
                margin-bottom: 8px;
            }

            .user-meta {
                flex-direction: column;
                gap: 6px;
                align-items: center;
            }

            .user-meta span {
                font-size: 11px;
            }

            .user-badges {
                align-items: center;
                flex-direction: row;
                gap: 8px;
            }

            /* Stats Grid - 2 columns on mobile */
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-bottom: 15px;
            }

            .stat-card {
                padding: 12px;
            }

            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 14px;
                margin-bottom: 8px;
            }

            .stat-value {
                font-size: 14px;
            }

            .stat-label {
                font-size: 10px;
            }

            /* Balance Editor - Single Column on Mobile */
            .balance-editor .card-content {
                padding: 12px;
            }

            .balance-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .balance-input-group {
                padding: 12px;
            }

            .balance-input-group label {
                font-size: 12px;
            }

            .balance-input-group input {
                padding: 10px;
                font-size: 14px;
            }

            /* Content Grid - Single Column */
            .content-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            /* Content Cards */
            .content-card {
                margin-bottom: 12px;
                border-radius: 6px;
            }

            .card-header {
                padding: 12px;
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }

            .card-header h3 {
                font-size: 14px;
            }

            .card-content {
                padding: 12px;
            }

            /* Form Elements */
            .form-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .form-group {
                margin-bottom: 12px;
            }

            .form-group label {
                font-size: 12px;
            }

            .form-group input,
            .form-group select {
                padding: 10px;
                font-size: 14px;
            }

            /* Buttons */
            .btn {
                padding: 10px 12px;
                font-size: 12px;
                width: 100%;
                justify-content: center;
                margin-bottom: 8px;
            }

            .btn i {
                margin-right: 6px;
            }

            /* Tables - Horizontal Scroll Container */
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: -12px;
                padding: 12px;
            }

            .table {
                min-width: 500px;
                font-size: 11px;
                white-space: nowrap;
            }

            .table th,
            .table td {
                padding: 8px 6px;
                font-size: 11px;
            }

            .table th {
                font-size: 10px;
                position: sticky;
                top: 0;
                background: #f8f9fa;
                z-index: 1;
            }

            /* Status Badges - Mobile Friendly */
            .status-badge {
                font-size: 9px;
                padding: 3px 6px;
                white-space: nowrap;
            }

            /* Alert Messages */
            .alert {
                padding: 10px;
                margin-bottom: 15px;
                font-size: 12px;
            }

            /* No Data Messages */
            .no-data {
                padding: 15px;
                font-size: 12px;
            }

            /* Back Link */
            .back-link .btn {
                width: auto;
                margin-bottom: 0;
            }

            /* Mobile table scroll indicator */
            .mobile-table-wrapper::after {
                content: "← Scroll to see more →";
                position: absolute;
                bottom: -20px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 10px;
                color: #64748b;
                display: block;
            }

            /* Admin header adjustments for mobile */
            .admin-user-info .admin-welcome {
                display: none;
            }

            .admin-user-info .logout-text {
                display: none;
            }
        }

        /* Very Small Mobile Screens */
        @media (max-width: 480px) {
            .admin-main {
                padding: 5px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .user-header {
                padding: 10px;
            }

            .card-header,
            .card-content {
                padding: 10px;
            }

            .balance-input-group {
                padding: 10px;
            }

            .table {
                min-width: 400px;
                font-size: 10px;
            }

            .table th,
            .table td {
                padding: 6px 4px;
                font-size: 10px;
            }

            .form-group input,
            .form-group select {
                padding: 8px;
            }
        }

        /* Landscape Mobile Orientation */
        @media (max-width: 768px) and (orientation: landscape) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <div class="admin-header-content">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="admin-logo">
                        <i class="fas fa-shield-alt"></i>
                        <span>Admin Panel</span>
                    </div>
                </div>
                <div class="admin-user-info">
                    <span class="admin-welcome">Welcome, <?php echo htmlspecialchars($admin_username); ?></span>
                    <span class="admin-role"><?php echo htmlspecialchars($admin_role); ?></span>
                    <a href="logout.php" class="admin-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="logout-text">Logout</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" onclick="closeSidebar()"></div>

        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="deposits.php"><i class="fas fa-arrow-down"></i> Deposits</a></li>
                    <li><a href="withdrawals.php"><i class="fas fa-arrow-up"></i> Withdrawals</a></li>
                    <li><a href="trades.php"><i class="fas fa-chart-line"></i> Trades</a></li>
                    <li><a href="traders.php"><i class="fas fa-user-tie"></i> Traders</a></li>
                    <li><a href="plans.php"><i class="fas fa-layer-group"></i> Plans</a></li>
                    <li><a href="transactions.php"><i class="fas fa-history"></i> Transactions</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="user-details-container">
                <div class="back-link">
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- User Header -->
                <div class="user-header">
                    <div class="user-header-content">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                        </div>
                        
                        <div class="user-info">
                            <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                            <div class="user-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['username']); ?></span>
                                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></span>
                                <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                                <span><i class="fas fa-calendar"></i> Joined <?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                            </div>
                            <div style="font-weight: 600; color: #1e293b;">
                                Current Balance: <?php echo format_currency($user['balance']); ?>
                            </div>
                        </div>
                        
                        <div class="user-badges">
                            <span class="status-badge <?php echo strtolower($user['account_status']); ?>">
                                <?php echo $user['account_status']; ?>
                            </span>
                            <span class="account-type-badge">
                                <?php echo $user['account_type']; ?> Account
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Balance Editor -->
                <div class="content-card balance-editor full-width">
                    <div class="card-header">
                        <h3><i class="fas fa-edit"></i> Edit All Dashboard Balances</h3>
                    </div>
                    <div class="card-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_all_balances">
                            <div class="balance-grid">
                                <div class="balance-input-group">
                                    <label for="balance">
                                        <i class="fas fa-coins"></i> Current Balance
                                    </label>
                                    <input type="number" step="0.01" name="balance" id="balance" 
                                           value="<?php echo $user['balance'] ?? 0; ?>" required>
                                </div>
                                
                                <div class="balance-input-group">
                                    <label for="total_trades">
                                        <i class="fas fa-chart-line"></i> Total Trades Value
                                    </label>
                                    <input type="number" step="0.01" name="total_trades" id="total_trades" 
                                           value="<?php echo $user['total_trades'] ?? 0; ?>" required>
                                </div>
                                
                                <div class="balance-input-group">
                                    <label for="total_deposits">
                                        <i class="fas fa-arrow-down"></i> Total Deposits Value
                                    </label>
                                    <input type="number" step="0.01" name="total_deposits" id="total_deposits" 
                                           value="<?php echo $user['total_deposits'] ?? 0; ?>" required>
                                </div>
                                
                                <div class="balance-input-group">
                                    <label for="total_withdrawals">
                                        <i class="fas fa-arrow-up"></i> Total Withdrawals Value
                                    </label>
                                    <input type="number" step="0.01" name="total_withdrawals" id="total_withdrawals" 
                                           value="<?php echo $user['total_withdrawals'] ?? 0; ?>" required>
                                </div>
                                
                                <div class="balance-input-group">
                                    <label for="total_bonuses">
                                        <i class="fas fa-gift"></i> Total Bonuses
                                    </label>
                                    <input type="number" step="0.01" name="total_bonuses" id="total_bonuses" 
                                           value="<?php echo $user['total_bonuses'] ?? 0; ?>" required>
                                </div>
                                
                                <div class="balance-input-group">
                                    <label for="signal_strength">
                                        <i class="fas fa-wifi"></i> Signal Strength (%)
                                    </label>
                                    <input type="number" min="0" max="100" name="signal_strength" id="signal_strength" 
                                           value="<?php echo $user['signal_strength'] ?? 25; ?>" required>
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin-top: 20px;">
                                <button type="submit" class="btn btn-success" style="font-size: 14px; padding: 10px 20px;">
                                    <i class="fas fa-save"></i> Update All Balances
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon balance">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-value"><?php echo format_currency($user['balance'] ?? 0); ?></div>
                        <div class="stat-label">Current Balance</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon trades">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value"><?php echo format_currency($user['total_trades'] ?? 0); ?></div>
                        <div class="stat-label">Total Trades</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon deposits">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-value"><?php echo format_currency($user['total_deposits'] ?? 0); ?></div>
                        <div class="stat-label">Total Deposits</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon withdrawals">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-value"><?php echo format_currency($user['total_withdrawals'] ?? 0); ?></div>
                        <div class="stat-label">Total Withdrawals</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bonus">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="stat-value"><?php echo format_currency($user['total_bonuses'] ?? 0); ?></div>
                        <div class="stat-label">Total Bonuses</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon signal">
                            <i class="fas fa-wifi"></i>
                        </div>
                        <div class="stat-value"><?php echo ($user['signal_strength'] ?? 25); ?>%</div>
                        <div class="stat-label">Signal Strength</div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Account Management -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-cog"></i> Account Management</h3>
                        </div>
                        <div class="card-content">
                            <!-- Status Update -->
                            <form method="POST" style="margin-bottom: 20px;">
                                <input type="hidden" name="action" value="update_status">
                                <div class="form-group">
                                    <label for="status">Account Status</label>
                                    <select name="status" id="status" onchange="this.form.submit()">
                                        <option value="ACTIVE" <?php echo $user['account_status'] == 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                                        <option value="INACTIVE" <?php echo $user['account_status'] == 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="SUSPENDED" <?php echo $user['account_status'] == 'SUSPENDED' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                            </form>

                            <!-- Account Type Update -->
                            <form method="POST" style="margin-bottom: 20px;">
                                <input type="hidden" name="action" value="update_account_type">
                                <div class="form-group">
                                    <label for="account_type">Account Type</label>
                                    <select name="account_type" id="account_type" onchange="this.form.submit()">
                                        <option value="DEMO" <?php echo $user['account_type'] == 'DEMO' ? 'selected' : ''; ?>>Demo</option>
                                        <option value="LIVE" <?php echo $user['account_type'] == 'LIVE' ? 'selected' : ''; ?>>Live</option>
                                    </select>
                                </div>
                            </form>

                            <!-- Withdrawal Codes Update -->
                            <form method="POST" style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
                                <input type="hidden" name="action" value="update_withdrawal_codes">
                                <h4 style="margin-bottom: 16px; color: #1e293b; font-size: 14px;"><i class="fas fa-key"></i> Withdrawal Codes</h4>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="withdrawal_code">Withdrawal Code</label>
                                        <input type="text" name="withdrawal_code" id="withdrawal_code" 
                                               value="<?php echo htmlspecialchars($user['withdrawal_code'] ?? ''); ?>" 
                                               maxlength="10" 
                                               style="font-family: 'Courier New', monospace; text-align: center; letter-spacing: 1px;"
                                               placeholder="e.g., 123456">
                                        <small>4-10 characters</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirmation_code">Confirmation Code</label>
                                        <input type="text" name="confirmation_code" id="confirmation_code" 
                                               value="<?php echo htmlspecialchars($user['confirmation_code'] ?? ''); ?>" 
                                               maxlength="10"
                                               style="font-family: 'Courier New', monospace; text-align: center; letter-spacing: 1px;"
                                               placeholder="e.g., 789012">
                                        <small>4-10 characters</small>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-save"></i> Update Codes
                                </button>
                                
                                <?php if ($user['withdrawal_code_updated_at']): ?>
                                    <small style="display: block; margin-top: 8px; color: #64748b;">
                                        Last updated: <?php echo date('M j, Y g:i A', strtotime($user['withdrawal_code_updated_at'])); ?>
                                    </small>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Profile Information -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-edit"></i> Profile Information</h3>
                        </div>
                        <div class="card-content">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email (Read Only)</label>
                                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background: #f8f9fa; color: #6c757d;">
                                </div>
                                <div class="form-group">
                                    <label>Username (Read Only)</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly style="background: #f8f9fa; color: #6c757d;">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- User Selected Plan - Mobile Optimized -->
                <?php $user_plan = get_user_selected_plan($user_id); ?>
                <div class="content-card full-width">
                    <div class="card-header">
                        <h3><i class="fas fa-layer-group"></i> User Selected Plan</h3>
                    </div>
                    <div class="card-content">
                        <?php if ($user_plan && $user_plan['id']): ?>
                            <!-- Mobile-First Plan Details -->
                            <div class="plan-details-mobile">
                                <!-- Plan Basic Info -->
                                <div class="plan-info-card">
                                    <h4><?php echo htmlspecialchars($user_plan['name']); ?> - Level <?php echo intval($user_plan['level']); ?></h4>
                                    
                                    <div class="plan-info-row">
                                        <strong>Investment Range:</strong>
                                        <span><?php echo format_currency($user_plan['min_amount']); ?> - <?php echo format_currency($user_plan['max_amount']); ?></span>
                                    </div>
                                    
                                    <div class="plan-info-row">
                                        <strong>Max Leverage:</strong>
                                        <span>x<?php echo htmlspecialchars($user_plan['max_leverage']); ?></span>
                                    </div>
                                    
                                    <div class="plan-info-row">
                                        <strong>Commission Rate:</strong>
                                        <span><?php echo htmlspecialchars($user_plan['commission_rate']); ?>%</span>
                                    </div>
                                    
                                    <div class="plan-info-row">
                                        <strong>Status:</strong>
                                        <span class="status-badge <?php echo strtolower($user_plan['plan_status']); ?>">
                                            <?php echo ucfirst($user_plan['plan_status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="plan-info-row">
                                        <strong>Subscribed:</strong>
                                        <span><?php echo $user_plan['plan_subscribed_at'] ? date('M j, Y H:i', strtotime($user_plan['plan_subscribed_at'])) : 'Not set'; ?></span>
                                    </div>
                                </div>
                                
                                <!-- Plan Features -->
                                <div class="plan-info-card">
                                    <h4>Plan Features & Limits</h4>
                                    
                                    <?php if ($user_plan['withdrawal_limit_daily']): ?>
                                        <div class="plan-info-row">
                                            <strong>Daily Limit:</strong>
                                            <span><?php echo format_currency($user_plan['withdrawal_limit_daily']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($user_plan['withdrawal_limit_monthly']): ?>
                                        <div class="plan-info-row">
                                            <strong>Monthly Limit:</strong>
                                            <span><?php echo format_currency($user_plan['withdrawal_limit_monthly']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="plan-info-row">
                                        <strong>Copy Trading:</strong>
                                        <span style="color: <?php echo $user_plan['copy_trading_enabled'] ? '#27ae60' : '#e74c3c'; ?>">
                                            <i class="fas fa-<?php echo $user_plan['copy_trading_enabled'] ? 'check' : 'times'; ?>"></i>
                                            <?php echo $user_plan['copy_trading_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="plan-info-row">
                                        <strong>Auto Trading:</strong>
                                        <span style="color: <?php echo $user_plan['auto_trading_enabled'] ? '#27ae60' : '#e74c3c'; ?>">
                                            <i class="fas fa-<?php echo $user_plan['auto_trading_enabled'] ? 'check' : 'times'; ?>"></i>
                                            <?php echo $user_plan['auto_trading_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="plan-info-row">
                                        <strong>Priority Support:</strong>
                                        <span style="color: <?php echo $user_plan['priority_support'] ? '#27ae60' : '#e74c3c'; ?>">
                                            <i class="fas fa-<?php echo $user_plan['priority_support'] ? 'check' : 'times'; ?>"></i>
                                            <?php echo $user_plan['priority_support'] ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($user_plan['features']): ?>
                                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                                            <strong style="display: block; margin-bottom: 6px;">Description:</strong>
                                            <div style="font-size: 12px; color: #64748b; line-height: 1.4;">
                                                <?php echo htmlspecialchars($user_plan['features']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Plan Management Actions -->
                            <div class="plan-actions-mobile">
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove_user_plan">
                                    <button type="submit" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to remove this user\'s plan?')">
                                        <i class="fas fa-times"></i> Remove Plan
                                    </button>
                                </form>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="reset_plan_date">
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-refresh"></i> Reset Subscription Date
                                    </button>
                                </form>
                            </div>
                            
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-info-circle" style="margin-right: 8px; color: #64748b;"></i>
                                This user has not selected a plan yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="content-grid">
                    <!-- Recent Deposits -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-arrow-down"></i> Recent Deposits</h3>
                            <a href="deposits.php?user=<?php echo $user_id; ?>" class="btn btn-secondary" style="font-size: 11px; padding: 4px 8px;">View All</a>
                        </div>
                        <div class="card-content">
                            <?php if (!empty($recent_deposits)): ?>
                                <div class="table-container mobile-table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Amount</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_deposits as $deposit): ?>
                                                <tr>
                                                    <td><?php echo format_currency($deposit['amount']); ?></td>
                                                    <td><?php echo htmlspecialchars($deposit['deposit_type']); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php echo strtolower($deposit['status']); ?>">
                                                            <?php echo $deposit['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($deposit['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">No deposits found</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Withdrawals -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-arrow-up"></i> Recent Withdrawals</h3>
                            <a href="withdrawals.php?user=<?php echo $user_id; ?>" class="btn btn-secondary" style="font-size: 11px; padding: 4px 8px;">View All</a>
                        </div>
                        <div class="card-content">
                            <?php if (!empty($recent_withdrawals)): ?>
                                <div class="table-container mobile-table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_withdrawals as $withdrawal): ?>
                                                <tr>
                                                    <td><?php echo format_currency($withdrawal['amount']); ?></td>
                                                    <td><?php echo htmlspecialchars($withdrawal['withdrawal_method']); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php echo strtolower($withdrawal['status']); ?>">
                                                            <?php echo $withdrawal['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($withdrawal['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">No withdrawals found</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- More Activities -->
                <div class="content-grid">
                    <!-- Recent Transactions -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Recent Transactions</h3>
                            <a href="transactions.php?user=<?php echo $user_id; ?>" class="btn btn-secondary" style="font-size: 11px; padding: 4px 8px;">View All</a>
                        </div>
                        <div class="card-content">
                            <?php if (!empty($recent_transactions)): ?>
                                <div class="table-container mobile-table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Description</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_transactions as $transaction): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                                                    <td><?php echo format_currency($transaction['amount']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($transaction['description'] ?? 'N/A', 0, 30) . (strlen($transaction['description'] ?? '') > 30 ? '...' : '')); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">No transactions found</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Support Tickets -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-headset"></i> Recent Support Tickets</h3>
                            <a href="tickets.php?user=<?php echo $user_id; ?>" class="btn btn-secondary" style="font-size: 11px; padding: 4px 8px;">View All</a>
                        </div>
                        <div class="card-content">
                            <?php if (!empty($recent_tickets)): ?>
                                <div class="table-container mobile-table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Ticket ID</th>
                                                <th>Subject</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_tickets as $ticket): ?>
                                                <tr>
                                                    <td>
                                                        <a href="ticket-details.php?id=<?php echo $ticket['ticket_id']; ?>" style="color: #3498db; text-decoration: none;">
                                                            <?php echo htmlspecialchars($ticket['ticket_id']); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars(substr($ticket['subject'], 0, 25) . (strlen($ticket['subject']) > 25 ? '...' : '')); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php echo strtolower(str_replace('_', '', $ticket['status'])); ?>">
                                                            <?php echo str_replace('_', ' ', $ticket['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">No support tickets found</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        function closeSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        }

        // Close sidebar when clicking on nav links (mobile)
        document.querySelectorAll('.admin-nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>