<?php
// User Profile Page
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

$error_message = '';
$success_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate required fields
    if (empty($full_name) || empty($email)) {
        $error_message = 'Full name and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } else {
        // Check if email is already taken by another user
        $existing_user = get_user_by_email($email);
        if ($existing_user && $existing_user['id'] != $_SESSION['user_id']) {
            $error_message = 'Email address is already taken';
        } else {
            $update_data = [
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone
            ];
            
            // Handle password change
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error_message = 'Current password is required to change password';
                } elseif (!verify_password($current_password, $current_user['password'])) {
                    $error_message = 'Current password is incorrect';
                } elseif (strlen($new_password) < 6) {
                    $error_message = 'New password must be at least 6 characters';
                } elseif ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match';
                } else {
                    $update_data['password'] = hash_password($new_password);
                }
            }
            
            if (empty($error_message)) {
                try {
                    // Build update query
                    $fields = [];
                    $params = [];
                    foreach ($update_data as $field => $value) {
                        $fields[] = "$field = ?";
                        $params[] = $value;
                    }
                    $params[] = $_SESSION['user_id'];
                    
                    $query = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
                    $updated = $db->update($query, $params);
                    
                    if ($updated) {
                        $success_message = 'Profile updated successfully';
                        // Update session email if changed
                        if (isset($update_data['email'])) {
                            $_SESSION['user_email'] = $update_data['email'];
                        }
                        // Refresh user data
                        $current_user = get_user_by_id($_SESSION['user_id']);
                    } else {
                        $error_message = 'Failed to update profile';
                    }
                } catch (Exception $e) {
                    $error_message = 'Error updating profile: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get user's referrals
try {
    $referrals = $db->select(
        "SELECT id, username, full_name, email, created_at, account_status 
         FROM users 
         WHERE referred_by = ? 
         ORDER BY created_at DESC",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
    $referrals = [];
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Profile - <?php echo SITE_NAME; ?></title>
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
            <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Account Update</a></li>
            <li><a href="trades.php"><i class="fas fa-chart-line"></i> Trade Records</a></li>
            <li><a href="plans.php" class="active"><i class="fas fa-chart-line"></i> Trade Plans</a></li>
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
                <h1 class="welcome-text">Account Profile</h1>
                <p class="text-light">Manage your account information and settings</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Information -->
            <div class="row">
                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Profile Information</h3>
                        </div>
                        <div style="padding: 20px;">
                            <form method="POST" action="" data-validate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input 
                                            type="text" 
                                            id="full_name" 
                                            name="full_name" 
                                            class="form-control" 
                                            required 
                                            value="<?php echo htmlspecialchars($current_user['full_name']); ?>"
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label for="username" class="form-label">Username</label>
                                        <input 
                                            type="text" 
                                            id="username" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($current_user['username']); ?>"
                                            readonly
                                            style="background-color: var(--secondary-dark); opacity: 0.7;"
                                        >
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input 
                                            type="email" 
                                            id="email" 
                                            name="email" 
                                            class="form-control" 
                                            required 
                                            value="<?php echo htmlspecialchars($current_user['email']); ?>"
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input 
                                            type="tel" 
                                            id="phone" 
                                            name="phone" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>

                                <hr style="margin: 30px 0; border-color: var(--border-color);">

                                <h4 style="margin-bottom: 20px; color: var(--text-white);">Change Password</h4>
                                <p class="text-muted" style="margin-bottom: 20px;">Leave password fields empty if you don't want to change your password</p>

                                <div class="form-group">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input 
                                        type="password" 
                                        id="current_password" 
                                        name="current_password" 
                                        class="form-control" 
                                        placeholder="Enter current password"
                                    >
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input 
                                            type="password" 
                                            id="new_password" 
                                            name="new_password" 
                                            class="form-control" 
                                            placeholder="Enter new password"
                                            minlength="6"
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input 
                                            type="password" 
                                            id="confirm_password" 
                                            name="confirm_password" 
                                            class="form-control" 
                                            placeholder="Confirm new password"
                                            minlength="6"
                                        >
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <!-- Account Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Account Summary</h3>
                        </div>
                        <div style="padding: 20px;">
                            <div class="stat-card" style="margin: 0 0 15px 0;">
                                <div class="stat-icon balance">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo format_currency($current_user['balance']); ?></h3>
                                    <p>Current Balance</p>
                                </div>
                            </div>
                            
                            <div style="padding: 15px 0; border-top: 1px solid var(--border-color);">
                                <p><strong>Account Status:</strong> 
                                    <span class="status-badge <?php echo strtolower($current_user['account_status']); ?>">
                                        <?php echo $current_user['account_status']; ?>
                                    </span>
                                </p>
                                <p><strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($current_user['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($current_user['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Referral Link -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Referral Program</h3>
                        </div>
                        <div style="padding: 20px;">
                            <p class="text-muted" style="margin-bottom: 15px;">Share your referral link and earn commissions</p>
                            <div class="form-group">
                                <label class="form-label">Your Referral Link</label>
                                <div style="display: flex; gap: 10px;">
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        value="<?php echo SITE_URL; ?>/user/register.php?ref=<?php echo $current_user['id']; ?>"
                                        readonly
                                        id="referralLink"
                                    >
                                    <button type="button" class="btn btn-secondary" onclick="copyReferralLink()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-muted" style="font-size: 0.8rem; margin-top: 10px;">
                                Total Referrals: <strong><?php echo count($referrals); ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Referrals Table -->
            <?php if (!empty($referrals)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Your Referrals</h3>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referrals as $referral): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($referral['username']); ?></td>
                                    <td><?php echo htmlspecialchars($referral['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($referral['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($referral['account_status']); ?>">
                                            <?php echo $referral['account_status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($referral['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Copy referral link function
        function copyReferralLink() {
            const referralInput = document.getElementById('referralLink');
            referralInput.select();
            referralInput.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                EmpiresMarkets.showAlert('Referral link copied to clipboard!', 'success');
            } catch (err) {
                EmpiresMarkets.showAlert('Failed to copy link', 'danger');
            }
        }
    </script>
</body>
</html>
