<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in
require_login();

// Get current user
$current_user = get_user_by_id($_SESSION['user_id']);
if (!$current_user) {
    header('Location: login.php');
    exit();
}

// Get trade history
$filters = [
    'instrument' => $_GET['instrument'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
];
$trades = get_detailed_trade_history($_SESSION['user_id'], array_filter($filters));
$instruments = get_trading_instruments();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade History - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <a href="dashboard.php" style="display: flex; align-items: center; gap: 12px; color: white; text-decoration: none;">
                    <i class="fas fa-chart-line" style="color: #e74c3c; font-size: 2rem;"></i>
                    <span style="font-size: 1.5rem; font-weight: bold;"><?php echo SITE_NAME; ?></span>
                </a>
            </div>
            <div class="nav-icons">
                <span class="nav-icon"><i class="fas fa-bell"></i></span>
                <span class="kyc-badge">KYC</span>
                <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
            </div>
        </div>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="trading.php"><i class="fas fa-exchange-alt"></i> Live Trading</a></li>
            <li><a href="trade-history.php" class="active"><i class="fas fa-history"></i> Trade History</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Account Update</a></li>
            <li><a href="trades.php"><i class="fas fa-chart-line"></i> Trade Records</a></li>
            <li><a href="plans.php"><i class="fas fa-list-alt"></i> Trade plans</a></li>
            <li><a href="deposit.php"><i class="fas fa-plus-circle"></i> Deposit/Withdrawal</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-users"></i> Request Connections</a></li>
            <li><a href="transactions.php"><i class="fas fa-history"></i> Transaction History</a></li>
            <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="dashboard-content">
            <div class="welcome-section">
                <h1 class="welcome-text">Your Trade History</h1>
            </div>

            <div class="trade-history-container">
                <div class="filters-container" style="margin-bottom: 20px;">
                    <form action="trade-history.php" method="GET" class="form-inline">
                        <div class="form-group">
                            <label for="instrument">Instrument</label>
                            <select name="instrument" id="instrument" class="form-control">
                                <option value="">All</option>
                                <?php foreach ($instruments as $instrument): ?>
                                    <option value="<?php echo htmlspecialchars($instrument['symbol']); ?>" <?php echo (isset($_GET['instrument']) && $_GET['instrument'] == $instrument['symbol']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($instrument['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Instrument</th>
                                <th>Type</th>
                                <th>Volume</th>
                                <th>Open Price</th>
                                <th>Status</th>
                                <th>Opened At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($trades)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No trades found matching your criteria.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($trades as $trade): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trade['instrument_name'] ?? $trade['instrument']); ?></td>
                                        <td class="<?php echo strtolower($trade['trade_type']); ?>"><?php echo htmlspecialchars($trade['trade_type']); ?></td>
                                        <td><?php echo htmlspecialchars($trade['volume']); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($trade['open_price'], 5)); ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower($trade['status']); ?>"><?php echo htmlspecialchars($trade['status']); ?></span></td>
                                        <td><?php echo htmlspecialchars(format_date($trade['opened_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
    </footer>

</body>
</html>