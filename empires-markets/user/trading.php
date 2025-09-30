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

// Get trading instruments
$instruments = get_trading_instruments();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Trading - <?php echo SITE_NAME; ?></title>
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
            <li><a href="trading.php" class="active"><i class="fas fa-exchange-alt"></i> Live Trading</a></li>
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
                <h1 class="welcome-text">Live Trading Terminal</h1>
            </div>

            <div class="trading-interface">
                <div class="trading-form-container">
                    <h2>Place a New Trade</h2>
                    <form id="trade-form" action="place_trade.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-group">
                            <label for="instrument">Instrument</label>
                            <select id="instrument" name="instrument" class="form-control" required>
                                <?php foreach ($instruments as $instrument): ?>
                                    <option value="<?php echo htmlspecialchars($instrument['symbol']); ?>">
                                        <?php echo htmlspecialchars($instrument['name']); ?> (<?php echo htmlspecialchars($instrument['symbol']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="trade_type">Trade Type</label>
                            <select id="trade_type" name="trade_type" class="form-control" required>
                                <option value="BUY">BUY</option>
                                <option value="SELL">SELL</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="volume">Volume (Lots)</label>
                            <input type="number" id="volume" name="volume" class="form-control" step="0.01" min="0.01" max="100" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Place Trade</button>
                    </form>
                </div>

                <div class="market-data-container">
                    <h2>Market Overview</h2>
                    <div id="market-data">
                        <!-- Prices will be loaded here via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const marketDataDiv = document.getElementById('market-data');

            async function fetchPrices() {
                try {
                    const response = await fetch('get_prices.php');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();

                    if (data.success && marketDataDiv) {
                        let html = '<ul>';
                        data.prices.forEach(item => {
                            const changeClass = item.change >= 0 ? 'price-up' : 'price-down';
                            html += `<li><strong>${item.symbol}</strong>: ${item.price} <span class="${changeClass}">(${item.change}%)</span></li>`;
                        });
                        html += '</ul>';
                        marketDataDiv.innerHTML = html;
                    } else {
                        marketDataDiv.innerHTML = `<p>${data.message || 'Could not load market data.'}</p>`;
                    }
                } catch (error) {
                    console.error("Error fetching prices:", error);
                    if (marketDataDiv) {
                        marketDataDiv.innerHTML = '<p>Error loading market data. Please try again later.</p>';
                    }
                }
            }

            // Fetch prices every 5 seconds
            setInterval(fetchPrices, 5000);
            // Initial fetch
            fetchPrices();
        });
    </script>
</body>
</html>