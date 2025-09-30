<?php
// Plans Page
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

// Load plans
$plans = get_investment_plans();

// Check if user already has a selected plan
$user_has_plan = !empty($current_user['plan_id']);
$user_current_plan = null;

if ($user_has_plan) {
    // Get user's current plan details
    global $db;
    $user_current_plan = $db->selectOne("
        SELECT * FROM plans WHERE id = ? AND status = 'ACTIVE'
    ", [$current_user['plan_id']]);
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Plans - <?php echo SITE_NAME; ?></title>

    <!-- Core Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* Plans grid */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 2rem 0;
        }
        .plan-card {
            background: #1f1f1f;
            border-radius: 12px;
            padding: 20px;
            color: #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease;
            position: relative;
        }
        .plan-card:hover {
            transform: translateY(-5px);
        }
        .plan-card.current-plan {
            border: 3px solid #27ae60;
            background: linear-gradient(135deg, #1f1f1f 0%, #2d5a2d 100%);
        }
        .plan-card.disabled {
            opacity: 0.6;
            background: #2a2a2a;
        }
        .plan-card.disabled:hover {
            transform: none;
        }
        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .plan-level {
            background: #e74c3c;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .plan-level.current {
            background: #27ae60;
        }
        .plan-name {
            font-size: 1.4rem;
            font-weight: bold;
        }
        .plan-body {
            margin: 15px 0;
        }
        .plan-body p {
            margin: 6px 0;
            font-size: 0.95rem;
            opacity: 0.9;
        }
        .plan-features {
            margin-top: 10px;
            font-size: 0.9rem;
            padding-left: 15px;
            list-style: disc;
            opacity: 0.85;
        }
        .plan-footer {
            margin-top: auto;
            text-align: center;
        }
        .plan-footer button {
            background: #e74c3c;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
            width: 100%;
        }
        .plan-footer button:hover {
            background: #c0392b;
        }
        .plan-footer button:disabled {
            background: #666;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .plan-footer button:disabled:hover {
            background: #666;
        }
        .plan-footer button.current-plan-btn {
            background: #27ae60;
        }
        .plan-footer button.current-plan-btn:hover {
            background: #219a52;
        }
        
        /* Current Plan Alert */
        .current-plan-alert {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 4px 8px rgba(39, 174, 96, 0.3);
        }
        .current-plan-alert h3 {
            margin: 0 0 10px 0;
            font-size: 1.3rem;
        }
        .current-plan-alert p {
            margin: 5px 0;
            opacity: 0.9;
        }
        .current-plan-alert .plan-details {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: left;
        }
        .current-plan-alert .plan-details h4 {
            margin: 0 0 10px 0;
            color: #fff;
        }

        /* Plan Status Badges */
        .plan-status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .plan-status-badge.current {
            background: #27ae60;
            color: white;
        }
        .plan-status-badge.unavailable {
            background: #666;
            color: #ccc;
        }

        @media (max-width: 768px) {
            .plans-grid {
                gap: 15px;
                grid-template-columns: 1fr;
            }
            .plan-card {
                padding: 15px;
            }
            .current-plan-alert {
                padding: 15px;
                margin-bottom: 1.5rem;
            }
            .current-plan-alert h3 {
                font-size: 1.1rem;
            }
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
            <li><a href="plans.php" class="active"><i class="fas fa-chart-line"></i> Trade Plans</a></li>
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

    <!-- Main -->
    <main class="main-content">
        <div class="dashboard-content">
            <h1 style="margin-bottom:1rem;">Investment Plans</h1>
            
            <?php if ($user_has_plan && $user_current_plan): ?>
                <!-- Current Plan Alert -->
                <div class="current-plan-alert">
                    <h3><i class="fas fa-check-circle"></i> You have an active plan!</h3>
                    <p>You are currently subscribed to the <strong><?php echo htmlspecialchars($user_current_plan['name']); ?></strong> plan.</p>
                    
                    <div class="plan-details">
                        <h4><?php echo htmlspecialchars($user_current_plan['name']); ?> - Level <?php echo (int)$user_current_plan['level']; ?></h4>
                        <ul class="plan-features" style="list-style: none; padding: 0;">
                            <li><strong>Investment Range:</strong> <?php echo format_currency($user_current_plan['min_amount']); ?> - <?php echo $user_current_plan['max_amount'] ? format_currency($user_current_plan['max_amount']) : 'Unlimited'; ?></li>
                            <li><strong>Max Leverage:</strong> 1:<?php echo htmlspecialchars($user_current_plan['max_leverage']); ?></li>
                            <li><strong>Instruments:</strong> Up to <?php echo htmlspecialchars($user_current_plan['instruments_count']); ?></li>
                            <li><strong>Support:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user_current_plan['support_level']))); ?></li>
                            <?php if ($user_current_plan['analysis_access']): ?><li><i class="fas fa-check-circle" style="color: #27ae60;"></i> Advanced Analysis</li><?php endif; ?>
                            <?php if ($user_current_plan['copy_trading_access']): ?><li><i class="fas fa-check-circle" style="color: #27ae60;"></i> Copy Trading</li><?php endif; ?>
                            <?php if ($user_current_plan['api_access']): ?><li><i class="fas fa-check-circle" style="color: #27ae60;"></i> API Access</li><?php endif; ?>
                        </ul>
                        <?php if ($current_user['plan_subscribed_at']): ?>
                            <p style="margin-top: 10px;"><strong>Subscribed:</strong> <?php echo date('M j, Y g:i A', strtotime($current_user['plan_subscribed_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <p><i class="fas fa-info-circle"></i> You can only have one active plan at a time. Contact support if you need to change your plan.</p>
                </div>
            <?php else: ?>
                <!-- No Plan Selected Message -->
                <div style="background: #3498db; color: white; padding: 15px; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
                    <p><i class="fas fa-info-circle"></i> Choose an investment plan that suits your trading goals and budget.</p>
                </div>
            <?php endif; ?>

            <div class="plans-grid">
                <?php if ($plans && count($plans) > 0): ?>
                    <?php foreach ($plans as $plan): ?>
                        <?php 
                        $is_current_plan = $user_has_plan && $user_current_plan && $plan['id'] == $user_current_plan['id'];
                        $is_disabled = $user_has_plan && !$is_current_plan;
                        ?>
                        <div class="plan-card <?php echo $is_current_plan ? 'current-plan' : ''; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>">
                            
                            <?php if ($is_current_plan): ?>
                                <div class="plan-status-badge current">Current Plan</div>
                            <?php elseif ($is_disabled): ?>
                                <div class="plan-status-badge unavailable">Unavailable</div>
                            <?php endif; ?>
                            
                            <div class="plan-header">
                                <span class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></span>
                                <span class="plan-level <?php echo $is_current_plan ? 'current' : ''; ?>">Level <?php echo (int)$plan['level']; ?></span>
                            </div>
                            <div class="plan-body">
                                <p><strong>Investment:</strong> <?php echo format_currency($plan['min_amount']); ?> - <?php echo $plan['max_amount'] ? format_currency($plan['max_amount']) : 'Unlimited'; ?></p>
                                <ul class="plan-features">
                                    <li><strong>Max Leverage:</strong> 1:<?php echo htmlspecialchars($plan['max_leverage']); ?></li>
                                    <li><strong>Instruments:</strong> Up to <?php echo htmlspecialchars($plan['instruments_count']); ?></li>
                                    <li><strong>Spread Reduction:</strong> <?php echo htmlspecialchars($plan['spread_reduction'] * 100); ?>%</li>
                                    <li><strong>Support:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $plan['support_level']))); ?></li>
                                    <?php if ($plan['analysis_access']): ?><li>Advanced Analysis Tools</li><?php endif; ?>
                                    <?php if ($plan['copy_trading_access']): ?><li>Copy Trading Access</li><?php endif; ?>
                                    <?php if ($plan['api_access']): ?><li>API Access</li><?php endif; ?>
                                </ul>
                            </div>
                            
                            <div class="plan-footer">
                                <?php if ($is_current_plan): ?>
                                    <button class="current-plan-btn" disabled>
                                        <i class="fas fa-check"></i> Current Plan
                                    </button>
                                <?php elseif ($is_disabled): ?>
                                    <button disabled>
                                        <i class="fas fa-lock"></i> Not Available
                                    </button>
                                <?php else: ?>
                                    <a href="subscribe_plan.php?plan_id=<?php echo (int)$plan['id']; ?>" style="text-decoration: none;">
                                        <button>
                                            <i class="fas fa-arrow-right"></i> Select Plan
                                        </button>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; color: #666; padding: 2rem;">
                        <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No active plans available right now.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($user_has_plan): ?>
                <!-- Help Section for Users with Plans -->
                <div style="background: #f8f9fa; color: #333; padding: 20px; border-radius: 8px; margin-top: 2rem; text-align: center;">
                    <h3 style="margin-bottom: 10px; color: #333;"><i class="fas fa-question-circle"></i> Need to Change Your Plan?</h3>
                    <p style="margin-bottom: 15px;">If you need to upgrade, downgrade, or change your current plan, please contact our support team.</p>
                    <a href="support.php" style="background: #3498db; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-headset"></i> Contact Support
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>All Rights Reserved © Empires Markets 2025</p>
        </div>
    </footer>

    <!-- JS (reuse dashboard scripts) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeSidebar();
            
            // Add click prevention for disabled plan cards
            const disabledCards = document.querySelectorAll('.plan-card.disabled');
            disabledCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Show tooltip or alert
                    const tooltip = document.createElement('div');
                    tooltip.textContent = 'You already have an active plan. Contact support to change plans.';
                    tooltip.style.cssText = `
                        position: absolute;
                        top: -40px;
                        left: 50%;
                        transform: translateX(-50%);
                        background: #333;
                        color: white;
                        padding: 8px 12px;
                        border-radius: 4px;
                        font-size: 12px;
                        white-space: nowrap;
                        z-index: 1000;
                        pointer-events: none;
                    `;
                    
                    card.style.position = 'relative';
                    card.appendChild(tooltip);
                    
                    setTimeout(() => {
                        if (tooltip.parentNode) {
                            tooltip.parentNode.removeChild(tooltip);
                        }
                    }, 3000);
                });
            });
        });
    </script>
</body>
</html>