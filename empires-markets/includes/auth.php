<?php
// Authentication Handler
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

class Auth {
    
    // User Authentication
    public static function login($username, $password) {
        $user = get_user_by_username($username);
        
        if (!$user) {
            $user = get_user_by_email($username);
        }
    
      
       //($user && verify_password($password, $user['password']))
        if ($user && ($password === $user['password'] || verify_password($password, $user['password']))) {
            if ($user['account_status'] !== 'ACTIVE') {
                return ['success' => false, 'message' => 'Account is suspended or inactive'];
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_full_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();
            
            // Update last login
            global $db;
            $db->update("UPDATE users SET updated_at = NOW() WHERE id = ?", [$user['id']]);
            
            return ['success' => true, 'message' => 'Login successful'];
        }
        
        return ['success' => false, 'message' => 'Invalid username/email or password'];
    }
    
    public static function register($data) {
        // Validate required fields
        $required_fields = ['username', 'email', 'password', 'full_name'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field $field is required"];
            }
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if username exists
        if (get_user_by_username($data['username'])) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email exists
        if (get_user_by_email($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Validate password strength
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        // Create user
        $user_id = create_user($data);
        
        if ($user_id) {
            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    public static function logout() {
        session_unset();
        session_destroy();
        session_start();
        return true;
    }
    
    // Admin Authentication
    public static function admin_login($username, $password) {
        global $db;
        
        $admin = $db->selectOne("SELECT * FROM admin_users WHERE username = ?", [$username]);
        
        if ($admin && verify_password($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_login_time'] = time();
            
            return ['success' => true, 'message' => 'Admin login successful'];
        }
        
        return ['success' => false, 'message' => 'Invalid admin credentials'];
    }
    
    public static function admin_logout() {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_role']);
        unset($_SESSION['admin_login_time']);
        return true;
    }
    
    // Session Management
    public static function check_session_timeout() {
        if (isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > SESSION_LIFETIME) {
                self::logout();
                return false;
            }
            $_SESSION['login_time'] = time(); // Refresh session
        }
        return true;
    }
    
    public static function check_admin_session_timeout() {
        if (isset($_SESSION['admin_login_time'])) {
            if (time() - $_SESSION['admin_login_time'] > SESSION_LIFETIME) {
                self::admin_logout();
                return false;
            }
            $_SESSION['admin_login_time'] = time(); // Refresh session
        }
        return true;
    }
    
    // Password Reset Functions
    public static function generate_reset_token($email) {
        global $db;
        
        $user = get_user_by_email($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token (you might want to create a password_resets table)
        $_SESSION['reset_token_' . $user['id']] = [
            'token' => $token,
            'expires' => $expires,
            'email' => $email
        ];
        
        return ['success' => true, 'token' => $token, 'user_id' => $user['id']];
    }
    
    public static function verify_reset_token($user_id, $token) {
        if (!isset($_SESSION['reset_token_' . $user_id])) {
            return false;
        }
        
        $stored_data = $_SESSION['reset_token_' . $user_id];
        
        if ($stored_data['token'] !== $token) {
            return false;
        }
        
        if (strtotime($stored_data['expires']) < time()) {
            unset($_SESSION['reset_token_' . $user_id]);
            return false;
        }
        
        return true;
    }
    
    public static function reset_password($user_id, $token, $new_password) {
        if (!self::verify_reset_token($user_id, $token)) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        if (strlen($new_password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        global $db;
        $hashed_password = $new_password;
        
        $updated = $db->update(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
            [$hashed_password, $user_id]
        );
        
        if ($updated) {
            unset($_SESSION['reset_token_' . $user_id]);
            return ['success' => true, 'message' => 'Password reset successful'];
        }
        
        return ['success' => false, 'message' => 'Password reset failed'];
    }
}
?>
