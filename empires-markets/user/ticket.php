<?php
// User Ticket View
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

// Get ticket ID from URL
$ticket_id = $_GET['id'] ?? '';
if (!$ticket_id) {
    header('Location: support.php');
    exit();
}

// Get ticket details
$ticket = get_ticket_by_id($ticket_id, $_SESSION['user_id']);
if (!$ticket) {
    header('Location: support.php');
    exit();
}

// Handle message submission
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_message') {
    $reply_message = trim($_POST['message'] ?? '');
    
    if ($reply_message) {
        if (add_ticket_message($ticket['id'], 'USER', $_SESSION['user_id'], $reply_message)) {
            $message = 'Message sent successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to send message. Please try again.';
            $message_type = 'error';
        }
    } else {
        $message = 'Please enter a message.';
        $message_type = 'error';
    }
}

// Get ticket messages
$messages = get_ticket_messages($ticket['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket <?php echo htmlspecialchars($ticket['ticket_id']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .ticket-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .ticket-header {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        .ticket-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 600;
        }
        .messages-container {
            background: #fff;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }
        .messages-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .messages-list {
            max-height: 600px;
            overflow-y: auto;
            padding: 20px;
        }
        .message {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
        }
        .message:last-child {
            margin-bottom: 0;
        }
        .message.user {
            flex-direction: row-reverse;
        }
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e74c3c;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }
        .message.admin .message-avatar {
            background: #3498db;
        }
        .message-content {
            flex: 1;
            max-width: 70%;
        }
        .message-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .message.user .message-header {
            justify-content: flex-end;
        }
        .message-sender {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        .message-time {
            font-size: 12px;
            color: #666;
        }
        .message-text {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 15px;
            line-height: 1.5;
            color: #2c3e50;
        }
        .message.user .message-text {
            background: #e74c3c;
            color: white;
        }
        .message.admin .message-text {
            background: #e3f2fd;
            color: #1976d2;
        }
        .reply-container {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }
        .btn {
            background: #e74c3c;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover {
            background: #c0392b;
        }
        .btn.secondary {
            background: #6c757d;
        }
        .btn.secondary:hover {
            background: #5a6268;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-badge.open {
            background: #e3f2fd;
            color: #1976d2;
        }
        .status-badge.in_progress {
            background: #fff3e0;
            color: #f57c00;
        }
        .status-badge.resolved {
            background: #e8f5e8;
            color: #388e3c;
        }
        .status-badge.closed {
            background: #fafafa;
            color: #757575;
        }
        .priority-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-badge.low {
            background: #e8f5e8;
            color: #388e3c;
        }
        .priority-badge.medium {
            background: #fff3e0;
            color: #f57c00;
        }
        .priority-badge.high {
            background: #ffebee;
            color: #d32f2f;
        }
        .priority-badge.urgent {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            margin-bottom: 20px;
        }
        .no-messages {
            text-align: center;
            padding: 40px;
            color: #666;
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
            <li><a href="deposit.php"><i class="fas fa-plus-circle"></i> Deposit/Withdrawal</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-users"></i> Request Connections</a></li>
            <li><a href="copy-trading.php"><i class="fas fa-link"></i> View Connections</a></li>
            <li><a href="transactions.php"><i class="fas fa-history"></i> Transaction History</a></li>
            <li><a href="support.php" class="active"><i class="fas fa-headset"></i> Support</a></li>
            <li><a href="profile.php"><i class="fas fa-user-friends"></i> Ref. Users</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="ticket-container">
            <div class="back-link">
                <a href="support.php" class="btn secondary">
                    <i class="fas fa-arrow-left"></i> Back to Support
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Ticket Header -->
            <div class="ticket-header">
                <h1><i class="fas fa-ticket-alt"></i> Ticket <?php echo htmlspecialchars($ticket['ticket_id']); ?></h1>
                <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                
                <div class="ticket-info">
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge <?php echo strtolower(str_replace('_', '', $ticket['status'])); ?>">
                                <?php echo str_replace('_', ' ', $ticket['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Priority</div>
                        <div class="info-value">
                            <span class="priority-badge <?php echo strtolower($ticket['priority']); ?>">
                                <?php echo $ticket['priority']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Created</div>
                        <div class="info-value"><?php echo date('M j, Y \a\t g:i A', strtotime($ticket['created_at'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Last Updated</div>
                        <div class="info-value"><?php echo date('M j, Y \a\t g:i A', strtotime($ticket['updated_at'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="messages-container">
                <div class="messages-header">
                    <h3><i class="fas fa-comments"></i> Conversation</h3>
                </div>
                
                <div class="messages-list">
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?php echo strtolower($msg['sender_type']); ?>">
                                <div class="message-avatar">
                                    <?php if ($msg['sender_type'] == 'USER'): ?>
                                        <i class="fas fa-user"></i>
                                    <?php else: ?>
                                        <i class="fas fa-headset"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="message-content">
                                    <div class="message-header">
                                        <span class="message-sender">
                                            <?php echo htmlspecialchars($msg['sender_name'] ?? ($msg['sender_type'] == 'USER' ? 'You' : 'Support')); ?>
                                        </span>
                                        <span class="message-time">
                                            <?php echo date('M j, Y \a\t g:i A', strtotime($msg['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="message-text">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-messages">
                            <i class="fas fa-comments" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <h3>No messages yet</h3>
                            <p>Start the conversation by sending a message below.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reply Form -->
            <?php if ($ticket['status'] != 'CLOSED'): ?>
                <div class="reply-container">
                    <h3><i class="fas fa-reply"></i> Send Reply</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_message">
                        
                        <div class="form-group">
                            <label for="message">Your Message</label>
                            <textarea id="message" name="message" placeholder="Type your message here..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="reply-container" style="text-align: center; color: #666;">
                    <i class="fas fa-lock" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <h3>Ticket Closed</h3>
                    <p>This ticket has been closed and no longer accepts new messages.</p>
                    <a href="support.php" class="btn">
                        <i class="fas fa-plus"></i> Create New Ticket
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

    <script src="../assets/js/main.js"></script>
    <script>
        // Auto scroll to bottom of messages
        const messagesList = document.querySelector('.messages-list');
        if (messagesList) {
            messagesList.scrollTop = messagesList.scrollHeight;
        }
    </script>
</body>
</html>