<?php
// Admin Settings
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

// Handle settings update
$message = '';
$message_type = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_market_data':
                $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
                $name = trim($_POST['name'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $change_percent = floatval($_POST['change_percent'] ?? 0);
                $volume = intval($_POST['volume'] ?? 0);
                
                if ($symbol && $name && $price) {
                    // Check if market data exists
                    $existing = $db->selectOne("SELECT id FROM market_data WHERE symbol = ?", [$symbol]);
                    
                    if ($existing) {
                        $db->update("UPDATE market_data SET name = ?, price = ?, change_percent = ?, volume = ?, updated_at = NOW() WHERE symbol = ?", 
                                   [$name, $price, $change_percent, $volume, $symbol]);
                    } else {
                        $db->insert("INSERT INTO market_data (symbol, name, price, change_percent, volume) VALUES (?, ?, ?, ?, ?)", 
                                   [$symbol, $name, $price, $change_percent, $volume]);
                    }
                    $message = 'Market data updated successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Please fill all required fields.';
                    $message_type = 'error';
                }
                break;
                
            case 'add_admin':
                if ($admin_role !== 'SUPER_ADMIN') {
                    $message = 'Only super admins can add new admin users.';
                    $message_type = 'error';
                    break;
                }
                
                $username = trim($_POST['admin_username'] ?? '');
                $password = $_POST['admin_password'] ?? '';
                $role = $_POST['admin_role'] ?? 'ADMIN';
                
                if ($username && $password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $db->insert("INSERT INTO admin_users (username, password, role) VALUES (?, ?, ?)", 
                               [$username, $hashed_password, $role]);
                    $message = 'Admin user added successfully.';
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

// Get current market data
try {
    $market_data = $db->select("SELECT * FROM market_data ORDER BY symbol ASC");
    $admin_users = $db->select("SELECT id, username, role, created_at FROM admin_users ORDER BY created_at DESC");
} catch (Exception $e) {
    $error_message = 'Error loading settings: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <i class="fas fa-shield-alt"></i>
                <span>Admin Panel</span>
            </div>
            <div class="admin-user-info">
                <span class="admin-welcome">Welcome, <?php echo htmlspecialchars($admin_username); ?></span>
                <span class="admin-role"><?php echo htmlspecialchars($admin_role); ?></span>
                <a href="logout.php" class="admin-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="deposits.php"><i class="fas fa-arrow-down"></i> Deposits</a></li>
                <li><a href="withdrawals.php"><i class="fas fa-arrow-up"></i> Withdrawals</a></li>
                <li><a href="trades.php"><i class="fas fa-chart-line"></i> Trades</a></li>
                <li><a href="traders.php"><i class="fas fa-user-tie"></i> Traders</a></li>
                <li><a href="plans.php"><i class="fas fa-layer-group"></i> Plans</a></li>
                <li><a href="transactions.php"><i class="fas fa-history"></i> Transactions</a></li>
                <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-content">
            <div class="admin-page-header">
                <h1>System Settings</h1>
                <p>Manage system configuration and settings</p>
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

            <!-- Settings Sections -->
            <div class="settings-sections">
                
                <!-- Market Data Section -->
                <div class="settings-section">
                    <h2><i class="fas fa-chart-line"></i> Market Data Management</h2>
                    <p>Update market prices and data displayed on the dashboard</p>
                    
                    <div class="settings-content">
                        <div class="add-market-data">
                            <h3>Add/Update Market Data</h3>
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_market_data">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="symbol">Symbol:</label>
                                        <input type="text" name="symbol" id="symbol" placeholder="BTCUSDT" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Name:</label>
                                        <input type="text" name="name" id="name" placeholder="Bitcoin / TetherUS" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="price">Price:</label>
                                        <input type="number" step="0.000001" name="price" id="price" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="change_percent">Change %:</label>
                                        <input type="number" step="0.01" name="change_percent" id="change_percent">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="volume">Volume:</label>
                                    <input type="number" name="volume" id="volume">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Market Data
                                </button>
                            </form>
                        </div>

                        <div class="current-market-data">
                            <h3>Current Market Data</h3>
                            <div class="admin-table-container">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Symbol</th>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Change %</th>
                                            <th>Volume</th>
                                            <th>Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($market_data): ?>
                                            <?php foreach ($market_data as $data): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($data['symbol']); ?></td>
                                                    <td><?php echo htmlspecialchars($data['name']); ?></td>
                                                    <td><?php echo format_currency($data['price']); ?></td>
                                                    <td class="<?php echo $data['change_percent'] >= 0 ? 'positive' : 'negative'; ?>">
                                                        <?php echo ($data['change_percent'] >= 0 ? '+' : '') . $data['change_percent']; ?>%
                                                    </td>
                                                    <td><?php echo number_format($data['volume']); ?></td>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($data['updated_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No market data found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Users Section -->
                <?php if ($admin_role === 'SUPER_ADMIN'): ?>
                <div class="settings-section">
                    <h2><i class="fas fa-users-cog"></i> Admin Users Management</h2>
                    <p>Manage admin user accounts</p>
                    
                    <div class="settings-content">
                        <div class="add-admin-user">
                            <h3>Add New Admin User</h3>
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="add_admin">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="admin_username">Username:</label>
                                        <input type="text" name="admin_username" id="admin_username" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="admin_password">Password:</label>
                                        <input type="password" name="admin_password" id="admin_password" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="admin_role">Role:</label>
                                    <select name="admin_role" id="admin_role" required>
                                        <option value="ADMIN">Admin</option>
                                        <option value="SUPER_ADMIN">Super Admin</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Add Admin User
                                </button>
                            </form>
                        </div>

                        <div class="current-admin-users">
                            <h3>Current Admin Users</h3>
                            <div class="admin-table-container">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($admin_users): ?>
                                            <?php foreach ($admin_users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td>
                                                        <span class="role-badge <?php echo strtolower(str_replace('_', '-', $user['role'])); ?>">
                                                            <?php echo $user['role']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No admin users found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- System Information Section -->
                <div class="settings-section">
                    <h2><i class="fas fa-info-circle"></i> System Information</h2>
                    <p>Current system configuration and information</p>
                    
                    <div class="settings-content">
                        <div class="system-info-grid">
                            <div class="info-card">
                                <h4>Site Configuration</h4>
                                <ul>
                                    <li><strong>Site Name:</strong> <?php echo SITE_NAME; ?></li>
                                    <li><strong>Site URL:</strong> <?php echo SITE_URL; ?></li>
                                    <li><strong>Site Email:</strong> <?php echo SITE_EMAIL; ?></li>
                                </ul>
                            </div>
                            
                            <div class="info-card">
                                <h4>Trading Configuration</h4>
                                <ul>
                                    <li><strong>Min Deposit:</strong> <?php echo format_currency(MIN_DEPOSIT); ?></li>
                                    <li><strong>Min Withdrawal:</strong> <?php echo format_currency(MIN_WITHDRAWAL); ?></li>
                                    <li><strong>Withdrawal Fee:</strong> <?php echo (WITHDRAWAL_FEE * 100); ?>%</li>
                                </ul>
                            </div>
                            
                            <div class="info-card">
                                <h4>Database Configuration</h4>
                                <ul>
                                    <li><strong>Host:</strong> <?php echo DB_HOST; ?></li>
                                    <li><strong>Database:</strong> <?php echo DB_NAME; ?></li>
                                    <li><strong>User:</strong> <?php echo DB_USER; ?></li>
                                </ul>
                            </div>
                            
                            <div class="info-card">
                                <h4>Session Configuration</h4>
                                <ul>
                                    <li><strong>Session Lifetime:</strong> <?php echo SESSION_LIFETIME; ?> seconds</li>
                                    <li><strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?></li>
                                    <li><strong>Max File Size:</strong> <?php echo number_format(MAX_FILE_SIZE / 1024 / 1024, 1); ?> MB</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
