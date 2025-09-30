<?php
// Admin Transactions Management
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

// Get transactions with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR t.transaction_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($type_filter) {
    $where_conditions[] = "t.type = ?";
    $params[] = $type_filter;
}

if ($status_filter) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Get total count
    $total_transactions = $db->count("SELECT COUNT(*) FROM transactions t JOIN users u ON t.user_id = u.id $where_clause", $params);
    
    // Get transactions
    $transactions = $db->select("
        SELECT t.*, u.username, u.email 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        $where_clause 
        ORDER BY t.created_at DESC 
        LIMIT $per_page OFFSET $offset
    ", $params);
    
    $total_pages = ceil($total_transactions / $per_page);
    
    // Get transaction statistics
    $stats = $db->selectOne("
        SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN type = 'DEPOSIT' THEN amount ELSE 0 END) as total_deposits,
            SUM(CASE WHEN type = 'WITHDRAWAL' THEN amount ELSE 0 END) as total_withdrawals,
            SUM(CASE WHEN type = 'TRADE' THEN amount ELSE 0 END) as total_trades,
            SUM(CASE WHEN type = 'BONUS' THEN amount ELSE 0 END) as total_bonuses
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        $where_clause
    ", $params);
    
} catch (Exception $e) {
    $error_message = 'Error loading transactions: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - <?php echo SITE_NAME; ?></title>
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

        /* Stats Grid */
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .admin-stat-card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
        }

        .admin-stat-icon.deposits { background: #27ae60; }
        .admin-stat-icon.withdrawals { background: #e67e22; }
        .admin-stat-icon.trades { background: #9b59b6; }
        .admin-stat-icon.bonus { background: #e74c3c; }

        .admin-stat-info h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .admin-stat-info p {
            font-size: 12px;
            color: #64748b;
            margin: 0;
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
            width: 180px;
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
            font-size: 12px;
        }

        .admin-table th,
        .admin-table td {
            padding: 10px 6px;
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
            font-size: 11px;
        }

        /* Transaction Type Badges */
        .transaction-type {
            padding: 3px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .transaction-type.deposit {
            background: #eafaf1;
            color: #27ae60;
        }

        .transaction-type.withdrawal {
            background: #fdf2e9;
            color: #e67e22;
        }

        .transaction-type.trade {
            background: #f4f1fc;
            color: #9b59b6;
        }

        .transaction-type.bonus {
            background: #fadbd8;
            color: #e74c3c;
        }

        .transaction-type.commission {
            background: #e8f4fd;
            color: #2980b9;
        }

        /* Amount Display */
        .amount {
            font-weight: 600;
            font-size: 13px;
        }

        .amount.positive {
            color: #27ae60;
        }

        .amount.negative {
            color: #e74c3c;
        }

        /* Status Badges */
        .status-badge {
            padding: 3px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-badge.pending {
            background: #fdf2e9;
            color: #e67e22;
        }

        .status-badge.completed {
            background: #eafaf1;
            color: #27ae60;
        }

        .status-badge.failed {
            background: #fadbd8;
            color: #e74c3c;
        }

        /* Mobile Cards */
        .mobile-cards {
            display: none;
        }

        .transaction-card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .transaction-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .transaction-info h4 {
            margin: 0 0 4px 0;
            color: #1e293b;
            font-size: 15px;
        }

        .transaction-info .user-email {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .transaction-info .transaction-id {
            color: #94a3b8;
            font-size: 11px;
        }

        .transaction-badges {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: flex-end;
        }

        .transaction-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
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

        .amount-detail.positive {
            border-left: 3px solid #27ae60;
        }

        .amount-detail.negative {
            border-left: 3px solid #e74c3c;
        }

        .description-detail {
            grid-column: 1 / -1;
            border-left: 3px solid #9b59b6;
        }

        .transaction-date {
            text-align: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 12px;
            color: #64748b;
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

        .no-transactions {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }

        .no-transactions i {
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

            .admin-stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .admin-stat-card {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }

            .admin-stat-icon {
                width: 36px;
                height: 36px;
                font-size: 14px;
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
                width: 140px;
            }

            .admin-pagination {
                flex-direction: column;
                gap: 10px;
            }

            .transaction-details {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .description-detail {
                grid-column: 1;
            }
        }

        @media (max-width: 480px) {
            .admin-stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group input,
            .filter-group select {
                width: 100%;
            }

            .transaction-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .transaction-badges {
                flex-direction: row;
                align-items: flex-start;
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
                    <li><a href="withdrawals.php"><i class="fas fa-arrow-up"></i> Withdrawals</a></li>
                    <li><a href="trades.php"><i class="fas fa-chart-line"></i> Trades</a></li>
                    <li><a href="traders.php"><i class="fas fa-user-tie"></i> Traders</a></li>
                    <li><a href="plans.php"><i class="fas fa-layer-group"></i> Plans</a></li>
                    <li><a href="transactions.php" class="active"><i class="fas fa-history"></i> Transactions</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-content">
                <div class="admin-page-header">
                    <h1>Transaction History</h1>
                    <p>View all system transactions</p>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Transaction Statistics -->
                <?php if (isset($stats)): ?>
                    <div class="admin-stats-grid">
                        <div class="admin-stat-card">
                            <div class="admin-stat-icon deposits">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="admin-stat-info">
                                <h3><?php echo format_currency($stats['total_deposits'] ?? 0); ?></h3>
                                <p>Total Deposits</p>
                            </div>
                        </div>

                        <div class="admin-stat-card">
                            <div class="admin-stat-icon withdrawals">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="admin-stat-info">
                                <h3><?php echo format_currency($stats['total_withdrawals'] ?? 0); ?></h3>
                                <p>Total Withdrawals</p>
                            </div>
                        </div>

                        <div class="admin-stat-card">
                            <div class="admin-stat-icon trades">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="admin-stat-info">
                                <h3><?php echo format_currency($stats['total_trades'] ?? 0); ?></h3>
                                <p>Total Trades</p>
                            </div>
                        </div>

                        <div class="admin-stat-card">
                            <div class="admin-stat-icon bonus">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div class="admin-stat-info">
                                <h3><?php echo format_currency($stats['total_bonuses'] ?? 0); ?></h3>
                                <p>Total Bonuses</p>
                            </div>
                        </div>
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
                            <select name="type">
                                <option value="">All Types</option>
                                <option value="DEPOSIT" <?php echo $type_filter == 'DEPOSIT' ? 'selected' : ''; ?>>Deposit</option>
                                <option value="WITHDRAWAL" <?php echo $type_filter == 'WITHDRAWAL' ? 'selected' : ''; ?>>Withdrawal</option>
                                <option value="TRADE" <?php echo $type_filter == 'TRADE' ? 'selected' : ''; ?>>Trade</option>
                                <option value="BONUS" <?php echo $type_filter == 'BONUS' ? 'selected' : ''; ?>>Bonus</option>
                                <option value="COMMISSION" <?php echo $type_filter == 'COMMISSION' ? 'selected' : ''; ?>>Commission</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="PENDING" <?php echo $status_filter == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                <option value="COMPLETED" <?php echo $status_filter == 'COMPLETED' ? 'selected' : ''; ?>>Completed</option>
                                <option value="FAILED" <?php echo $status_filter == 'FAILED' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="transactions.php" class="btn btn-secondary">
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
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo $transaction['id']; ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($transaction['username']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($transaction['email']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                        <td>
                                            <span class="transaction-type <?php echo strtolower($transaction['type']); ?>">
                                                <i class="fas fa-<?php 
                                                    echo $transaction['type'] == 'DEPOSIT' ? 'arrow-down' : 
                                                        ($transaction['type'] == 'WITHDRAWAL' ? 'arrow-up' : 
                                                        ($transaction['type'] == 'TRADE' ? 'chart-line' : 
                                                        ($transaction['type'] == 'BONUS' ? 'gift' : 'percentage'))); 
                                                ?>"></i>
                                                <?php echo $transaction['type']; ?>
                                            </span>
                                        </td>
                                        <td class="amount <?php echo $transaction['type'] == 'WITHDRAWAL' ? 'negative' : 'positive'; ?>">
                                            <?php echo ($transaction['type'] == 'WITHDRAWAL' ? '-' : '+') . format_currency($transaction['amount']); ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['description']): ?>
                                                <span title="<?php echo htmlspecialchars($transaction['description']); ?>">
                                                    <?php echo substr($transaction['description'], 0, 50) . (strlen($transaction['description']) > 50 ? '...' : ''); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">No description</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($transaction['status']); ?>">
                                                <?php echo $transaction['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="no-transactions">
                                            <i class="fas fa-history"></i>
                                            <h3>No transactions found</h3>
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
                    <?php if ($transactions): ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <div class="transaction-card">
                                <div class="transaction-card-header">
                                    <div class="transaction-info">
                                        <h4><?php echo htmlspecialchars($transaction['username']); ?></h4>
                                        <div class="user-email"><?php echo htmlspecialchars($transaction['email']); ?></div>
                                        <div class="transaction-id">ID: <?php echo htmlspecialchars($transaction['transaction_id']); ?></div>
                                    </div>
                                    <div class="transaction-badges">
                                        <span class="transaction-type <?php echo strtolower($transaction['type']); ?>">
                                            <i class="fas fa-<?php 
                                                echo $transaction['type'] == 'DEPOSIT' ? 'arrow-down' : 
                                                    ($transaction['type'] == 'WITHDRAWAL' ? 'arrow-up' : 
                                                    ($transaction['type'] == 'TRADE' ? 'chart-line' : 
                                                    ($transaction['type'] == 'BONUS' ? 'gift' : 'percentage'))); 
                                            ?>"></i>
                                            <?php echo $transaction['type']; ?>
                                        </span>
                                        <span class="status-badge <?php echo strtolower($transaction['status']); ?>">
                                            <?php echo $transaction['status']; ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="transaction-details">
                                    <div class="detail-item amount-detail <?php echo $transaction['type'] == 'WITHDRAWAL' ? 'negative' : 'positive'; ?>">
                                        <div class="detail-label">Amount</div>
                                        <div class="detail-value amount <?php echo $transaction['type'] == 'WITHDRAWAL' ? 'negative' : 'positive'; ?>">
                                            <?php echo ($transaction['type'] == 'WITHDRAWAL' ? '-' : '+') . format_currency($transaction['amount']); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($transaction['description']): ?>
                                        <div class="detail-item description-detail">
                                            <div class="detail-label">Description</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="transaction-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-transactions">
                            <i class="fas fa-history"></i>
                            <h3>No transactions found</h3>
                            <p>Try adjusting your search criteria or check back later.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="admin-pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <span class="pagination-info">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                            (<?php echo number_format($total_transactions); ?> total transactions)
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-secondary">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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