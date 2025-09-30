<?php
// Admin Ticket Details
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
$admin_id = $_SESSION['admin_id'];

// Get ticket ID from URL
$ticket_id = $_GET['id'] ?? '';
if (!$ticket_id) {
    header('Location: tickets.php');
    exit();
}

// Get ticket details
$ticket = get_ticket_by_id($ticket_id);
if (!$ticket) {
    header('Location: tickets.php');
    exit();
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add_message':
            $reply_message = trim($_POST['message'] ?? '');
            if ($reply_message) {
                if (add_ticket_message($ticket['id'], 'ADMIN', $admin_id, $reply_message)) {
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
            break;
            
        case 'update_status':
            $new_status = $_POST['status'] ?? '';
            if (in_array($new_status, ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])) {
                if (update_ticket_status($ticket['id'], $new_status)) {
                    $ticket['status'] = $new_status; // Update local variable
                    $message = 'Ticket status updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to update status.';
                    $message_type = 'error';
                }
            }
            break;
            
        case 'update_priority':
            $new_priority = $_POST['priority'] ?? '';
            if (in_array($new_priority, ['LOW', 'MEDIUM', 'HIGH', 'URGENT'])) {
                if (update_ticket_priority($ticket['id'], $new_priority)) {
                    $ticket['priority'] = $new_priority; // Update local variable
                    $message = 'Ticket priority updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to update priority.';
                    $message_type = 'error';
                }
            }
            break;
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
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .ticket-details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .ticket-header-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        .ticket-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .info-section h4 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .info-item:last-child {
            margin-bottom: 0;
        }
        .info-label {
            font-weight: 600;
            color: #555;
        }
        .info-value {
            color: #2c3e50;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .message.admin {
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
        .message.admin .message-header {
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
        .message.admin .message-text {
            background: #3498db;
            color: white;
        }
        .reply-container {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }
        .actions-container {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .action-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
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
    </style>
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
                <li><a href="tickets.php" class="active"><i class="fas fa-headset"></i> Support Tickets</a></li>
                <li><a href="transactions.php"><i class="fas fa-history"></i> Transactions</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="ticket-details-container">
            <div class="back-link">
                <a href="tickets.php" class="btn secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tickets
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Ticket Header -->
            <div class="ticket-header-card">
                <h1><i class="fas fa-ticket-alt"></i> Ticket <?php echo htmlspecialchars($ticket['ticket_id']); ?></h1>
                <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                
                <div class="ticket-info-grid">
                    <div class="info-section">
                        <h4>Ticket Information</h4>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span class="info-value">
                                <span class="status-badge <?php echo strtolower(str_replace('_', '', $ticket['status'])); ?>">
                                    <?php echo str_replace('_', ' ', $ticket['status']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Priority:</span>
                            <span class="info-value">
                                <span class="priority-badge <?php echo strtolower($ticket['priority']); ?>">
                                    <?php echo $ticket['priority']; ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Created:</span>
                            <span class="info-value"><?php echo date('M j, Y \a\t g:i A', strtotime($ticket['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Updated:</span>
                            <span class="info-value"><?php echo date('M j, Y \a\t g:i A', strtotime($ticket['updated_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h4>Customer Information</h4>
                        <div class="info-item">
                            <span class="info-label">Username:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Full Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['full_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['phone'] ?? 'Not provided'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="messages-container">
                <div class="messages-header">
                    <h3><i class="fas fa-comments"></i> Conversation (<?php echo count($messages); ?> messages)</h3>
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
                                            <?php echo htmlspecialchars($msg['sender_name'] ?? ($msg['sender_type'] == 'USER' ? 'Customer' : 'Support')); ?>
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
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-comments" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <h3>No messages yet</h3>
                            <p>This ticket doesn't have any messages yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reply Form -->
            <div class="reply-container">
                <h3><i class="fas fa-reply"></i> Send Reply</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_message">
                    
                    <div class="form-group">
                        <label for="message">Your Reply</label>
                        <textarea id="message" name="message" placeholder="Type your reply here..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="actions-container">
                <h3><i class="fas fa-tools"></i> Quick Actions</h3>
                
                <div class="actions-grid">
                    <div class="action-card">
                        <h4>Update Status</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <div class="form-group">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="OPEN" <?php echo $ticket['status'] == 'OPEN' ? 'selected' : ''; ?>>Open</option>
                                    <option value="IN_PROGRESS" <?php echo $ticket['status'] == 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="RESOLVED" <?php echo $ticket['status'] == 'RESOLVED' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="CLOSED" <?php echo $ticket['status'] == 'CLOSED' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    
                    <div class="action-card">
                        <h4>Update Priority</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_priority">
                            <div class="form-group">
                                <select name="priority" onchange="this.form.submit()">
                                    <option value="LOW" <?php echo $ticket['priority'] == 'LOW' ? 'selected' : ''; ?>>Low</option>
                                    <option value="MEDIUM" <?php echo $ticket['priority'] == 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="HIGH" <?php echo $ticket['priority'] == 'HIGH' ? 'selected' : ''; ?>>High</option>
                                    <option value="URGENT" <?php echo $ticket['priority'] == 'URGENT' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    
                    <div class="action-card">
                        <h4>User Account</h4>
                        <a href="user-details.php?id=<?php echo $ticket['user_id']; ?>" class="btn" style="margin-top: 10px;">
                            <i class="fas fa-user"></i> View User
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

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