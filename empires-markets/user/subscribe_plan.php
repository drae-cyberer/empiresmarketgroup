<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// subscribe_plan.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check login
require_login();

// Load user
$current_user = get_user_by_id($_SESSION['user_id']);
if (!$current_user) {
    header('Location: login.php');
    exit();
}

// Validate plan ID
if (!isset($_GET['plan_id']) || !is_numeric($_GET['plan_id'])) {
    header("Location: plans.php?error=invalid_plan");
    exit();
}

$plan_id = (int) $_GET['plan_id'];

try {
    global $db; // Use the global Database instance
    
    // Load plan details using your Database class
    $plan = $db->selectOne("SELECT * FROM plans WHERE id = ? AND status = 'ACTIVE'", [$plan_id]);
    
    if (!$plan) {
        header("Location: plans.php?error=plan_not_found");
        exit();
    }
    
    // Check if user's balance meets minimum plan requirement (optional)
    if ($current_user['balance'] < $plan['min_amount']) {
        header("Location: plans.php?error=insufficient_balance&required=" . $plan['min_amount']);
        exit();
    }
    
    // Update user's plan using your Database class
    $result = $db->update(
        "UPDATE users SET plan_id = ?, plan_subscribed_at = NOW() WHERE id = ?", 
        [$plan_id, $current_user['id']]
    );
    
    if (!$result) {
        throw new Exception("Failed to update user plan");
    }
    
    // Optional: Log the plan subscription in transactions table
    $transaction_id = generate_transaction_id('PLAN');
    $db->insert(
        "INSERT INTO transactions (user_id, transaction_id, type, amount, net_amount, currency, description, status) VALUES (?, ?, 'BONUS', 0, 0, 'USD', ?, 'COMPLETED')",
        [$current_user['id'], $transaction_id, 'Subscribed to ' . $plan['name'] . ' plan']
    );
    
} catch (Exception $e) {
    // Log error and redirect with error message
    error_log("Plan subscription error: " . $e->getMessage());
    header("Location: plans.php?error=subscription_failed");
    exit();
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Subscription - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-box {
            background: #1f1f1f;
            padding: 30px;
            border-radius: 12px;
            color: #fff;
            text-align: center;
            max-width: 500px;
            margin: 4rem auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .success-box h2 {
            color: #2ecc71;
            margin-bottom: 15px;
        }
        .success-box p {
            margin-bottom: 25px;
            font-size: 1.05rem;
            opacity: 0.9;
        }
        .plan-details {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .plan-details h3 {
            color: #e74c3c;
            margin-bottom: 10px;
        }
        .plan-details p {
            margin: 5px 0;
            font-size: 0.95rem;
        }
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        .btn-group a {
            background: #e74c3c;
            padding: 12px 20px;
            border-radius: 6px;
            color: #fff;
            text-decoration: none;
            transition: background 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-group a:hover {
            background: #c0392b;
        }
        .btn-secondary {
            background: #34495e !important;
        }
        .btn-secondary:hover {
            background: #2c3e50 !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div style="display:flex;align-items:center;gap:20px;">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <div class="logo" style="display:flex;align-items:center;gap:12px;">
                    <i class="fas fa-chart-line" style="color:#e74c3c;font-size:2rem;"></i>
                    <img src="logo-white.png" alt="Logo" style="height:40px;" onerror="this.style.display='none'">
                </div>
            </div>
            <div class="nav-icons">
                <span class="nav-icon"><i class="fas fa-bell"></i></span>
                <span class="kyc-badge">KYC</span>
                <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Account Update</a></li>
            <li><a href="trades.php"><i class="fas fa-chart-line"></i> Trade Records</a></li>
            <li><a href="plans.php"><i class="fas fa-chart-line"></i> Trade Plans</a></li>
            <li><a href="deposit.php"><i class="fas fa-plus-circle"></i> Deposit/Withdrawal</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-users"></i> Request Connections</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-link"></i> View Connections</a></li>
            <li><a href="transactions.php"><i class="fas fa-history"></i> Transaction History</a></li>
            <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
            <li><a href="profile.php"><i class="fas fa-user-friends"></i> Ref. Users</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content">
        <div class="success-box">
            <h2><i class="fas fa-check-circle"></i> Plan Subscription Successful!</h2>
            <p>You have successfully subscribed to the <strong><?php echo htmlspecialchars($plan['name']); ?></strong> plan.</p>
            
            <div class="plan-details">
                <h3><?php echo htmlspecialchars($plan['name']); ?> - Level <?php echo (int)$plan['level']; ?></h3>
                <p><strong>Investment Range:</strong> <?php echo format_currency($plan['min_amount']); ?> - <?php echo format_currency($plan['max_amount']); ?></p>
                <p><strong>Max Leverage:</strong> x<?php echo htmlspecialchars($plan['max_leverage']); ?></p>
                <p><strong>Features:</strong> <?php echo htmlspecialchars($plan['features']); ?></p>
                <p><strong>Subscription Date:</strong> <?php echo date('F j, Y g:i A'); ?></p>
            </div>
            
            <div class="btn-group">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
                <a href="copy-trading.php">
                    <i class="fas fa-users"></i> Start Copy Trading
                </a>
                <a href="plans.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> View Other Plans
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>All Rights Reserved © Empires Markets 2025</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Close sidebar when clicking overlay
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            this.classList.remove('active');
        });

        // Auto-close sidebar on larger screens
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('sidebarOverlay').classList.remove('active');
            }
        });
    </script>
</body>
</html>