<?php
// User Login Page
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password';
    } else {
        $result = Auth::login($username, $password);
        
        if ($result['success']) {
            redirect('dashboard.php');
        } else {
            $error_message = $result['message'];
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo" style="display: flex; justify-content: center; align-items: center;">
    <div style="display: flex; align-items: center; gap: 12px; font-family: 'Segoe UI', sans-serif;">
        <i class="fas fa-chart-line" style="color: #e74c3c; font-size: 2rem;"></i>
        <img src="logo-white.png" alt="Logo" style="height: 40px; width: auto;">
    </div>
</div>
                <p>Sign in to your trading account</p>
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

            <form method="POST" action="" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        required 
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        placeholder="Enter your username or email"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required 
                        placeholder="Enter your password"
                    >
                </div>

                <div class="form-group">
                    <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">
                        Sign In
                    </button>
                </div>

                <div class="form-group text-center">
                    <p class="text-muted">
                        Don't have an account? 
                        <a href="register.php" class="text-primary">Create Account</a>
                    </p>
                </div>
            </form>

            
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
