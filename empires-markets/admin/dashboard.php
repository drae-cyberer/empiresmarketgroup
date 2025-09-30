<?php
// Admin Dashboard
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// Get admin info
$admin_username = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'];

// Get dashboard statistics
try {
    // Total users
    $total_users = $db->count("SELECT COUNT(*) FROM users");
    
    // Active users
    $active_users = $db->count("SELECT COUNT(*) FROM users WHERE account_status = 'ACTIVE'");
    
    // Total deposits
    $deposits_data = $db->selectOne("SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as sum FROM deposits");
    $total_deposits = $deposits_data['total'] ?? 0;
    $total_deposit_amount = $deposits_data['sum'] ?? 0;
    
    // Pending deposits
    $pending_deposits = $db->count("SELECT COUNT(*) FROM deposits WHERE status = 'PENDING'");
    
    // Total withdrawals
    $withdrawals_data = $db->selectOne("SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as sum FROM withdrawals");
    $total_withdrawals = $withdrawals_data['total'] ?? 0;
    $total_withdrawal_amount = $withdrawals_data['sum'] ?? 0;
    
    // Pending withdrawals
    $pending_withdrawals = $db->count("SELECT COUNT(*) FROM withdrawals WHERE status = 'PENDING'");
    
    // Total trades
    $total_trades = $db->count("SELECT COUNT(*) FROM trades");
    
    // Active copy trades
    $active_copy_trades = $db->count("SELECT COUNT(*) FROM copy_trades WHERE status = 'ACTIVE'");
    
} catch (Exception $e) {
    $error_message = 'Error loading dashboard data: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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
            background: rgba(255,255,255,0.2);
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
            background: rgba(255,255,255,0.1);
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
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
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

        .admin-stat-icon.users { background: #3498db; }
        .admin-stat-icon.deposits { background: #27ae60; }
        .admin-stat-icon.withdrawals { background: #e67e22; }
        .admin-stat-icon.trades { background: #9b59b6; }

        .admin-stat-info h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .admin-stat-info p {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .admin-stat-info small {
            font-size: 11px;
            color: #94a3b8;
        }

        /* Quick Actions */
        .admin-quick-actions {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .admin-quick-actions h2 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .quick-action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            color: #64748b;
            transition: all 0.2s;
            position: relative;
            font-size: 12px;
        }

        .quick-action-card:hover {
            border-color: #2980b9;
            color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(41,128,185,0.15);
        }

        .quick-action-card i {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .quick-action-card .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc2626;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            min-width: 18px;
            text-align: center;
        }

        /* Recent Activity */
        .admin-recent-activity {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .admin-recent-activity h2 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
        }

        .activity-icon.deposit { background: #27ae60; }
        .activity-icon.withdrawal { background: #e67e22; }
        .activity-icon.trade { background: #9b59b6; }

        .activity-info {
            flex: 1;
        }

        .activity-info p {
            font-size: 12px;
            margin-bottom: 2px;
        }

        .activity-info small {
            font-size: 10px;
            color: #94a3b8;
        }

        .activity-status {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .activity-status.pending {
            background: #fdf2e9;
            color: #e67e22;
        }

        .activity-status.completed {
            background: #eafaf1;
            color: #27ae60;
        }

        .activity-status.failed {
            background: #fadbd8;
            color: #e74c3c;
        }

        .no-activity {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
        }

        .no-activity i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .admin-user-info .admin-welcome {
                display: none;
            }

            .admin-stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 10px;
            }

            .admin-stat-card {
                padding: 12px;
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }

            .admin-stat-icon {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }

            .admin-stat-info h3 {
                font-size: 16px;
            }

            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .quick-action-card {
                padding: 12px;
            }

            .quick-action-card i {
                font-size: 16px;
            }

            .admin-main {
                padding: 10px;
            }

            .admin-page-header h1 {
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {
            .admin-stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions-grid {
                grid-template-columns: 1fr;
            }

            .admin-stat-card {
                flex-direction: row;
                text-align: left;
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
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
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
                    <h1>Dashboard Overview</h1>
                    <p>Welcome to the Empires Markets admin panel</p>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="admin-stat-info">
                            <h3><?php echo number_format($total_users ?? 0); ?></h3>
                            <p>Total Users</p>
                            <small><?php echo number_format($active_users ?? 0); ?> Active</small>
                        </div>
                    </div>

                    <div class="admin-stat-card">
                        <div class="admin-stat-icon deposits">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="admin-stat-info">
                            <h3><?php echo format_currency($total_deposit_amount ?? 0); ?></h3>
                            <p>Total Deposits</p>
                            <small><?php echo number_format($pending_deposits ?? 0); ?> Pending</small>
                        </div>
                    </div>

                    <div class="admin-stat-card">
                        <div class="admin-stat-icon withdrawals">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="admin-stat-info">
                            <h3><?php echo format_currency($total_withdrawal_amount ?? 0); ?></h3>
                            <p>Total Withdrawals</p>
                            <small><?php echo number_format($pending_withdrawals ?? 0); ?> Pending</small>
                        </div>
                    </div>

                    <div class="admin-stat-card">
                        <div class="admin-stat-icon trades">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="admin-stat-info">
                            <h3><?php echo number_format($total_trades ?? 0); ?></h3>
                            <p>Total Trades</p>
                            <small><?php echo number_format($active_copy_trades ?? 0); ?> Copy Trades</small>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="admin-quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions-grid">
                        <a href="deposits.php?status=pending" class="quick-action-card">
                            <i class="fas fa-clock"></i>
                            <span>Pending Deposits</span>
                            <?php if ($pending_deposits > 0): ?>
                                <div class="badge"><?php echo number_format($pending_deposits); ?></div>
                            <?php endif; ?>
                        </a>
                        
                        <a href="withdrawals.php?status=pending" class="quick-action-card">
                            <i class="fas fa-hourglass-half"></i>
                            <span>Pending Withdrawals</span>
                            <?php if ($pending_withdrawals > 0): ?>
                                <div class="badge"><?php echo number_format($pending_withdrawals); ?></div>
                            <?php endif; ?>
                        </a>
                        
                        <a href="users.php" class="quick-action-card">
                            <i class="fas fa-user-plus"></i>
                            <span>Manage Users</span>
                        </a>
                        
                        <a href="traders.php" class="quick-action-card">
                            <i class="fas fa-user-tie"></i>
                            <span>Manage Traders</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="admin-recent-activity">
                    <h2>Recent Activity</h2>
                    <div class="activity-list">
                        <?php
                        try {
                            // Get recent transactions
                            $recent_transactions = $db->select("
                                SELECT t.*, u.username 
                                FROM transactions t 
                                JOIN users u ON t.user_id = u.id 
                                ORDER BY t.created_at DESC 
                                LIMIT 10
                            ");
                            
                            if ($recent_transactions):
                                foreach ($recent_transactions as $transaction):
                        ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo strtolower($transaction['type']); ?>">
                                    <i class="fas fa-<?php 
                                        echo $transaction['type'] == 'DEPOSIT' ? 'arrow-down' : 
                                            ($transaction['type'] == 'WITHDRAWAL' ? 'arrow-up' : 'exchange-alt'); 
                                    ?>"></i>
                                </div>
                                <div class="activity-info">
                                    <p><strong><?php echo htmlspecialchars($transaction['username']); ?></strong> 
                                       <?php echo strtolower($transaction['type']); ?> of 
                                       <strong><?php echo format_currency($transaction['amount']); ?></strong></p>
                                    <small><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></small>
                                </div>
                                <div class="activity-status <?php echo strtolower($transaction['status']); ?>">
                                    <?php echo $transaction['status']; ?>
                                </div>
                            </div>
                        <?php 
                                endforeach;
                            else:
                        ?>
                            <div class="no-activity">
                                <i class="fas fa-info-circle"></i>
                                <p>No recent activity</p>
                            </div>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            echo '<div class="alert alert-error">Error loading recent activity</div>';
                        }
                        ?>
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