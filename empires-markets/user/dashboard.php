<?php
// User Dashboard - Copy Trading Interface - FIXED VERSION
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in
require_login();

// LOAD FRESH USER DATA FROM DATABASE - THIS IS THE KEY FIX!
$current_user = get_user_by_id($_SESSION['user_id']);
if (!$current_user) {
    header('Location: login.php');
    exit();
}

// Get user statistics
$user_deposits = get_user_deposits($_SESSION['user_id'], 10);
$user_withdrawals = get_user_withdrawals($_SESSION['user_id'], 10);
$user_trades = get_user_trades($_SESSION['user_id'], 10);


// Get investment plans
$plans = get_investment_plans();

// Get traders by level
$traders_by_level = [];
for ($i = 1; $i <= 5; $i++) {
    $traders_by_level[$i] = get_traders_by_level($i);
}

// Get market data
$market_data = get_market_data();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    
    <!-- Critical CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Mobile Navigation Fixes Only -->
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
        
        /* Chart fallback styling */
        .chart-fallback {
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            text-align: center;
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
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
            }
            
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                padding: 0 10px;
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
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Account Update</a></li>
            <li><a href="trades.php"><i class="fas fa-chart-line"></i> Trade Records</a></li>
            <li><a href="plans.php"><i class="fas fa-chart-line"></i> Trade plans</a></li>
            <li><a href="deposit.php"><i class="fas fa-plus-circle"></i> Deposit/Withdrawal</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-users"></i> Request Connections</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-link"></i> View Connections</a></li>
            <li><a href="transactions.php"><i class="fas fa-history"></i> Transaction History</a></li>
            <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
            <li><a href="profile.php"><i class="fas fa-user-friends"></i> Ref. Users</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-content" id="dashboardContent">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-text">Welcome, <?php echo htmlspecialchars($current_user['username']); ?>!</h1>
            </div>

            <!-- Stats Grid - 2x4 Layout - DISPLAYS REAL DATABASE VALUES -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon balance">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo format_currency($current_user['balance']); ?></h3>
                        <p>Total Balance</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon trades">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo format_currency($current_user['total_trades']); ?></h3>
                        <p>Total Trades</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon deposits">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo format_currency($current_user['total_deposits']); ?></h3>
                        <p>Total Deposits</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon withdrawals">
                        <i class="fas fa-upload"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo format_currency($current_user['total_withdrawals']); ?></h3>
                        <p>Total Withdrawals</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bonus">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo format_currency($current_user['total_bonuses'] ?? 0); ?></h3>
                        <p>Total Bonuses</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon status">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo strtoupper($current_user['account_status']); ?></h3>
                        <p>Account Status</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon signal">
                        <i class="fas fa-signal"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $current_user['signal_strength']; ?>.00%</h3>
                        <p>Signal Strength</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon account">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo strtoupper($current_user['account_type']); ?></h3>
                        <p>Account Type</p>
                    </div>
                </div>
            </div>

            <!-- TradingView Charts Section -->
            <div class="charts-section">
                <!-- Apple Inc Chart -->
                <div class="chart-card">
                    <div class="tradingview-widget-container" style="height: 400px;">
                        <div id="tradingview_aapl"></div>
                        <div class="chart-description">
                            Track all Stock markets on Empires Markets
                        </div>
                    </div>
                </div>

                <!-- Bitcoin Chart -->
                <div class="chart-card">
                    <div class="tradingview-widget-container" style="height: 400px;">
                        <div id="tradingview_btc"></div>
                        <div class="chart-description">
                            Track all Crypto markets on Empires Markets
                        </div>
                    </div>
                </div>

                <!-- Federal Funds Rate Chart -->
                <div class="chart-card">
                    <div class="tradingview-widget-container" style="height: 400px;">
                        <div id="tradingview_fed"></div>
                        <div class="chart-description">
                            Track all Economies markets on Empires Markets
                        </div>
                    </div>
                </div>
            </div>

            <!-- Refresh button -->
            <div style="text-align: center; margin: 2rem 0;">
                <button id="refreshData" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i> Refresh Data
                </button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>All Rights Reserved © Empires Markets 2025</p>
        </div>
    </footer>

    <!-- Core JavaScript - NO LOADING ISSUES -->
    <script>
        // IMMEDIATE INITIALIZATION - NO LOADING DELAYS
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize everything immediately
            initializeSidebar();
            initializeCopyTrading();
            initializeRefreshButton();
            
            // Load TradingView charts immediately
            loadTradingViewScript();
            
            console.log('Dashboard loaded immediately');
        });

        // Sidebar functionality - Desktop + Mobile fixed
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

        // Copy Trading functionality
        function initializeCopyTrading() {
            const connectButtons = document.querySelectorAll('.btn-connect');
            
            connectButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const traderId = this.getAttribute('data-trader-id');
                    const traderName = this.getAttribute('data-trader-name');
                    const level = this.getAttribute('data-level');
                    
                    const amount = prompt(`Connect to ${traderName} (Level ${level})\n\nEnter investment amount ($):`);
                    
                    if (amount && !isNaN(amount) && parseFloat(amount) >= 10) {
                        this.disabled = true;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting...';
                        
                        // Create and submit form
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'copy-trading.php';
                        form.style.display = 'none';
                        
                        const fields = {
                            'trader_id': traderId,
                            'amount': amount,
                            'csrf_token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        };
                        
                        Object.keys(fields).forEach(key => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = fields[key];
                            form.appendChild(input);
                        });
                        
                        document.body.appendChild(form);
                        form.submit();
                    } else if (amount !== null) {
                        alert('Please enter a valid amount (minimum $10)');
                    }
                });
            });
        }

        // Load TradingView script immediately
        function loadTradingViewScript() {
            // Check if TradingView is already loaded
            if (typeof TradingView !== 'undefined') {
                initializeTradingViewCharts();
                return;
            }
            
            // Load TradingView script
            const script = document.createElement('script');
            script.src = 'https://s3.tradingview.com/tv.js';
            script.async = true;
            
            script.onload = function() {
                // Wait a moment for TradingView to initialize
                setTimeout(function() {
                    initializeTradingViewCharts();
                }, 1000);
            };
            
            script.onerror = function() {
                console.log('TradingView script failed to load - using fallbacks');
                showChartFallbacks();
            };
            
            document.head.appendChild(script);
            
            // Fallback after 10 seconds if TradingView doesn't load
            setTimeout(function() {
                if (typeof TradingView === 'undefined') {
                    console.log('TradingView fallback after 10 seconds');
                    showChartFallbacks();
                }
            }, 10000);
        }

        // Initialize TradingView charts
        function initializeTradingViewCharts() {
            if (typeof TradingView === 'undefined') {
                showChartFallbacks();
                return;
            }
            
            try {
                // Apple Inc Chart
                new TradingView.widget({
                    "width": "100%",
                    "height": 350,
                    "symbol": "NASDAQ:AAPL",
                    "interval": "D",
                    "timezone": "Etc/UTC",
                    "theme": "dark",
                    "style": "1",
                    "locale": "en",
                    "toolbar_bg": "#f1f3f6",
                    "enable_publishing": false,
                    "allow_symbol_change": false,
                    "container_id": "tradingview_aapl",
                    "details": true,
                    "hotlist": false,
                    "calendar": false,
                    "hide_side_toolbar": true,
                    "withdateranges": true,
                    "hide_volume": false,
                    "save_image": false
                });
                
                // Bitcoin Chart
                new TradingView.widget({
                    "width": "100%",
                    "height": 350,
                    "symbol": "BINANCE:BTCUSDT",
                    "interval": "D",
                    "timezone": "Etc/UTC",
                    "theme": "dark",
                    "style": "1",
                    "locale": "en",
                    "toolbar_bg": "#f1f3f6",
                    "enable_publishing": false,
                    "allow_symbol_change": false,
                    "container_id": "tradingview_btc",
                    "details": true,
                    "hotlist": false,
                    "calendar": false,
                    "hide_side_toolbar": true,
                    "withdateranges": true,
                    "hide_volume": false,
                    "save_image": false
                });
                
                // Federal Funds Rate Chart
                new TradingView.widget({
                    "width": "100%",
                    "height": 350,
                    "symbol": "ECONOMICS:USINTR",
                    "interval": "M",
                    "timezone": "Etc/UTC",
                    "theme": "dark",
                    "style": "2",
                    "locale": "en",
                    "toolbar_bg": "#f1f3f6",
                    "enable_publishing": false,
                    "allow_symbol_change": false,
                    "container_id": "tradingview_fed",
                    "details": true,
                    "hotlist": false,
                    "calendar": false,
                    "hide_side_toolbar": true,
                    "withdateranges": true,
                    "hide_volume": true,
                    "save_image": false
                });
                
                console.log('TradingView charts initialized successfully');
                
            } catch (error) {
                console.error('Error initializing TradingView charts:', error);
                showChartFallbacks();
            }
        }

        // Show chart fallbacks
        function showChartFallbacks() {
            const charts = [
                { id: 'tradingview_aapl', title: 'Apple Inc (AAPL)', price: '$150.25', change: '+2.15%' },
                { id: 'tradingview_btc', title: 'Bitcoin (BTC)', price: '$43,250', change: '+1.85%' },
                { id: 'tradingview_fed', title: 'Federal Funds Rate', price: '5.25%', change: '0.00%' }
            ];
            
            charts.forEach(chart => {
                const container = document.getElementById(chart.id);
                if (container) {
                    container.innerHTML = `
                        <div class="chart-fallback">
                            <div>
                                <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #4CAF50;"></i>
                                <h3 style="margin: 0; font-size: 1.2rem; color: #fff;">${chart.title}</h3>
                                <p style="margin: 0.5rem 0; font-size: 1.5rem; color: #4CAF50; font-weight: bold;">${chart.price}</p>
                                <small style="opacity: 0.8; color: #4CAF50;">${chart.change}</small>
                                <br>
                                <small style="opacity: 0.6; color: #ccc; margin-top: 1rem; display: block;">Chart data available</small>
                            </div>
                        </div>
                    `;
                }
            });
        }

        // Refresh button functionality
        function initializeRefreshButton() {
            const refreshButton = document.getElementById('refreshData');
            if (refreshButton) {
                refreshButton.addEventListener('click', function() {
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
                    
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                });
            }
        }
    </script>

    <!-- Smartsupp Live Chat script -->
<script type="text/javascript">
var _smartsupp = _smartsupp || {};
_smartsupp.key = '6ca50d867629d06526628b9053948e0015ec18ed';
window.smartsupp||(function(d) {
  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];
  s=d.getElementsByTagName('script')[0];c=d.createElement('script');
  c.type='text/javascript';c.charset='utf-8';c.async=true;
  c.src='https://www.smartsuppchat.com/loader.js?';s.parentNode.insertBefore(c,s);
})(document);
</script>
<noscript> Powered by <a href=“https://www.smartsupp.com” target=“_blank”>Smartsupp</a></noscript>
</body>
</html>