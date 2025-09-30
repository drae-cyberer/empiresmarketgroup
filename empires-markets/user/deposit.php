<?php
// User Deposit/Withdrawal Page with Withdrawal Codes
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

// Handle deposit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_deposit'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    $deposit_type = sanitize_input($_POST['deposit_type'] ?? '');
    
    if ($amount < MIN_DEPOSIT) {
        $error_message = 'Minimum deposit amount is ' . format_currency(MIN_DEPOSIT);
    } elseif (empty($deposit_type)) {
        $error_message = 'Please select a deposit method';
    } else {
        // Handle file upload for proof
        $proof_image = null;
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_file($_FILES['proof_image'], '../uploads/deposits/');
            if ($upload_result['success']) {
                $proof_image = $upload_result['filename'];
            } else {
                $error_message = $upload_result['message'];
            }
        }
        
        if (empty($error_message)) {
            $deposit_id = create_deposit($_SESSION['user_id'], $amount, $deposit_type, $proof_image);
            if ($deposit_id) {
                $success_message = 'Deposit request submitted successfully. It will be processed within 24 hours.';
            } else {
                $error_message = 'Failed to submit deposit request. Please try again.';
            }
        }
    }
}

// Handle withdrawal form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_withdrawal'])) {
    $amount = floatval($_POST['withdrawal_amount'] ?? 0);
    $method = sanitize_input($_POST['withdrawal_method'] ?? '');
    $wallet_address = sanitize_input($_POST['wallet_address'] ?? '');
    $withdrawal_code = sanitize_input($_POST['withdrawal_code'] ?? '');
    $confirmation_code = sanitize_input($_POST['confirmation_code'] ?? '');
    
    if ($amount < MIN_WITHDRAWAL) {
        $error_message = 'Minimum withdrawal amount is ' . format_currency(MIN_WITHDRAWAL);
    } elseif ($amount > $current_user['balance']) {
        $error_message = 'Insufficient balance. Available: ' . format_currency($current_user['balance']);
    } elseif (empty($method)) {
        $error_message = 'Please select a withdrawal method';
    } elseif (empty($wallet_address)) {
        $error_message = 'Please provide wallet address or account details';
    } elseif (empty($withdrawal_code)) {
        $error_message = 'Please enter withdrawal code';
    } elseif (empty($confirmation_code)) {
        $error_message = 'Please enter confirmation code';
    } else {
        // Validate withdrawal codes
        if ($withdrawal_code !== $current_user['withdrawal_code']) {
            $error_message = 'Invalid withdrawal code. Please contact support if you need assistance.';
        } elseif ($confirmation_code !== $current_user['confirmation_code']) {
            $error_message = 'Invalid confirmation code. Please contact support if you need assistance.';
        } else {
            // Create withdrawal with used codes
            $withdrawal_id = create_withdrawal_with_codes($_SESSION['user_id'], $amount, $method, $wallet_address, $withdrawal_code, $confirmation_code);
            if ($withdrawal_id) {
                $success_message = 'Withdrawal request submitted successfully. It will be processed within 24-48 hours.';
                // Clean up session data after successful withdrawal
                unset($_SESSION['withdrawal_data']);
                unset($_SESSION['withdrawal_code_valid']);
                // Refresh user data
                $current_user = get_user_by_id($_SESSION['user_id']);
            } else {
                $error_message = 'Failed to submit withdrawal request. Please try again.';
            }
        }
    }
}

