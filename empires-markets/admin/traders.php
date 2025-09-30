<?php
// Admin Traders Management
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

// Handle trader actions
$message = '';
$message_type = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_trader':
                $trader_id = strtoupper(trim($_POST['trader_id'] ?? ''));
                $name = trim($_POST['name'] ?? '');
                $category = $_POST['category'] ?? '';
                $level = intval($_POST['level'] ?? 1);
                $level_amount = floatval($_POST['level_amount'] ?? 0);
                $avatar = $_POST['avatar'] ?? 'default.jpg';
                
                if ($trader_id && $name && $category && $level && $level_amount) {
                    $db->insert("INSERT INTO traders (trader_id, name, avatar, category, level, level_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'ACTIVE')", 
                               [$trader_id, $name, $avatar, $category, $level, $level_amount]);
                    $message = 'Trader added successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Please fill all required fields.';
                    $message_type = 'error';
                }
                break;
                
            case 'update_trader':
                $id = intval($_POST['trader_db_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $category = $_POST['category'] ?? '';
                $level = intval($_POST['level'] ?? 1);
                $level_amount = floatval($_POST['level_amount'] ?? 0);
                $processed_amount = floatval($_POST['processed_amount'] ?? 0);
                $active_connections = intval($_POST['active_connections'] ?? 0);
                $rating = intval($_POST['rating'] ?? 0);
                $percentage_rating = floatval($_POST['percentage_rating'] ?? 0);
                $status = $_POST['status'] ?? 'ACTIVE';
                $avatar = $_POST['avatar'] ?? 'default.jpg';
                
                if ($id && $name && $category && $level && $level_amount) {
                    $db->update("UPDATE traders SET name = ?, avatar = ?, category = ?, level = ?, level_amount = ?, processed_amount = ?, active_connections = ?, rating = ?, percentage_rating = ?, status = ? WHERE id = ?", 
                               [$name, $avatar, $category, $level, $level_amount, $processed_amount, $active_connections, $rating, $percentage_rating, $status, $id]);
                    $message = 'Trader updated successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Please fill all required fields.';
                    $message_type = 'error';
                }
                break;
                
            case 'delete_trader':
                $id = intval($_POST['trader_db_id'] ?? 0);
                if ($id) {
                    $db->delete("DELETE FROM traders WHERE id = ?", [$id]);
                    $message = 'Trader deleted successfully.';
                    $message_type = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get traders with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$level_filter = $_GET['level'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(trader_id LIKE ? OR name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($level_filter) {
    $where_conditions[] = "level = ?";
    $params[] = $level_filter;
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Get total count
    $total_traders = $db->count("SELECT COUNT(*) FROM traders $where_clause", $params);
    
    // Get traders
    $traders = $db->select("
        SELECT * FROM traders 
        $where_clause 
        ORDER BY level ASC, name ASC 
        LIMIT $per_page OFFSET $offset
    ", $params);
    
    $total_pages = ceil($total_traders / $per_page);
    
} catch (Exception $e) {
    $error_message = 'Error loading traders: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traders Management - <?php echo SITE_NAME; ?></title>
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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 15px;
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

        .trader-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
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

        .status-badge.active {
            background: #eafaf1;
            color: #27ae60;
        }

        .status-badge.inactive {
            background: #fadbd8;
            color: #e74c3c;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 4px;
        }

        /* Mobile Cards */
        .mobile-cards {
            display: none;
        }

        .trader-card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .trader-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .trader-avatar-large {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .trader-info h4 {
            margin: 0 0 4px 0;
            color: #1e293b;
            font-size: 16px;
        }

        .trader-info .trader-id {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .trader-info .trader-category {
            background: #f1f5f9;
            color: #475569;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .trader-status {
            margin-left: auto;
        }

        .trader-details {
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

        .level-detail {
            border-left: 3px solid #3498db;
        }

        .processed-detail {
            border-left: 3px solid #27ae60;
        }

        .connections-detail {
            border-left: 3px solid #e67e22;
        }

        .rating-detail {
            border-left: 3px solid #9b59b6;
        }

        .trader-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
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
            margin: 2% auto;
            padding: 0;
            border-radius: 12px;
            width: 95%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            margin: 0 16px 16px 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
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

        .no-traders {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }

        .no-traders i {
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

            .admin-page-header {
                flex-direction: column;
                align-items: stretch;
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
                width: 120px;
            }

            .admin-pagination {
                flex-direction: column;
                gap: 10px;
            }

            .trader-details {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .modal-content {
                width: 98%;
                margin: 1% auto;
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

            .trader-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .trader-status {
                margin-left: 0;
            }

            .trader-actions {
                flex-direction: column;
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
                    <li><a href="traders.php" class="active"><i class="fas fa-user-tie"></i> Traders</a></li>
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
                    <div>
                        <h1>Traders Management</h1>
                        <p>Manage copy trading traders</p>
                    </div>
                    <button class="btn btn-primary" onclick="showAddTraderModal()">
                        <i class="fas fa-plus"></i> Add New Trader
                    </button>
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
                            <input type="text" name="search" placeholder="Search traders..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <select name="level">
                                <option value="">All Levels</option>
                                <option value="1" <?php echo $level_filter == '1' ? 'selected' : ''; ?>>Level 1</option>
                                <option value="2" <?php echo $level_filter == '2' ? 'selected' : ''; ?>>Level 2</option>
                                <option value="3" <?php echo $level_filter == '3' ? 'selected' : ''; ?>>Level 3</option>
                                <option value="4" <?php echo $level_filter == '4' ? 'selected' : ''; ?>>Level 4</option>
                                <option value="5" <?php echo $level_filter == '5' ? 'selected' : ''; ?>>Level 5</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="ACTIVE" <?php echo $status_filter == 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                                <option value="INACTIVE" <?php echo $status_filter == 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="traders.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </form>
                </div>

                <!-- Desktop Table View -->
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <th>Trader ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Level</th>
                                <th>Processed</th>
                                <th>Connections</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($traders): ?>
                                <?php foreach ($traders as $trader): ?>
                                    <tr>
                                        <td>
                                            <img src="../assets/images/avatars/<?php echo htmlspecialchars($trader['avatar']); ?>" 
                                                 alt="<?php echo htmlspecialchars($trader['name']); ?>" 
                                                 class="trader-avatar-small"
                                                 
                                        </td>
                                        <td><?php echo htmlspecialchars($trader['trader_id']); ?></td>
                                        <td><?php echo htmlspecialchars($trader['name']); ?></td>
                                        <td><?php echo htmlspecialchars($trader['category']); ?></td>
                                        <td>Level <?php echo $trader['level']; ?></td>
                                        <td><?php echo format_currency($trader['processed_amount']); ?></td>
                                        <td><?php echo number_format($trader['active_connections']); ?></td>
                                        <td><?php echo $trader['rating']; ?> (<?php echo $trader['percentage_rating']; ?>%)</td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($trader['status']); ?>">
                                                <?php echo $trader['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="editTrader(<?php echo htmlspecialchars(json_encode($trader)); ?>)" 
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteTrader(<?php echo $trader['id']; ?>, '<?php echo htmlspecialchars($trader['name']); ?>')" 
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">
                                        <div class="no-traders">
                                            <i class="fas fa-user-tie"></i>
                                            <h3>No traders found</h3>
                                            <p>Try adjusting your search criteria or add a new trader.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View -->
                <div class="mobile-cards">
                    <?php if ($traders): ?>
                        <?php foreach ($traders as $trader): ?>
                            <div class="trader-card">
                                <div class="trader-card-header">
                                    <img src="../assets/images/avatars/<?php echo htmlspecialchars($trader['avatar']); ?>" 
                                         alt="<?php echo htmlspecialchars($trader['name']); ?>" 
                                         class="trader-avatar-large"
                                         
                                    
                                    <div class="trader-info">
                                        <h4><?php echo htmlspecialchars($trader['name']); ?></h4>
                                        <div class="trader-id">ID: <?php echo htmlspecialchars($trader['trader_id']); ?></div>
                                        <span class="trader-category"><?php echo htmlspecialchars($trader['category']); ?></span>
                                    </div>
                                    
                                    <div class="trader-status">
                                        <span class="status-badge <?php echo strtolower($trader['status']); ?>">
                                            <?php echo $trader['status']; ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="trader-details">
                                    <div class="detail-item level-detail">
                                        <div class="detail-label">Level</div>
                                        <div class="detail-value">Level <?php echo $trader['level']; ?></div>
                                    </div>
                                    
                                    <div class="detail-item processed-detail">
                                        <div class="detail-label">Processed</div>
                                        <div class="detail-value"><?php echo format_currency($trader['processed_amount']); ?></div>
                                    </div>
                                    
                                    <div class="detail-item connections-detail">
                                        <div class="detail-label">Connections</div>
                                        <div class="detail-value"><?php echo number_format($trader['active_connections']); ?></div>
                                    </div>
                                    
                                    <div class="detail-item rating-detail">
                                        <div class="detail-label">Rating</div>
                                        <div class="detail-value"><?php echo $trader['rating']; ?> (<?php echo $trader['percentage_rating']; ?>%)</div>
                                    </div>
                                </div>

                                <div class="trader-actions">
                                    <button class="btn btn-primary" 
                                            onclick="editTrader(<?php echo htmlspecialchars(json_encode($trader)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger" 
                                            onclick="deleteTrader(<?php echo $trader['id']; ?>, '<?php echo htmlspecialchars($trader['name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-traders">
                            <i class="fas fa-user-tie"></i>
                            <h3>No traders found</h3>
                            <p>Try adjusting your search criteria or add a new trader.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="admin-pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&level=<?php echo urlencode($level_filter); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <span class="pagination-info">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                            (<?php echo number_format($total_traders); ?> total traders)
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&level=<?php echo urlencode($level_filter); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-secondary">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add/Edit Trader Modal -->
    <div id="traderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Trader</h3>
                <span class="modal-close">&times;</span>
            </div>
            <form method="POST" id="traderForm">
                <input type="hidden" name="action" id="traderAction" value="add_trader">
                <input type="hidden" name="trader_db_id" id="traderDbId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="trader_id">Trader ID:</label>
                        <input type="text" name="trader_id" id="trader_id" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select name="category" id="category" required>
                            <option value="">Select Category</option>
                            <option value="HUMAN">Human</option>
                            <option value="TRADING BOT">Trading Bot</option>
                            <option value="MINING BOT">Mining Bot</option>
                            <option value="NFT">NFT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="level">Level:</label>
                        <select name="level" id="level" required>
                            <option value="1">Level 1</option>
                            <option value="2">Level 2</option>
                            <option value="3">Level 3</option>
                            <option value="4">Level 4</option>
                            <option value="5">Level 5</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="level_amount">Level Amount:</label>
                        <input type="number" step="0.01" name="level_amount" id="level_amount" required>
                    </div>
                    <div class="form-group">
                        <label for="avatar">Avatar:</label>
                        <input type="text" name="avatar" id="avatar" placeholder="avatar1.jpg">
                    </div>
                </div>
                
                <div id="editFields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="processed_amount">Processed Amount:</label>
                            <input type="number" step="0.01" name="processed_amount" id="processed_amount">
                        </div>
                        <div class="form-group">
                            <label for="active_connections">Active Connections:</label>
                            <input type="number" name="active_connections" id="active_connections">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rating">Rating:</label>
                            <input type="number" name="rating" id="rating">
                        </div>
                        <div class="form-group">
                            <label for="percentage_rating">Percentage Rating:</label>
                            <input type="number" step="0.01" name="percentage_rating" id="percentage_rating">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="ACTIVE">Active</option>
                            <option value="INACTIVE">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary" id="submitButton">Add Trader</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Trader</h3>
                <span class="modal-close">&times;</span>
            </div>
            <p style="padding: 16px;">Are you sure you want to delete trader <strong id="deleteTraderName"></strong>?</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_trader">
                <input type="hidden" name="trader_db_id" id="deleteTraderDbId">
                <div class="modal-actions">
                    <button type="submit" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
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

        function showAddTraderModal() {
            document.getElementById('modalTitle').textContent = 'Add New Trader';
            document.getElementById('traderAction').value = 'add_trader';
            document.getElementById('submitButton').textContent = 'Add Trader';
            document.getElementById('editFields').style.display = 'none';
            document.getElementById('traderForm').reset();
            document.getElementById('traderModal').style.display = 'block';
        }

        function editTrader(trader) {
            document.getElementById('modalTitle').textContent = 'Edit Trader';
            document.getElementById('traderAction').value = 'update_trader';
            document.getElementById('submitButton').textContent = 'Update Trader';
            document.getElementById('editFields').style.display = 'block';
            
            // Fill form with trader data
            document.getElementById('traderDbId').value = trader.id;
            document.getElementById('trader_id').value = trader.trader_id;
            document.getElementById('trader_id').readOnly = true;
            document.getElementById('name').value = trader.name;
            document.getElementById('category').value = trader.category;
            document.getElementById('level').value = trader.level;
            document.getElementById('level_amount').value = trader.level_amount;
            document.getElementById('avatar').value = trader.avatar;
            document.getElementById('processed_amount').value = trader.processed_amount;
            document.getElementById('active_connections').value = trader.active_connections;
            document.getElementById('rating').value = trader.rating;
            document.getElementById('percentage_rating').value = trader.percentage_rating;
            document.getElementById('status').value = trader.status;
            
            document.getElementById('traderModal').style.display = 'block';
        }

        function deleteTrader(id, name) {
            document.getElementById('deleteTraderName').textContent = name;
            document.getElementById('deleteTraderDbId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('traderModal').style.display = 'none';
            document.getElementById('trader_id').readOnly = false;
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const traderModal = document.getElementById('traderModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == traderModal) {
                traderModal.style.display = 'none';
            }
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
        }

        // Close modals with X button
        document.querySelectorAll('.modal-close').forEach(function(element) {
            element.onclick = function() {
                closeModal();
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>