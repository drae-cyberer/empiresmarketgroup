<?php
// Common Functions File
require_once 'config.php';
require_once 'db.php';




// Ticket System Functions
function generate_ticket_id() {
    return 'TKT-' . strtoupper(substr(uniqid(), -6));
}

function create_support_ticket($user_id, $subject, $message, $priority = 'MEDIUM') {
    global $db;
    try {
        $ticket_id = generate_ticket_id();
        
        // Create ticket
        $ticket_db_id = $db->insert(
            "INSERT INTO support_tickets (user_id, ticket_id, subject, priority) VALUES (?, ?, ?, ?)",
            [$user_id, $ticket_id, $subject, $priority]
        );
        
        // Add initial message
        $db->insert(
            "INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?, 'USER', ?, ?)",
            [$ticket_db_id, $user_id, $message]
        );
        
        return $ticket_id;
    } catch (Exception $e) {
        return false;
    }
}

function get_user_tickets($user_id) {
    global $db;
    try {
        return $db->select("
            SELECT st.*, 
            (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = st.id) as message_count,
            (SELECT created_at FROM ticket_messages WHERE ticket_id = st.id ORDER BY created_at DESC LIMIT 1) as last_message_date
            FROM support_tickets st
            WHERE st.user_id = ? 
            ORDER BY st.updated_at DESC
        ", [$user_id]);
    } catch (Exception $e) {
        return [];
    }
}

function get_ticket_by_id($ticket_id, $user_id = null) {
    global $db;
    try {
        $query = "SELECT st.*, u.username, u.email, u.full_name, u.phone 
                  FROM support_tickets st
                  LEFT JOIN users u ON st.user_id = u.id
                  WHERE st.ticket_id = ?";
        $params = [$ticket_id];
        
        if ($user_id) {
            $query .= " AND st.user_id = ?";
            $params[] = $user_id;
        }
        
        return $db->selectOne($query, $params);
    } catch (Exception $e) {
        return null;
    }
}

function get_ticket_messages($ticket_db_id) {
    global $db;
    try {
        return $db->select("
            SELECT tm.*, 
            CASE 
                WHEN tm.sender_type = 'USER' THEN u.username
                WHEN tm.sender_type = 'ADMIN' THEN au.username
            END as sender_name
            FROM ticket_messages tm
            LEFT JOIN users u ON tm.sender_id = u.id AND tm.sender_type = 'USER'
            LEFT JOIN admin_users au ON tm.sender_id = au.id AND tm.sender_type = 'ADMIN'
            WHERE tm.ticket_id = ? 
            ORDER BY tm.created_at ASC
        ", [$ticket_db_id]);
    } catch (Exception $e) {
        return [];
    }
}

function add_ticket_message($ticket_db_id, $sender_type, $sender_id, $message) {
    global $db;
    try {
        $db->insert(
            "INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?, ?, ?, ?)",
            [$ticket_db_id, $sender_type, $sender_id, $message]
        );
        
        // Update ticket updated_at
        $db->update("UPDATE support_tickets SET updated_at = NOW() WHERE id = ?", [$ticket_db_id]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function update_ticket_status($ticket_db_id, $status) {
    global $db;
    try {
        return $db->update("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $ticket_db_id]);
    } catch (Exception $e) {
        return false;
    }
}

function update_ticket_priority($ticket_db_id, $priority) {
    global $db;
    try {
        return $db->update("UPDATE support_tickets SET priority = ?, updated_at = NOW() WHERE id = ?", [$priority, $ticket_db_id]);
    } catch (Exception $e) {
        return false;
    }
}

function get_all_tickets($search = '', $status_filter = '', $priority_filter = '', $limit = 20, $offset = 0) {
    global $db;
    try {
        $where_conditions = [];
        $params = [];

        if ($search) {
            $where_conditions[] = "(st.ticket_id LIKE ? OR st.subject LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }

        if ($status_filter) {
            $where_conditions[] = "st.status = ?";
            $params[] = $status_filter;
        }

        if ($priority_filter) {
            $where_conditions[] = "st.priority = ?";
            $params[] = $priority_filter;
        }

        $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        return $db->select("
            SELECT st.*, u.username, u.email, u.full_name,
            (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = st.id) as message_count,
            (SELECT created_at FROM ticket_messages WHERE ticket_id = st.id ORDER BY created_at DESC LIMIT 1) as last_message_date
            FROM support_tickets st
            LEFT JOIN users u ON st.user_id = u.id
            $where_clause
            ORDER BY st.updated_at DESC
            LIMIT $limit OFFSET $offset
        ", $params);
    } catch (Exception $e) {
        return [];
    }
}

function count_all_tickets($search = '', $status_filter = '', $priority_filter = '') {
    global $db;
    try {
        $where_conditions = [];
        $params = [];

        if ($search) {
            $where_conditions[] = "(st.ticket_id LIKE ? OR st.subject LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }

        if ($status_filter) {
            $where_conditions[] = "st.status = ?";
            $params[] = $status_filter;
        }

        if ($priority_filter) {
            $where_conditions[] = "st.priority = ?";
            $params[] = $priority_filter;
        }

        $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        $result = $db->selectOne("
            SELECT COUNT(*) as total
            FROM support_tickets st
            LEFT JOIN users u ON st.user_id = u.id
            $where_clause
        ", $params);
        
        return $result['total'];
    } catch (Exception $e) {
        return 0;
    }
}

function get_ticket_stats() {
    global $db;
    try {
        return $db->selectOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'OPEN' THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN priority = 'URGENT' THEN 1 ELSE 0 END) as urgent_tickets
            FROM support_tickets
        ");
    } catch (Exception $e) {
        return [
            'total' => 0, 'open_tickets' => 0, 'in_progress' => 0, 
            'resolved' => 0, 'closed' => 0, 'urgent_tickets' => 0
        ];
    }
}



// Security Functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generate_transaction_id($prefix = 'TXN') {
    return $prefix . '_' . date('YmdHis') . '_' . rand(1000, 9999);
}

// Password Functions
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// User Functions
function get_user_by_id($user_id) {
    global $db;
    return $db->selectOne("SELECT * FROM users WHERE id = ?", [$user_id]);
}

function get_user_by_username($username) {
    global $db;
    return $db->selectOne("SELECT * FROM users WHERE username = ?", [$username]);
}

function get_user_by_email($email) {
    global $db;
    return $db->selectOne("SELECT * FROM users WHERE email = ?", [$email]);
}

function create_user($data) {
    global $db;
    $query = "INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)";
    return $db->insert($query, [
        $data['username'],
        $data['email'],
        $data['password'],
        $data['full_name'],
        $data['phone'] ?? null
    ]);
}

function update_user_balance($user_id, $amount, $type = 'add') {
    global $db;
    $user = get_user_by_id($user_id);
    if (!$user) return false;
    
    $new_balance = $type === 'add' ? 
        $user['balance'] + $amount : 
        $user['balance'] - $amount;
    
    if ($new_balance < 0) return false;
    
    return $db->update("UPDATE users SET balance = ? WHERE id = ?", [$new_balance, $user_id]);
}

// Trading Functions
function get_all_traders() {
    global $db;
    return $db->select("SELECT * FROM traders WHERE status = 'ACTIVE' ORDER BY level, name");
}


function get_traders_by_level($level) {
    global $db;
    return $db->select("SELECT * FROM traders WHERE level = ? AND status = 'ACTIVE' ORDER BY name", [$level]);
}


function get_trader_by_id($trader_id) {
    global $db;
    return $db->selectOne("SELECT * FROM traders WHERE id = ?", [$trader_id]);
}


function get_investment_plans() {
    global $db;
    return $db->select("SELECT * FROM plans WHERE status = 'ACTIVE' ORDER BY level");
}

function create_copy_trade($user_id, $trader_id, $amount) {
    global $db;
    
    // Check if user has sufficient balance
    $user = get_user_by_id($user_id);
    if (!$user || $user['balance'] < $amount) {
        return false;
    }
    
    $db->beginTransaction();
    try {
        // Deduct amount from user balance
        update_user_balance($user_id, $amount, 'subtract');
        
        // Create copy trade record
        $copy_trade_id = $db->insert(
            "INSERT INTO copy_trades (user_id, trader_id, amount) VALUES (?, ?, ?)",
            [$user_id, $trader_id, $amount]
        );
        
        // Update trader's active connections
        $db->update(
            "UPDATE traders SET active_connections = active_connections + 1 WHERE id = ?",
            [$trader_id]
        );
        
        // Create transaction record
        $transaction_id = generate_transaction_id('COPY');
        $db->insert(
            "INSERT INTO transactions (user_id, transaction_id, type, amount, description, status) VALUES (?, ?, 'TRADE', ?, ?, 'COMPLETED')",
            [$user_id, $transaction_id, $amount, "Copy trade with trader ID: $trader_id"]
        );
        
        $db->commit();
        return $copy_trade_id;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

// Transaction Functions
function create_deposit($user_id, $amount, $type, $proof_image = null) {
    global $db;
    $transaction_id = generate_transaction_id('DEP');
    return $db->insert(
        "INSERT INTO deposits (user_id, transaction_id, amount, deposit_type, proof_image) VALUES (?, ?, ?, ?, ?)",
        [$user_id, $transaction_id, $amount, $type, $proof_image]
    );
}

function create_withdrawal($user_id, $amount, $method, $wallet_address = null) {
    global $db;
    
    $user = get_user_by_id($user_id);
    if (!$user || $user['balance'] < $amount) {
        return false;
    }
    
    $transaction_id = generate_transaction_id('WTH');
    $withdrawal_id = $db->insert(
        "INSERT INTO withdrawals (user_id, transaction_id, amount, withdrawal_method, wallet_address) VALUES (?, ?, ?, ?, ?)",
        [$user_id, $transaction_id, $amount, $method, $wallet_address]
    );
    
    if ($withdrawal_id) {
        // Deduct amount from user balance (pending withdrawal)
        update_user_balance($user_id, $amount, 'subtract');
    }
    
    return $withdrawal_id;
}

function get_user_deposits($user_id, $limit = 50) {
    global $db;
    return $db->select(
        "SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
        [$user_id, $limit]
    );
}

function get_user_withdrawals($user_id, $limit = 50) {
    global $db;
    return $db->select(
        "SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
        [$user_id, $limit]
    );
}

function get_user_trades($user_id, $limit = 50) {
    global $db;
    return $db->select(
        "SELECT t.*, tr.name as trader_name FROM trades t 
         LEFT JOIN traders tr ON t.trader_id = tr.id 
         WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT ?",
        [$user_id, $limit]
    );
}

function get_user_transactions($user_id, $limit = 50) {
    global $db;
    return $db->select(
        "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
        [$user_id, $limit]
    );
}

// Market Data Functions
function get_market_data() {
    global $db;
    return $db->select("SELECT * FROM market_data ORDER BY symbol");
}

function update_market_price($symbol, $price, $change_percent = 0) {
    global $db;
    return $db->update(
        "UPDATE market_data SET price = ?, change_percent = ?, updated_at = NOW() WHERE symbol = ?",
        [$price, $change_percent, $symbol]
    );
}

// File Upload Functions
function upload_file($file, $upload_dir = null) {
    if (!$upload_dir) {
        $upload_dir = UPLOAD_PATH;
    }
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

// Utility Functions
function format_currency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

function format_date($date) {
    return date('M j, Y g:i A', strtotime($date));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('index.php');
    }
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        redirect('index.php');
    }
}

// Live Trading Functions
function get_trading_instruments() {
    global $db;
    return $db->select("SELECT * FROM trading_instruments WHERE is_active = 1 ORDER BY category, name");
}

function get_instrument_by_symbol($symbol) {
    global $db;
    return $db->selectOne("SELECT * FROM trading_instruments WHERE symbol = ?", [$symbol]);
}

function place_live_trade($user_id, $instrument_symbol, $trade_type, $volume) {
    global $db;

    $instrument = get_instrument_by_symbol($instrument_symbol);
    if (!$instrument) {
        return ['success' => false, 'message' => 'Invalid trading instrument.'];
    }

    // This is a simplified calculation. A real system would get the live price.
    $open_price = get_market_data_by_symbol($instrument_symbol)['price'] ?? 0;
    if ($open_price <= 0) {
        return ['success' => false, 'message' => 'Could not retrieve a valid market price to open the trade.'];
    }

    $db->beginTransaction();
    try {
        // Insert the new trade
        $trade_id = $db->insert(
            "INSERT INTO live_trades (user_id, instrument, trade_type, volume, open_price, status) VALUES (?, ?, ?, ?, ?, 'OPEN')",
            [$user_id, $instrument_symbol, $trade_type, $volume, $open_price]
        );

        // Create a corresponding transaction record
        $transaction_id = generate_transaction_id('TRADE');
        $description = "Opened $trade_type trade for $volume lots of $instrument_symbol at $open_price";
        $db->insert(
            "INSERT INTO transactions (user_id, transaction_id, type, amount, description, status) VALUES (?, ?, 'TRADE', ?, ?, 'COMPLETED')",
            [$user_id, $transaction_id, 0, $description] // Amount is 0 as it's a trade record, not a balance change
        );

        $db->commit();
        return ['success' => true, 'trade_id' => $trade_id];
    } catch (Exception $e) {
        $db->rollback();
        // In a real app, you would log the error: error_log($e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while placing the trade. Please try again.'];
    }
}

function get_market_data_by_symbol($symbol) {
    global $db;
    return $db->selectOne("SELECT * FROM market_data WHERE symbol = ?", [$symbol]);
}

// Enhanced trade history function
function get_detailed_trade_history($user_id, $filters = []) {
    global $db;

    $where_conditions = ['lt.user_id = ?'];
    $params = [$user_id];

    // Add filter conditions if any
    if (!empty($filters['instrument'])) {
        $where_conditions[] = 'lt.instrument = ?';
        $params[] = $filters['instrument'];
    }

    if (!empty($filters['date_from'])) {
        $where_conditions[] = 'lt.opened_at >= ?';
        $params[] = $filters['date_from'];
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

    return $db->select("
        SELECT
            lt.*,
            ti.name as instrument_name,
            ti.category,
            CASE
                WHEN lt.profit_loss > 0 THEN 'PROFIT'
                WHEN lt.profit_loss < 0 THEN 'LOSS'
                ELSE 'BREAKEVEN'
            END as trade_result,
            TIMESTAMPDIFF(MINUTE, lt.opened_at, lt.closed_at) as duration_minutes
        FROM live_trades lt
        LEFT JOIN trading_instruments ti ON lt.instrument = ti.symbol
        $where_clause
        ORDER BY lt.opened_at DESC
    ", $params);
}
?>