// Handle step validation for withdrawal
$withdrawal_step = 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_step1'])) {
    $amount = floatval($_POST['withdrawal_amount'] ?? 0);
    $method = sanitize_input($_POST['withdrawal_method'] ?? '');
    $wallet_address = sanitize_input($_POST['wallet_address'] ?? '');
    
    if ($amount >= MIN_WITHDRAWAL && $amount <= $current_user['balance'] && !empty($method) && !empty($wallet_address)) {
        $withdrawal_step = 2;
        $_SESSION['withdrawal_data'] = [
            'amount' => $amount,
            'method' => $method,
            'wallet_address' => $wallet_address
        ];
    } else {
        if ($amount < MIN_WITHDRAWAL) {
            $error_message = 'Minimum withdrawal amount is ' . format_currency(MIN_WITHDRAWAL);
        } elseif ($amount > $current_user['balance']) {
            $error_message = 'Insufficient balance. Available: ' . format_currency($current_user['balance']);
        } elseif (empty($method)) {
            $error_message = 'Please select a withdrawal method';
        } elseif (empty($wallet_address)) {
            $error_message = 'Please provide wallet address or account details';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_step2'])) {
    $withdrawal_code = sanitize_input($_POST['withdrawal_code'] ?? '');
    
    if ($withdrawal_code === $current_user['withdrawal_code']) {
        $withdrawal_step = 3;
        $_SESSION['withdrawal_code_valid'] = true;
    } else {
        $withdrawal_step = 2;
        $error_message = 'Invalid withdrawal code. Please try again.';
    }
}

// Check session state
if (isset($_SESSION['withdrawal_data']) && !isset($_POST['validate_step1'])) {
    if (isset($_SESSION['withdrawal_code_valid'])) {
        $withdrawal_step = 3;
    } else {
        $withdrawal_step = 2;
    }
}

// Handle withdrawal reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_withdrawal'])) {
    unset($_SESSION['withdrawal_data']);
    unset($_SESSION['withdrawal_code_valid']);
    exit(); // Just exit as this is an AJAX call
}

