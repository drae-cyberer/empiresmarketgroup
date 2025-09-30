<?php
// User Transaction History Page
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

// Get filter parameters
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query conditions
$where_conditions = ['user_id = ?'];
$params = [$_SESSION['user_id']];

if ($type_filter) {
    $where_conditions[] = "type = ?";
    $params[] = $type_filter;
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

try {
    // Get total count
    $total_transactions = $db->count("SELECT COUNT(*) FROM transactions $where_clause", $params);
    
    // Get transactions
    $transactions = $db->select("
        SELECT * FROM transactions 
        $where_clause 
        ORDER BY created_at DESC 
        LIMIT $per_page OFFSET $offset
    ", $params);
    
    $total_pages = ceil($total_transactions / $per_page);
    
    // Get summary statistics
    $summary = $db->selectOne("
        SELECT 
            SUM(CASE WHEN type = 'DEPOSIT' AND status = 'COMPLETED' THEN amount ELSE 0 END) as total_deposits,
            SUM(CASE WHEN type = 'WITHDRAWAL' AND status = 'COMPLETED' THEN amount ELSE 0 END) as total_withdrawals,
            SUM(CASE WHEN type = 'TRADE' AND status = 'COMPLETED' THEN amount ELSE 0 END) as total_trades,
            SUM(CASE WHEN type = 'CREDIT' AND status = 'COMPLETED' THEN amount ELSE 0 END) as total_credits,
            SUM(CASE WHEN type = 'DEBIT' AND status = 'COMPLETED' THEN amount ELSE 0 END) as total_debits
        FROM transactions 
        WHERE user_id = ?
    ", [$_SESSION['user_id']]);
    
} catch (Exception $e) {
    $error_message = 'Error loading transactions: ' . $e->getMessage();
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Transaction History - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <div class="header-content">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <span class="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </span>
                    <div class="logo" style="display: flex; justify-content: center; align-items: center;">
    <div style="display: flex; align-items: center; gap: 12px; font-family: 'Segoe UI', sans-serif;">
        <i class="fas fa-chart-line" style="color: #e74c3c; font-size: 2rem;"></i>
        <img src="logo-white.png" alt="Logo" style="height: 40px; width: auto;">
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
            <li><a href="trades.php"><i class="fas fa-chart-line"></i> Trade Records</a></li>
            <li><a href="deposit.php"><i class="fas fa-plus-circle"></i> Deposit/Withdrawal</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-users"></i> Request Connections</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-link"></i> View Connections</a></li>
            <li><a href="transactions.php" class="active"><i class="fas fa-history"></i> Transaction History</a></li>
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
                <h1 class="welcome-text">Transaction History</h1>
                <p class="text-light">View all your account transactions and activities</p>
            </div>

            <!-- Summary Cards -->
            <?php if ($summary): ?>
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-icon deposits">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo format_currency($summary['total_deposits'] ?? 0); ?></h3>
                        <p>Total Deposits</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon withdrawals">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo format_currency($summary['total_withdrawals'] ?? 0); ?></h3>
                        <p>Total Withdrawals</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon trades">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo format_currency($summary['total_trades'] ?? 0); ?></h3>
                        <p>Total Trades</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon balance">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo format_currency($current_user['balance']); ?></h3>
                        <p>Current Balance</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Filter Transactions</h3>
                </div>
                <div style="padding: 20px;">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="type">Transaction Type</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="DEPOSIT" <?php echo $type_filter == 'DEPOSIT' ? 'selected' : ''; ?>>Deposits</option>
                                    <option value="WITHDRAWAL" <?php echo $type_filter == 'WITHDRAWAL' ? 'selected' : ''; ?>>Withdrawals</option>
                                    <option value="TRADE" <?php echo $type_filter == 'TRADE' ? 'selected' : ''; ?>>Trades</option>
                                    <option value="CREDIT" <?php echo $type_filter == 'CREDIT' ? 'selected' : ''; ?>>Credits</option>
                                    <option value="DEBIT" <?php echo $type_filter == 'DEBIT' ? 'selected' : ''; ?>>Debits</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="PENDING" <?php echo $status_filter == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="COMPLETED" <?php echo $status_filter == 'COMPLETED' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="CANCELLED" <?php echo $status_filter == 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="REJECTED" <?php echo $status_filter == 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_from">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="form-group">
                                <label for="date_to">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="transactions.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transaction History</h3>
                    <div style="margin-left: auto;">
                        <span class="text-muted">
                            Showing <?php echo count($transactions); ?> of <?php echo number_format($total_transactions); ?> transactions
                        </span>
                    </div>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
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
                                        <td>
                                            <code><?php echo htmlspecialchars($transaction['transaction_id']); ?></code>
                                        </td>
                                        <td>
                                            <span class="transaction-type <?php echo strtolower($transaction['type']); ?>">
                                                <?php 
                                                $type_icons = [
                                                    'DEPOSIT' => 'fas fa-arrow-down',
                                                    'WITHDRAWAL' => 'fas fa-arrow-up',
                                                    'TRADE' => 'fas fa-chart-line',
                                                    'CREDIT' => 'fas fa-plus',
                                                    'DEBIT' => 'fas fa-minus'
                                                ];
                                                $icon = $type_icons[$transaction['type']] ?? 'fas fa-circle';
                                                ?>
                                                <i class="<?php echo $icon; ?>"></i>
                                                <?php echo $transaction['type']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="amount <?php echo in_array($transaction['type'], ['WITHDRAWAL', 'DEBIT', 'TRADE']) ? 'negative' : 'positive'; ?>">
                                                <?php 
                                                $sign = in_array($transaction['type'], ['WITHDRAWAL', 'DEBIT', 'TRADE']) ? '-' : '+';
                                                echo $sign . format_currency($transaction['amount']); 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="transaction-description">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($transaction['status']); ?>">
                                                <?php echo $transaction['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="transaction-date">
                                                <div><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></div>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($transaction['created_at'])); ?></small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div style="padding: 40px;">
                                            <i class="fas fa-history" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px;"></i>
                                            <p>No transactions found</p>
                                            <p class="text-muted">Your transaction history will appear here</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="admin-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                        (<?php echo number_format($total_transactions); ?> total transactions)
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn btn-secondary">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
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

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
