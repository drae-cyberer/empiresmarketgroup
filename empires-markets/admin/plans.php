<?php
// Admin Plans Management
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

// Handle plan actions
$message = '';
$message_type = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_plan':
                $id = intval($_POST['plan_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $level = intval($_POST['level'] ?? 1);
                $min_amount = floatval($_POST['min_amount'] ?? 0);
                $max_amount = $_POST['max_amount'] ? floatval($_POST['max_amount']) : null;
                $features = trim($_POST['features'] ?? '');
                $status = $_POST['status'] ?? 'ACTIVE';
                
                if ($id && $name && $level && $min_amount) {
                    $db->update("UPDATE plans SET name = ?, level = ?, min_amount = ?, max_amount = ?, features = ?, status = ? WHERE id = ?", 
                               [$name, $level, $min_amount, $max_amount, $features, $status, $id]);
                    $message = 'Plan updated successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Please fill all required fields.';
                    $message_type = 'error';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get all plans
try {
    $plans = $db->select("SELECT * FROM plans ORDER BY level ASC");
} catch (Exception $e) {
    $error_message = 'Error loading plans: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans Management - <?php echo SITE_NAME; ?></title>
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

        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
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

        /* Mobile Cards */
        .mobile-cards {
            display: none;
        }

        .plan-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: relative;
        }

        .plan-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .plan-info h4 {
            margin: 0 0 8px 0;
            color: #1e293b;
            font-size: 18px;
            font-weight: 600;
        }

        .plan-level {
            background: #e8f4fd;
            color: #2980b9;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .plan-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
        }

        .detail-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 15px;
            color: #1e293b;
            font-weight: 600;
        }

        .min-amount-detail {
            border-left: 4px solid #27ae60;
        }

        .max-amount-detail {
            border-left: 4px solid #e67e22;
        }

        .plan-features {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            border-left: 4px solid #9b59b6;
        }

        .plan-features .detail-label {
            margin-bottom: 6px;
        }

        .plan-features .detail-value {
            font-size: 13px;
            font-weight: 400;
            line-height: 1.5;
            color: #475569;
        }

        .plan-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .plan-date {
            font-size: 12px;
            color: #64748b;
        }

        .plan-actions {
            text-align: center;
        }

        .no-plans {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }

        .no-plans i {
            font-size: 36px;
            margin-bottom: 16px;
            color: #cbd5e1;
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
            margin: 3% auto;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2980b9;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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

            .plan-details {
                grid-template-columns: 1fr;
                gap: 12px;
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
            .plan-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .plan-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
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
                    <li><a href="plans.php" class="active"><i class="fas fa-layer-group"></i> Plans</a></li>
                    <li><a href="transactions.php"><i class="fas fa-history"></i> Transactions</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-content">
                <div class="admin-page-header">
                    <h1>Investment Plans Management</h1>
                    <p>Manage investment plans and levels</p>
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

                <!-- Desktop Table View -->
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>Name</th>
                                <th>Min Amount</th>
                                <th>Max Amount</th>
                                <th>Features</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($plans): ?>
                                <?php foreach ($plans as $plan): ?>
                                    <tr>
                                        <td>Level <?php echo $plan['level']; ?></td>
                                        <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                        <td><?php echo format_currency($plan['min_amount']); ?></td>
                                        <td>
                                            <?php if ($plan['max_amount']): ?>
                                                <?php echo format_currency($plan['max_amount']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No limit</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($plan['features']): ?>
                                                <span title="<?php echo htmlspecialchars($plan['features']); ?>">
                                                    <?php echo substr($plan['features'], 0, 50) . (strlen($plan['features']) > 50 ? '...' : ''); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">No features</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($plan['status']); ?>">
                                                <?php echo $plan['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($plan['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="editPlan(<?php echo htmlspecialchars(json_encode($plan)); ?>)" 
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="no-plans">
                                            <i class="fas fa-layer-group"></i>
                                            <h3>No plans found</h3>
                                            <p>No investment plans have been created yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View -->
                <div class="mobile-cards">
                    <?php if ($plans): ?>
                        <?php foreach ($plans as $plan): ?>
                            <div class="plan-card">
                                <div class="plan-card-header">
                                    <div class="plan-info">
                                        <h4><?php echo htmlspecialchars($plan['name']); ?></h4>
                                        <span class="plan-level">Level <?php echo $plan['level']; ?></span>
                                    </div>
                                    <span class="status-badge <?php echo strtolower($plan['status']); ?>">
                                        <?php echo $plan['status']; ?>
                                    </span>
                                </div>

                                <div class="plan-details">
                                    <div class="detail-item min-amount-detail">
                                        <div class="detail-label">Minimum Amount</div>
                                        <div class="detail-value"><?php echo format_currency($plan['min_amount']); ?></div>
                                    </div>
                                    
                                    <div class="detail-item max-amount-detail">
                                        <div class="detail-label">Maximum Amount</div>
                                        <div class="detail-value">
                                            <?php if ($plan['max_amount']): ?>
                                                <?php echo format_currency($plan['max_amount']); ?>
                                            <?php else: ?>
                                                <span style="color: #6c757d;">No limit</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($plan['features']): ?>
                                    <div class="plan-features">
                                        <div class="detail-label">Features</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($plan['features']); ?></div>
                                    </div>
                                <?php endif; ?>

                                <div class="plan-meta">
                                    <div class="plan-date">
                                        <i class="fas fa-calendar"></i>
                                        Created: <?php echo date('M j, Y', strtotime($plan['created_at'])); ?>
                                    </div>
                                </div>

                                <div class="plan-actions">
                                    <button class="btn btn-primary" 
                                            onclick="editPlan(<?php echo htmlspecialchars(json_encode($plan)); ?>)">
                                        <i class="fas fa-edit"></i> Edit Plan
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-plans">
                            <i class="fas fa-layer-group"></i>
                            <h3>No plans found</h3>
                            <p>No investment plans have been created yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Plan Modal -->
    <div id="planModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Investment Plan</h3>
                <span class="modal-close">&times;</span>
            </div>
            <form method="POST" id="planForm">
                <input type="hidden" name="action" value="update_plan">
                <input type="hidden" name="plan_id" id="planId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Plan Name:</label>
                        <input type="text" name="name" id="name" required>
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
                        <label for="min_amount">Minimum Amount:</label>
                        <input type="number" step="0.01" name="min_amount" id="min_amount" required>
                    </div>
                    <div class="form-group">
                        <label for="max_amount">Maximum Amount (Optional):</label>
                        <input type="number" step="0.01" name="max_amount" id="max_amount">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="features">Features:</label>
                    <textarea name="features" id="features" rows="3" 
                              placeholder="Describe the features of this plan..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status" required>
                        <option value="ACTIVE">Active</option>
                        <option value="INACTIVE">Inactive</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Update Plan</button>
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

        function editPlan(plan) {
            document.getElementById('planId').value = plan.id;
            document.getElementById('name').value = plan.name;
            document.getElementById('level').value = plan.level;
            document.getElementById('min_amount').value = plan.min_amount;
            document.getElementById('max_amount').value = plan.max_amount || '';
            document.getElementById('features').value = plan.features || '';
            document.getElementById('status').value = plan.status;
            
            document.getElementById('planModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('planModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('planModal');
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