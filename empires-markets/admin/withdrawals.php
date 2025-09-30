<?php
// Admin Withdrawals Management
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

// Handle withdrawal actions
$message = '';
$message_type = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    $withdrawal_id = $_POST['withdrawal_id'] ?? '';
    
    if ($action && $withdrawal_id) {
        try {
            switch ($action) {
                case 'approve':
                    $admin_note = $_POST['admin_note'] ?? '';
                    
                    // Get withdrawal details
                    $withdrawal = $db->selectOne("SELECT * FROM withdrawals WHERE id = ?", [$withdrawal_id]);
                    
                    if ($withdrawal && $withdrawal['status'] == 'PENDING') {
                        // Start transaction
                        $db->beginTransaction();
                        
                        // Update withdrawal status
                        $db->update("UPDATE withdrawals SET status = 'APPROVED', admin_note = ?, updated_at = NOW() WHERE id = ?", 
                                   [$admin_note, $withdrawal_id]);
                        
                        // Update user balance and total withdrawals
                        $db->update("UPDATE users SET total_withdrawals = total_withdrawals + ? WHERE id = ?", 
                                   [$withdrawal['amount'], $withdrawal['user_id']]);
                        
                        // Add transaction record
                        $db->insert("INSERT INTO transactions (user_id, transaction_id, type, amount, description, status, created_at) VALUES (?, ?, 'WITHDRAWAL', ?, ?, 'COMPLETED', NOW())", 
                                   [$withdrawal['user_id'], $withdrawal['transaction_id'], $withdrawal['amount'], 'Withdrawal approved by admin']);
                        
                        $db->commit();
                        $message = 'Withdrawal approved successfully.';
                        $message_type = 'success';
                    } else {
                        $message = 'Invalid withdrawal or already processed.';
                        $message_type = 'error';
                    }
                    break;
                    
                case 'reject':
                    $admin_note = $_POST['admin_note'] ?? '';
                    
                    // Get withdrawal details
                    $withdrawal = $db->selectOne("SELECT * FROM withdrawals WHERE id = ?", [$withdrawal_id]);
                    
                    if ($withdrawal && $withdrawal['status'] == 'PENDING') {
                        // Start transaction
                        $db->beginTransaction();
                        
                        // Update withdrawal status
                        $db->update("UPDATE withdrawals SET status = 'REJECTED', admin_note = ?, updated_at = NOW() WHERE id = ?", 
                                   [$admin_note, $withdrawal_id]);
                        
                        // Refund user balance (since it was deducted when withdrawal was requested)
                        $db->update("UPDATE users SET balance = balance + ? WHERE id = ?", 
                                   [$withdrawal['amount'], $withdrawal['user_id']]);
                        
                        $db->commit();
                        $message = 'Withdrawal rejected and amount refunded to user.';
                        $message_type = 'success';
                    } else {
                        $message = 'Invalid withdrawal or already processed.';
                        $message_type = 'error';
                    }
                    break;
            }
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get withdrawals with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "w.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR w.transaction_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Get total count
    $total_withdrawals = $db->count("SELECT COUNT(*) FROM withdrawals w JOIN users u ON w.user_id = u.id $where_clause", $params);
    
    // Get withdrawals
    $withdrawals = $db->select("
        SELECT w.*, u.username, u.email 
        FROM withdrawals w 
        JOIN users u ON w.user_id = u.id 
        $where_clause 
        ORDER BY w.created_at DESC 
        LIMIT $per_page OFFSET $offset
    ", $params);
    
    $total_pages = ceil($total_withdrawals / $per_page);
    
} catch (Exception $e) {
    $error_message = 'Error loading withdrawals: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawals Management - <?php echo SITE_NAME; ?></title>
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
            width: 200px;
            transition: border-color 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #2980b9;
        }

        /* Buttons */
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

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #27ae60;
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

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        /* Table Container */
        .admin-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 20px;
        }

        /* Desktop Table */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .admin-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
        }

        .admin-table tbody tr:hover {
            background: #f8f9fa;
        }

        .admin-table .text-center {
            text-align: center;
            padding: 40px;
            color: #64748b;
            font-style: italic;
        }

        .admin-table .text-muted {
            color: #6c757d;
            font-size: 12px;
        }

        .wallet-address {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
        }

        /* Mobile Cards */
        .mobile-cards {
            display: none;
        }

        .withdrawal-card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .withdrawal-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .withdrawal-info h4 {
            margin: 0 0 4px 0;
            color: #1e293b;
            font-size: 15px;
        }

        .withdrawal-info .user-email {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .withdrawal-info .transaction-id {
            color: #94a3b8;
            font-size: 11px;
        }

        .withdrawal-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
        }

        .detail-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .detail-value {
            font-size: 13px;
            color: #1e293b;
            font-weight: 600;
        }

        .wallet-detail {
            grid-column: 1 / -1;
        }

        .wallet-detail .detail-value {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 4px 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            word-break: break-all;
        }

        .withdrawal-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-badge.pending {
            background: #fdf2e9;
            color: #e67e22;
        }

        .status-badge.approved {
            background: #eafaf1;
            color: #27ae60;
        }

        .status-badge.rejected {
            background: #fadbd8;
            color: #e74c3c;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 4px;
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
            color: #475569;
            font-size: 13px;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            transition: border-color 0.3s;
            resize: vertical;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #2980b9;
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

        .no-withdrawals {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }

        .no-withdrawals i {
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

            .admin-main {
                padding: 10px;
            }

            .admin-page-header h1 {
                font-size: 20px;
            }

            /* Hide desktop table, show mobile cards */
            .admin-table-container {
                display: none;
            }

            .mobile-cards {
                display: block;
            }

            .filter-form {
                gap: 8px;
            }

            .filter-group input,
            .filter-group select {
                width: 150px;
            }

            .admin-pagination {
                flex-direction: column;
                gap: 10px;
            }

            .withdrawal-details {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .wallet-detail {
                grid-column: 1;
            }
        }

        @media (max-width: 480px) {
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group input,
            .filter-group select {
                width: 100%;
            }

            .withdrawal-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .withdrawal-actions {
                justify-content: center;
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
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="deposits.php"><i class="fas fa-arrow-down"></i> Deposits</a></li>
                    <li><a href="withdrawals.php" class="active"><i class="fas fa-arrow-up"></i> Withdrawals</a></li>
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
                    <h1>Withdrawals Management</h1>
                    <p>Manage user withdrawal requests</p>
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
                            <input type="text" name="search" placeholder="Search by username or transaction ID..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="PENDING" <?php echo $status_filter == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                <option value="APPROVED" <?php echo $status_filter == 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                                <option value="REJECTED" <?php echo $status_filter == 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="withdrawals.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </form>
                </div>

                <!-- Desktop Table View -->
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Transaction ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Wallet Address</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($withdrawals): ?>
                                <?php foreach ($withdrawals as $withdrawal): ?>
                                    <tr>
                                        <td><?php echo $withdrawal['id']; ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($withdrawal['username']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($withdrawal['email']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($withdrawal['transaction_id']); ?></td>
                                        <td><?php echo format_currency($withdrawal['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($withdrawal['withdrawal_method']); ?></td>
                                        <td>
                                            <?php if ($withdrawal['wallet_address']): ?>
                                                <span class="wallet-address" title="<?php echo htmlspecialchars($withdrawal['wallet_address']); ?>">
                                                    <?php echo substr($withdrawal['wallet_address'], 0, 10) . '...'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($withdrawal['status']); ?>">
                                                <?php echo $withdrawal['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($withdrawal['created_at'])); ?></td>
                                        <td>
                                            <?php if ($withdrawal['status'] == 'PENDING'): ?>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-success" 
                                                            onclick="processWithdrawal(<?php echo $withdrawal['id']; ?>, 'approve')" 
                                                            title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="processWithdrawal(<?php echo $withdrawal['id']; ?>, 'reject')" 
                                                            title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Processed</span>
                                                <?php if ($withdrawal['admin_note']): ?>
                                                    <br><small title="<?php echo htmlspecialchars($withdrawal['admin_note']); ?>">
                                                        <i class="fas fa-sticky-note"></i> Note
                                                    </small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <div class="no-withdrawals">
                                            <i class="fas fa-arrow-up"></i>
                                            <h3>No withdrawals found</h3>
                                            <p>Try adjusting your search criteria or check back later.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View -->
                <div class="mobile-cards">
                    <?php if ($withdrawals): ?>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                            <div class="withdrawal-card">
                                <div class="withdrawal-card-header">
                                    <div class="withdrawal-info">
                                        <h4><?php echo htmlspecialchars($withdrawal['username']); ?></h4>
                                        <div class="user-email"><?php echo htmlspecialchars($withdrawal['email']); ?></div>
                                        <div class="transaction-id">ID: <?php echo htmlspecialchars($withdrawal['transaction_id']); ?></div>
                                    </div>
                                    <span class="status-badge <?php echo strtolower($withdrawal['status']); ?>">
                                        <?php echo $withdrawal['status']; ?>
                                    </span>
                                </div>

                                <div class="withdrawal-details">
                                    <div class="detail-item">
                                        <div class="detail-label">Amount</div>
                                        <div class="detail-value"><?php echo format_currency($withdrawal['amount']); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Method</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($withdrawal['withdrawal_method']); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Date</div>
                                        <div class="detail-value"><?php echo date('M j, Y', strtotime($withdrawal['created_at'])); ?></div>
                                    </div>
                                    <?php if ($withdrawal['wallet_address']): ?>
                                        <div class="detail-item wallet-detail">
                                            <div class="detail-label">Wallet Address</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($withdrawal['wallet_address']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($withdrawal['status'] == 'PENDING'): ?>
                                    <div class="withdrawal-actions">
                                        <button class="btn btn-success" 
                                                onclick="processWithdrawal(<?php echo $withdrawal['id']; ?>, 'approve')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger" 
                                                onclick="processWithdrawal(<?php echo $withdrawal['id']; ?>, 'reject')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 10px; color: #64748b; font-style: italic;">
                                        Processed
                                        <?php if ($withdrawal['admin_note']): ?>
                                            <br><small title="<?php echo htmlspecialchars($withdrawal['admin_note']); ?>">
                                                <i class="fas fa-sticky-note"></i> Admin Note Available
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-withdrawals">
                            <i class="fas fa-arrow-up"></i>
                            <h3>No withdrawals found</h3>
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
                            (<?php echo number_format($total_withdrawals); ?> total withdrawals)
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

    <!-- Process Withdrawal Modal -->
    <div id="processModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Process Withdrawal</h3>
                <span class="modal-close">&times;</span>
            </div>
            <form method="POST" id="processForm">
                <input type="hidden" name="action" id="processAction">
                <input type="hidden" name="withdrawal_id" id="processWithdrawalId">
                <div class="form-group">
                    <label for="admin_note">Admin Note (Optional):</label>
                    <textarea name="admin_note" id="admin_note" rows="3" 
                              placeholder="Add a note about this decision..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn" id="processButton">Process</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
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

        function processWithdrawal(withdrawalId, action) {
            document.getElementById('processWithdrawalId').value = withdrawalId;
            document.getElementById('processAction').value = action;
            
            const modal = document.getElementById('processModal');
            const title = document.getElementById('modalTitle');
            const button = document.getElementById('processButton');
            
            if (action === 'approve') {
                title.textContent = 'Approve Withdrawal';
                button.textContent = 'Approve';
                button.className = 'btn btn-success';
            } else {
                title.textContent = 'Reject Withdrawal';
                button.textContent = 'Reject';
                button.className = 'btn btn-danger';
            }
            
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('processModal').style.display = 'none';
            document.getElementById('admin_note').value = '';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('processModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Close modal with X button
        document.querySelector('.modal-close').onclick = function() {
            closeModal();
        }
    </script>
</body>
</html>