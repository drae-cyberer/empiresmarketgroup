<?php
// Trade Records Page
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

// Get current user data
$current_user = get_user_by_id($_SESSION['user_id']);
if (!$current_user) {
    Auth::logout();
    redirect('index.php');
}

// Get user trades
$user_trades = get_user_trades($_SESSION['user_id'], 100);

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Trade Records - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Mobile Navigation CSS - Same as Dashboard -->
    <style>
        /* Mobile Navigation Fixes */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 10px;
            z-index: 1001;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Mobile Responsive - Only modify sidebar for mobile */
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 280px;
                height: 100vh;
                background: #1a1a1a;
                transition: left 0.3s ease;
                z-index: 1000;
                overflow-y: auto;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            
            .header-content {
                padding: 0 10px;
            }
            
            .data-table {
                margin: 10px 0;
            }
            
            .table {
                font-size: 12px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
            }
        }
        
        /* Desktop - Keep original sidebar behavior */
        @media (min-width: 769px) {
            .sidebar {
                /* Keep original desktop sidebar styles */
            }
            
            .main-content {
                /* Keep original desktop main content styles */
            }
        }
    </style>
    
    <!-- Mobile Navigation CSS -->
    <style>
        /* Mobile Navigation Fixes */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 10px;
            z-index: 1001;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Mobile Responsive - Only modify sidebar for mobile */
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 280px;
                height: 100vh;
                background: #1a1a1a;
                transition: left 0.3s ease;
                z-index: 1000;
                overflow-y: auto;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            
            .header-content {
                padding: 0 10px;
            }
            
            .data-table {
                margin: 10px 0;
            }
            
            .table {
                font-size: 12px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
            }
        }
        
        /* Desktop - Keep original sidebar behavior */
        @media (min-width: 769px) {
            .sidebar {
                /* Keep original desktop sidebar styles */
            }
            
            .main-content {
                /* Keep original desktop main content styles */
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <div class="header-content">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="logo" style="display: flex; justify-content: center; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px; font-family: 'Segoe UI', sans-serif;">
                            <i class="fas fa-chart-line" style="color: #e74c3c; font-size: 2rem;"></i>
                            <img src="logo-white.png" alt="Logo" style="height: 40px; width: auto;" onerror="this.style.display='none'">
                        </div>
                    </div>
                </div>
                
                <div class="nav-icons">
                    <span class="nav-icon">
                        <i class="fas fa-bell"></i>
                    </span>
                    <span class="kyc-badge">KYC</span>
                    <span class="nav-icon">
                        <i class="fas fa-user-circle"></i>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Account Update</a></li>
            <li><a href="trades.php" class="active"><i class="fas fa-chart-line"></i> Trade Records</a></li>
            <li><a href="plans.php" ><i class="fas fa-chart-line"></i> Trade Plans</a></li>
            <li><a href="deposit.php"><i class="fas fa-plus-circle"></i> Deposit/Withdrawal</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-users"></i> Request Connections</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-link"></i> View Connections</a></li>
            <li><a href="transactions.php"><i class="fas fa-history"></i> Transaction History</a></li>
            <li><a href="profile.php"><i class="fas fa-user-friends"></i> Ref. Users</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-content" id="dashboardContent">
            <!-- Page Header -->
            <div class="welcome-section">
                <h1 class="welcome-text">TRADE HISTORY</h1>
            </div>

            <!-- Open Trades Section -->
            <div class="data-table">
                <div class="table-header">
                    <h3 class="table-title">OPEN TRADES</h3>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>TRANSACTION ID</th>
                                <th>OPEN AMOUNT</th>
                                <th>TRADE TYPE</th>
                                <th>ASSET IMAGE</th>
                                <th>TRANSACTION DATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $open_trades = array_filter($user_trades, function($trade) {
                                return $trade['status'] === 'OPEN';
                            });
                            ?>
                            
                            <?php if (empty($open_trades)): ?>
                                <tr>
                                    <td colspan="5" class="no-data">NO PENDING TRADES</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($open_trades as $trade): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trade['transaction_id']); ?></td>
                                        <td><?php echo format_currency($trade['open_amount']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($trade['trade_type']); ?>">
                                                <?php echo htmlspecialchars($trade['trade_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($trade['asset']); ?></td>
                                        <td><?php echo format_date($trade['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Closed Trades Section -->
            <div class="data-table">
                <div class="table-header">
                    <h3 class="table-title">CLOSED TRADES</h3>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>TRANSACTION ID</th>
                                <th>OPEN AMOUNT</th>
                                <th>CLOSE AMOUNT</th>
                                <th>TRADE RETURN</th>
                                <th>TRADE TYPE</th>
                                <th>ASSET IMAGE</th>
                                <th>TRANSACTION DATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $closed_trades = array_filter($user_trades, function($trade) {
                                return $trade['status'] === 'CLOSED';
                            });
                            ?>
                            
                            <?php if (empty($closed_trades)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">NO APPROVED TRADES</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($closed_trades as $trade): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trade['transaction_id']); ?></td>
                                        <td><?php echo format_currency($trade['open_amount']); ?></td>
                                        <td><?php echo $trade['close_amount'] ? format_currency($trade['close_amount']) : '-'; ?></td>
                                        <td>
                                            <?php if ($trade['return_amount']): ?>
                                                <span class="<?php echo $trade['return_amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo format_currency($trade['return_amount']); ?>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($trade['trade_type']); ?>">
                                                <?php echo htmlspecialchars($trade['trade_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($trade['asset']); ?></td>
                                        <td><?php echo format_date($trade['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Trading Statistics -->
            <div class="stats-grid" style="margin-top: 30px;">
                <div class="stat-card">
                    <div class="stat-icon trades">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($user_trades); ?></h3>
                        <p>Total Trades</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon status">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($open_trades); ?></h3>
                        <p>Open Trades</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($closed_trades); ?></h3>
                        <p>Closed Trades</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon balance">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-info">
                        <?php 
                        $total_invested = array_sum(array_column($user_trades, 'open_amount'));
                        ?>
                        <h3><?php echo format_currency($total_invested); ?></h3>
                        <p>Total Invested</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>All Rights Reserved © Empires Markets 2025</p>
        </div>
    </footer>

    <!-- JavaScript - Same as Dashboard -->
    <script>
        // Initialize page immediately
        document.addEventListener('DOMContentLoaded', function() {
            initializeSidebar();
            console.log('Trades page loaded successfully');
        });

        // Sidebar functionality - Desktop + Mobile (Same as Dashboard)
        function initializeSidebar() {
            window.toggleSidebar = function() {
                // Only work on mobile screens
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    
                    if (sidebar && overlay) {
                        const isActive = sidebar.classList.contains('active');
                        
                        if (isActive) {
                            sidebar.classList.remove('active');
                            overlay.classList.remove('active');
                            document.body.style.overflow = '';
                        } else {
                            sidebar.classList.add('active');
                            overlay.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }
                    }
                }
            };
            
            // Close sidebar when clicking overlay (mobile only)
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay) {
                overlay.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        window.toggleSidebar();
                    }
                });
            }
            
            // Close sidebar when clicking menu items on mobile
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                const menuItems = sidebar.querySelectorAll('.sidebar-menu a');
                menuItems.forEach(item => {
                    item.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            window.toggleSidebar();
                        }
                    });
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    
                    if (sidebar && overlay) {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                }
            });
        }
    </script>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>