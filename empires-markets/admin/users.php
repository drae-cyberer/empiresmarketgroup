<?php
// Admin Users Management
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

// Handle user actions
$message = '';
$message_type = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    if ($action && $user_id) {
        try {
            switch ($action) {
                case 'activate':
                    $db->update("UPDATE users SET account_status = 'ACTIVE' WHERE id = ?", [$user_id]);
                    $message = 'User activated successfully.';
                    $message_type = 'success';
                    break;
                    
                case 'suspend':
                    $db->update("UPDATE users SET account_status = 'SUSPENDED' WHERE id = ?", [$user_id]);
                    $message = 'User suspended successfully.';
                    $message_type = 'success';
                    break;
                    
                case 'deactivate':
                    $db->update("UPDATE users SET account_status = 'INACTIVE' WHERE id = ?", [$user_id]);
                    $message = 'User deactivated successfully.';
                    $message_type = 'success';
                    break;
                    
                case 'update_balance':
                    $new_balance = floatval($_POST['new_balance'] ?? 0);
                    $db->update("UPDATE users SET balance = ? WHERE id = ?", [$new_balance, $user_id]);
                    $message = 'User balance updated successfully.';
                    $message_type = 'success';
                    break;
                    
                case 'credit_balance':
                    $credit_amount = floatval($_POST['credit_amount'] ?? 0);
                    $description = sanitize_input($_POST['description'] ?? 'Admin credit');
                    if ($credit_amount > 0) {
                        $user = get_user_by_id($user_id);
                        $new_balance = $user['balance'] + $credit_amount;
                        $db->update("UPDATE users SET balance = ? WHERE id = ?", [$new_balance, $user_id]);
                        
                        // Create transaction record
                        $transaction_id = generate_transaction_id('CREDIT');
                        $db->insert(
                            "INSERT INTO transactions (user_id, transaction_id, type, amount, description, status) VALUES (?, ?, 'CREDIT', ?, ?, 'COMPLETED')",
                            [$user_id, $transaction_id, $credit_amount, $description]
                        );
                        
                        $message = "Successfully credited " . format_currency($credit_amount) . " to user account.";
                        $message_type = 'success';
                    } else {
                        $message = 'Credit amount must be greater than 0.';
                        $message_type = 'error';
                    }
                    break;
                    
                case 'debit_balance':
                    $debit_amount = floatval($_POST['debit_amount'] ?? 0);
                    $description = sanitize_input($_POST['description'] ?? 'Admin debit');
                    if ($debit_amount > 0) {
                        $user = get_user_by_id($user_id);
                        if ($user['balance'] >= $debit_amount) {
                            $new_balance = $user['balance'] - $debit_amount;
                            $db->update("UPDATE users SET balance = ? WHERE id = ?", [$new_balance, $user_id]);
                            
                            // Create transaction record
                            $transaction_id = generate_transaction_id('DEBIT');
                            $db->insert(
                                "INSERT INTO transactions (user_id, transaction_id, type, amount, description, status) VALUES (?, ?, 'DEBIT', ?, ?, 'COMPLETED')",
                                [$user_id, $transaction_id, $debit_amount, $description]
                            );
                            
                            $message = "Successfully debited " . format_currency($debit_amount) . " from user account.";
                            $message_type = 'success';
                        } else {
                            $message = 'Insufficient balance. User has ' . format_currency($user['balance']) . ' available.';
                            $message_type = 'error';
                        }
                    } else {
                        $message = 'Debit amount must be greater than 0.';
                        $message_type = 'error';
                    }
                    break;
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get users with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12; // Increased for better grid layout
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter) {
    $where_conditions[] = "account_status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Get total count
    $total_users = $db->count("SELECT COUNT(*) FROM users $where_clause", $params);
    
    // Get users
    $users = $db->select("
        SELECT * FROM users 
        $where_clause 
        ORDER BY created_at DESC 
        LIMIT $per_page OFFSET $offset
    ", $params);
    
    $total_pages = ceil($total_users / $per_page);
    
} catch (Exception $e) {
    $error_message = 'Error loading users: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - <?php echo SITE_NAME; ?></title>
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
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
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
        }

        .admin-page-header {
            margin-bottom: 20px;
        }

        .admin-page-header h1 {
            font-size: 22px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .admin-page-header p {
            color: #64748b;
            font-size: 13px;
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

        /* Filters */
        .admin-filters {
            background: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
            width: 160px;
            transition: border-color 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #2980b9;
        }

        /* Users Container */
        .users-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .users-container h3 {
            margin: 0 0 20px 0;
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .user-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            background: #fafbfc;
            transition: all 0.3s ease;
        }

        .user-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .user-info h3 {
            margin: 0 0 4px 0;
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
        }

        .user-email {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .user-id {
            color: #94a3b8;
            font-size: 11px;
        }

        .user-status {
            text-align: right;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
            display: inline-block;
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

        .account-type {
            font-size: 11px;
            color: #6c757d;
        }

        .user-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .detail-item {
            background: white;
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #3498db;
        }

        .detail-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .detail-value {
            font-size: 13px;
            color: #1e293b;
            font-weight: 600;
        }

        .balance-item {
            border-left-color: #27ae60;
        }

        .joined-item {
            border-left-color: #9b59b6;
        }

        /* Action Buttons */
        .user-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 12px;
        }

        .btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-warning {
            background: #e67e22;
            color: white;
        }

        .btn-primary {
            background: #3498db;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        /* Pagination */
        .admin-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 25px;
            padding: 16px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .pagination-info {
            color: #64748b;
            font-size: 13px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #1e293b;
            font-size: 16px;
        }

        .modal-close {
            font-size: 20px;
            cursor: pointer;
            color: #adb5bd;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: #e74c3c;
        }

        .form-group {
            margin: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #495057;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 13px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2980b9;
            box-shadow: 0 0 0 2px rgba(41,128,185,0.25);
        }

        .modal-actions {
            padding: 16px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 12px 12px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .no-users {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }

        .no-users i {
            font-size: 36px;
            margin-bottom: 16px;
            color: #cbd5e1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .admin-user-info .admin-welcome {
                display: none;
            }

            .users-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .user-details {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .admin-main {
                padding: 10px;
            }

            .admin-page-header h1 {
                font-size: 20px;
            }

            .filter-form {
                gap: 8px;
            }

            .filter-group input,
            .filter-group select {
                width: 140px;
            }

            .user-actions {
                gap: 4px;
            }

            .btn {
                padding: 4px 8px;
                font-size: 9px;
            }
        }

        @media (max-width: 480px) {
            .user-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .user-status {
                text-align: left;
            }

            .admin-pagination {
                flex-direction: column;
                gap: 10px;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group input,
            .filter-group select {
                width: 100%;
            }
        }

        @media (min-width: 769px) {
            .admin-sidebar {
                position: fixed;
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 250px;
            }

            .mobile-menu-toggle {
                display: none;
            }

            .sidebar-overlay {
                display: none !important;
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
            <div class="admin-content">
                <div class="admin-page-header">
                    <h1>Users Management</h1>
                    <p>Manage user accounts and settings</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="admin-filters">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Search users..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="ACTIVE" <?php echo $status_filter == 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                                <option value="INACTIVE" <?php echo $status_filter == 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="SUSPENDED" <?php echo $status_filter == 'SUSPENDED' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </form>
                </div>

                <!-- Users Container -->
                <div class="users-container">
                    <h3>
                        <i class="fas fa-users"></i> 
                        Users (<?php echo number_format($total_users); ?> total)
                    </h3>

                    <?php if ($users): ?>
                        <div class="users-grid">
                            <?php foreach ($users as $user): ?>
                                <div class="user-card">
                                    <div class="user-header">
                                        <div class="user-info">
                                            <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                            <div class="user-id">ID: <?php echo $user['id']; ?></div>
                                        </div>
                                        <div class="user-status">
                                            <span class="status-badge <?php echo strtolower($user['account_status']); ?>">
                                                <?php echo $user['account_status']; ?>
                                            </span>
                                            <div class="account-type"><?php echo $user['account_type']; ?> Account</div>
                                        </div>
                                    </div>

                                    <div class="user-details">
                                        <div class="detail-item">
                                            <div class="detail-label">Full Name</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Password</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($user['password']); ?></div>
                                        </div>
                                        <div class="detail-item balance-item">
                                            <div class="detail-label">Balance</div>
                                            <div class="detail-value"><?php echo format_currency($user['balance']); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Phone</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></div>
                                        </div>
                                        <div class="detail-item joined-item">
                                            <div class="detail-label">Joined</div>
                                            <div class="detail-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                        </div>
                                    </div>

                                    <div class="user-actions">
                                        <?php if ($user['account_status'] != 'ACTIVE'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-success" title="Activate User">
                                                    <i class="fas fa-check"></i> Activate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['account_status'] != 'SUSPENDED'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="suspend">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-warning" title="Suspend User" onclick="return confirm('Suspend this user?')">
                                                    <i class="fas fa-pause"></i> Suspend
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-primary" onclick="editBalance(<?php echo $user['id']; ?>, <?php echo $user['balance']; ?>)" title="Edit Balance">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        
                                        <button class="btn btn-success" onclick="creditBalance(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Credit Balance">
                                            <i class="fas fa-plus"></i> Credit
                                        </button>
                                        
                                        <button class="btn btn-danger" onclick="debitBalance(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['balance']; ?>)" title="Debit Balance">
                                            <i class="fas fa-minus"></i> Debit
                                        </button>
                                        
                                        <a href="user-details.php?id=<?php echo $user['id']; ?>" class="btn btn-info" title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-users">
                            <i class="fas fa-users"></i>
                            <h3>No users found</h3>
                            <p>Try adjusting your search criteria or check back later.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="admin-pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <span class="pagination-info">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                            (<?php echo number_format($total_users); ?> total users)
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-secondary">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Balance Edit Modal -->
    <div id="balanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User Balance</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="balanceForm">
                <input type="hidden" name="action" value="update_balance">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="form-group">
                    <label for="new_balance">New Balance:</label>
                    <input type="number" step="0.01" name="new_balance" id="new_balance" required>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Update Balance</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Credit Balance Modal -->
    <div id="creditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Credit User Balance</h3>
                <span class="modal-close" onclick="closeCreditModal()">&times;</span>
            </div>
            <form method="POST" id="creditForm">
                <input type="hidden" name="action" value="credit_balance">
                <input type="hidden" name="user_id" id="creditUserId">
                <div class="form-group">
                    <label>User: <strong><span id="creditUsername"></span></strong></label>
                </div>
                <div class="form-group">
                    <label for="credit_amount">Credit Amount:</label>
                    <input type="number" step="0.01" name="credit_amount" id="credit_amount" required min="0.01">
                </div>
                <div class="form-group">
                    <label for="credit_description">Description:</label>
                    <input type="text" name="description" id="credit_description" value="Admin credit" required>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Credit Balance</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCreditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Debit Balance Modal -->
    <div id="debitModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Debit User Balance</h3>
                <span class="modal-close" onclick="closeDebitModal()">&times;</span>
            </div>
            <form method="POST" id="debitForm">
                <input type="hidden" name="action" value="debit_balance">
                <input type="hidden" name="user_id" id="debitUserId">
                <div class="form-group">
                    <label>User: <strong><span id="debitUsername"></span></strong></label>
                    <label>Current Balance: <strong><span id="debitCurrentBalance"></span></strong></label>
                </div>
                <div class="form-group">
                    <label for="debit_amount">Debit Amount:</label>
                    <input type="number" step="0.01" name="debit_amount" id="debit_amount" required min="0.01">
                </div>
                <div class="form-group">
                    <label for="debit_description">Description:</label>
                    <input type="text" name="description" id="debit_description" value="Admin debit" required>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-danger">Debit Balance</button>
                    <button type="button" class="btn btn-secondary" onclick="closeDebitModal()">Cancel</button>
                </div>
            </form>
        </div>
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

        function editBalance(userId, currentBalance) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('new_balance').value = currentBalance;
            document.getElementById('balanceModal').style.display = 'block';
        }

        function creditBalance(userId, username) {
            document.getElementById('creditUserId').value = userId;
            document.getElementById('creditUsername').textContent = username;
            document.getElementById('credit_amount').value = '';
            document.getElementById('credit_description').value = 'Admin credit';
            document.getElementById('creditModal').style.display = 'block';
        }

        function debitBalance(userId, username, currentBalance) {
            document.getElementById('debitUserId').value = userId;
            document.getElementById('debitUsername').textContent = username;
            document.getElementById('debitCurrentBalance').textContent = '$' + parseFloat(currentBalance).toFixed(2);
            document.getElementById('debit_amount').value = '';
            document.getElementById('debit_amount').max = currentBalance;
            document.getElementById('debit_description').value = 'Admin debit';
            document.getElementById('debitModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('balanceModal').style.display = 'none';
        }

        function closeCreditModal() {
            document.getElementById('creditModal').style.display = 'none';
        }

        function closeDebitModal() {
            document.getElementById('debitModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['balanceModal', 'creditModal', 'debitModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Validation for debit amount
        document.getElementById('debit_amount').addEventListener('input', function() {
            const maxAmount = parseFloat(this.max);
            const currentAmount = parseFloat(this.value);
            
            if (currentAmount > maxAmount) {
                this.setCustomValidity('Amount cannot exceed current balance');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>