// Updated function to create withdrawal with codes
function create_withdrawal_with_codes($user_id, $amount, $method, $wallet_address, $withdrawal_code, $confirmation_code) {
    global $db;
    
    try {
        $transaction_id = 'WTH_' . date('YmdHis') . '_' . rand(1000, 9999);
        
        $db->beginTransaction();
        
        // Create withdrawal record
        $withdrawal_id = $db->insert("
            INSERT INTO withdrawals (user_id, transaction_id, amount, withdrawal_method, wallet_address, withdrawal_code_used, confirmation_code_used, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW())
        ", [$user_id, $transaction_id, $amount, $method, $wallet_address, $withdrawal_code, $confirmation_code]);
        
        // Update user balance (deduct amount)
        $db->update("UPDATE users SET balance = balance - ? WHERE id = ?", [$amount, $user_id]);
        
        // Create transaction record
        $db->insert("
            INSERT INTO transactions (user_id, transaction_id, type, amount, description, status, created_at) 
            VALUES (?, ?, 'WITHDRAWAL', ?, ?, 'PENDING', NOW())
        ", [$user_id, $transaction_id, -$amount, "Withdrawal request: $method"]);
        
        $db->commit();
        return $withdrawal_id;
        
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

// Get recent deposits and withdrawals
$recent_deposits = get_user_deposits($_SESSION['user_id'], 10);
$recent_withdrawals = get_user_withdrawals($_SESSION['user_id'], 10);

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Deposit/Withdrawal - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Additional styles for withdrawal codes and steps */
        .withdrawal-codes-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .codes-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .code-info-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .code-info-box h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .code-display {
            font-size: 18px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 5px;
            letter-spacing: 2px;
        }
        
        .withdrawal-info-alert {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .withdrawal-info-alert i {
            margin-right: 10px;
        }
        
        /* Step indicators */
        .withdrawal-steps {
            display: flex;
            gap: 10px;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: #007bff;
            color: white;
        }
        
        /* Step headers */
        .step-header {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .step-header h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .step-header p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Withdrawal summary */
        .withdrawal-summary {
            background: #e8f5e8;
            border: 1px solid #d4edda;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .withdrawal-summary h5 {
            margin: 0 0 10px 0;
            color: #155724;
            font-size: 16px;
        }
        
        .withdrawal-summary p {
            margin: 5px 0;
            color: #155724;
            font-size: 14px;
        }
        
        /* Code inputs */
        .code-input {
            font-family: 'Courier New', monospace;
            text-align: center;
            letter-spacing: 2px;
            font-size: 16px;
            font-weight: 600;
        }

        /* Updated Withdrawal Form Styles */
        .withdrawal-card {
            background: #2c3e50;
            border: none;
            border-radius: 15px;
            color: white;
            overflow: hidden;
        }

        .withdrawal-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 20px;
        }

        .withdrawal-card .card-title {
            color: white;
            font-size: 1.2rem;
            margin: 0;
            font-weight: 600;
        }

        .withdrawal-form-container {
            padding: 25px;
        }

        .available-balance {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 1.1rem;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .withdrawal-form .form-group {
            margin-bottom: 20px;
        }

        .withdrawal-form .form-label {
            display: block;
            color: #bdc3c7;
            font-size: 0.9rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .amount-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .currency-symbol {
            position: absolute;
            left: 15px;
            color: #ecf0f1;
            font-size: 1.1rem;
            font-weight: 600;
            z-index: 2;
        }

        .amount-input {
            width: 100%;
            padding: 15px 15px 15px 40px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .amount-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255,255,255,0.15);
        }

        .amount-input::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .withdrawal-select {
            width: 100%;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }

        .withdrawal-select:focus {
            outline: none;
            border-color: #3498db;
            background-color: rgba(255,255,255,0.15);
        }

        .withdrawal-select option {
            background: #34495e;
            color: white;
            padding: 10px;
        }

        .withdrawal-textarea {
            width: 100%;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            resize: vertical;
            min-height: 80px;
        }

        .withdrawal-textarea:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255,255,255,0.15);
        }

        .withdrawal-textarea::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .withdrawal-token-input {
            width: 100%;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .withdrawal-token-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255,255,255,0.15);
        }

        .withdrawal-token-input::placeholder {
            color: rgba(255,255,255,0.5);
            letter-spacing: normal;
        }

        .withdrawal-submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .withdrawal-submit-btn:hover {
            background: linear-gradient(135deg, #2980b9, #1f4e79);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }

        .withdrawal-submit-btn:active {
            transform: translateY(0);
        }
        
        @media (max-width: 768px) {
            .codes-info {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .withdrawal-steps {
                justify-content: center;
            }

            .withdrawal-form-container {
                padding: 20px;
            }
            
            .available-balance {
                font-size: 1rem;
                padding: 12px;
            }
            
            .amount-input,
            .withdrawal-select,
            .withdrawal-textarea,
            .withdrawal-token-input,
            .withdrawal-submit-btn {
                padding: 12px;
                font-size: 1rem;
            }
            
            .currency-symbol {
                left: 12px;
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
            <li><a href="plans.php" ><i class="fas fa-chart-line"></i> Trade Plans</a></li>
            <li><a href="deposit.php" class="active"><i class="fas fa-plus-circle"></i> Deposit/Withdrawal</a></li>
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
                <h1 class="welcome-text">Deposit & Withdrawal</h1>
                <p class="text-light">Manage your account funding and withdrawals</p>
            </div>

            <!-- Current Balance -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Account Balance</h3>
                </div>
                <div style="padding: 20px;">
                    <div class="stat-card" style="margin: 0;">
                        <div class="stat-icon balance">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo format_currency($current_user['balance']); ?></h3>
                            <p>Available Balance</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Deposit/Withdrawal Forms -->
            <div class="row">
                <!-- Deposit Form -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Make a Deposit</h3>
                        </div>
                        <div style="padding: 20px;">
                            <form method="POST" action="" enctype="multipart/form-data" data-validate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <div class="form-group">
                                    <label for="amount" class="form-label">Deposit Amount *</label>
                                    <input 
                                        type="number" 
                                        id="amount" 
                                        name="amount" 
                                        class="form-control" 
                                        required 
                                        min="<?php echo MIN_DEPOSIT; ?>"
                                        step="0.01"
                                        placeholder="Enter amount"
                                    >
                                    <small class="text-muted">Minimum deposit: <?php echo format_currency(MIN_DEPOSIT); ?></small>
                                </div>

                                <div class="form-group">
                                    <label for="deposit_type" class="form-label">Deposit Method *</label>
                                    <select id="deposit_type" name="deposit_type" class="form-control" required>
                                        <option value="">Select deposit method</option>
                                        <option value="BITCOIN">Bitcoin (BTC)</option>
                                        <option value="ETHEREUM">Ethereum (ETH)</option>
                                        <option value="USDT">Tether (USDT)</option>
                                        <option value="BANK_TRANSFER">Bank Transfer</option>
                                        <option value="PAYPAL">PayPal</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="proof_image" class="form-label">Payment Proof</label>
                                    <div class="file-upload">
                                        <input type="file" id="proof_image" name="proof_image" accept="image/*">
                                        <label for="proof_image" class="file-upload-label">
                                            <i class="fas fa-upload"></i>
                                            Upload payment screenshot
                                        </label>
                                    </div>
                                    <small class="text-muted">Upload screenshot of payment confirmation</small>
                                </div>

                                <div class="form-group">
                                    <button type="submit" name="submit_deposit" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Submit Deposit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Withdrawal Form with New Design -->
                <div class="col-6">
                    <div class="card withdrawal-card">
                        <div class="card-header">
                            <h3 class="card-title">Request For Withdrawal</h3>
                        </div>
                        <div class="withdrawal-form-container">
                            <?php if (!$current_user['withdrawal_code'] || !$current_user['confirmation_code']): ?>
                                <div class="withdrawal-info-alert">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Withdrawal codes not set!</strong><br>
                                    Please contact support to set up your withdrawal and confirmation codes before making withdrawal requests.
                                </div>
                            <?php else: ?>
                                
                                <!-- Available Balance Display -->
                                <div class="available-balance">
                                    Available Withdrawal: <?php echo format_currency($current_user['balance']); ?>
                                </div>
                                
                                <form method="POST" action="" class="withdrawal-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <!-- Amount Input -->
                                    <div class="form-group">
                                        <label class="form-label">AMOUNT</label>
                                        <div class="amount-input-container">
                                            <span class="currency-symbol">$</span>
                                            <input 
                                                type="number" 
                                                name="withdrawal_amount" 
                                                class="amount-input" 
                                                required 
                                                min="<?php echo MIN_WITHDRAWAL; ?>"
                                                max="<?php echo $current_user['balance']; ?>"
                                                step="0.01"
                                                placeholder="0.00"
                                            >
                                        </div>
                                    </div>

                                    <!-- Withdrawal Method -->
                                    <div class="form-group">
                                        <label class="form-label">WITHDRAWAL METHOD</label>
                                        <select name="withdrawal_method" class="withdrawal-select" required>
                                            <option value="">SELECT WITHDRAWAL METHOD</option>
                                            <option value="BITCOIN">Bitcoin (BTC)</option>
                                            <option value="ETHEREUM">Ethereum (ETH)</option>
                                            <option value="USDT">Tether (USDT)</option>
                                            <option value="BANK_TRANSFER">Bank Transfer</option>
                                            <option value="PAYPAL">PayPal</option>
                                        </select>
                                    </div>

                                    <!-- Wallet Address -->
                                    <div class="form-group">
                                        <label class="form-label">Wallet Address/Account Details</label>
                                        <textarea 
                                            name="wallet_address" 
                                            class="withdrawal-textarea" 
                                            required 
                                            rows="3"
                                            placeholder="Enter wallet address or bank account details"
                                        ></textarea>
                                    </div>

                                    <!-- Withdrawal Token -->
                                    <div class="form-group">
                                        <label class="form-label">Withdrawal_Token</label>
                                        <input 
                                            type="text" 
                                            name="withdrawal_code" 
                                            class="withdrawal-token-input" 
                                            required 
                                            maxlength="10"
                                            placeholder="Enter withdrawal code"
                                        >
                                    </div>

                                    <!-- Confirmation Code -->
                                    <div class="form-group">
                                        <label class="form-label">Confirmation Code</label>
                                        <input 
                                            type="text" 
                                            name="confirmation_code" 
                                            class="withdrawal-token-input" 
                                            required 
                                            maxlength="10"
                                            placeholder="Enter confirmation code"
                                        >
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="form-group">
                                        <button type="submit" name="submit_withdrawal" class="withdrawal-submit-btn">
                                            <i class="fas fa-plus"></i> Request Withdrawal
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="row mt-4">
                <!-- Recent Deposits -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Deposits</h3>
                        </div>
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_deposits): ?>
                                        <?php foreach ($recent_deposits as $deposit): ?>
                                            <tr>
                                                <td><?php echo format_currency($deposit['amount']); ?></td>
                                                <td><?php echo htmlspecialchars($deposit['deposit_type']); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo strtolower($deposit['status']); ?>">
                                                        <?php echo $deposit['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($deposit['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No deposits found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Withdrawals -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Withdrawals</h3>
                        </div>
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_withdrawals): ?>
                                        <?php foreach ($recent_withdrawals as $withdrawal): ?>
                                            <tr>
                                                <td><?php echo format_currency($withdrawal['amount']); ?></td>
                                                <td><?php echo htmlspecialchars($withdrawal['withdrawal_method']); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo strtolower($withdrawal['status']); ?>">
                                                        <?php echo $withdrawal['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($withdrawal['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No withdrawals found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function resetWithdrawal() {
            // Send request to clear session data
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'reset_withdrawal=1&csrf_token=<?php echo $csrf_token; ?>'
            }).then(() => {
                location.reload();
            });
        }
        
        // Auto focus on code inputs for better UX
        document.addEventListener('DOMContentLoaded', function() {
            const codeInputs = document.querySelectorAll('.withdrawal-token-input');
            codeInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });
        });
    </script>
</body>
</html>