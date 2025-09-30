<?php
// User Support System
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

// Handle form submission
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'create_ticket') {
        $subject = trim($_POST['subject'] ?? '');
        $ticket_message = trim($_POST['message'] ?? '');
        $priority = $_POST['priority'] ?? 'MEDIUM';
        
        if ($subject && $ticket_message) {
            $ticket_id = create_support_ticket($_SESSION['user_id'], $subject, $ticket_message, $priority);
            if ($ticket_id) {
                $message = "Ticket created successfully! Ticket ID: $ticket_id";
                $message_type = 'success';
            } else {
                $message = 'Failed to create ticket. Please try again.';
                $message_type = 'error';
            }
        } else {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        }
    }
}

// Get user tickets
$user_tickets = get_user_tickets($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .support-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .support-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .support-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        @media (max-width: 768px) {
            .support-grid {
                grid-template-columns: 1fr;
            }
        }
        .support-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        .support-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-group textarea {
            height: 120px;
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
        .tickets-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .tickets-table th,
        .tickets-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .tickets-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .status-badge {
            padding: 4px 12px;
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
            padding: 2px 8px;
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
        .no-tickets {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .ticket-link {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
        }
        .ticket-link:hover {
            text-decoration: underline;
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
                    <div class="logo">
                        <i class="fas fa-chart-line" style="color: #e74c3c; font-size: 1.5rem;"></i>
                        <span style="margin-left: 8px;"><?php echo SITE_NAME; ?></span>
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
        <div class="support-container">
            <div class="support-header">
                <h1><i class="fas fa-headset"></i> Support Center</h1>
                <p>Get help with your account and trading questions</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="support-grid">
                <!-- Create New Ticket -->
                <div class="support-card">
                    <h3><i class="fas fa-plus-circle"></i> Create New Ticket</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="create_ticket">
                        
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                <option value="LOW">Low</option>
                                <option value="MEDIUM" selected>Medium</option>
                                <option value="HIGH">High</option>
                                <option value="URGENT">Urgent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" placeholder="Describe your issue in detail..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn">
                            <i class="fas fa-paper-plane"></i> Submit Ticket
                        </button>
                    </form>
                </div>

                <!-- Quick Help -->
                <div class="support-card">
                    <h3><i class="fas fa-question-circle"></i> Quick Help</h3>
                    <div style="space-y: 15px;">
                        <div style="margin-bottom: 15px;">
                            <h4><i class="fas fa-deposit"></i> Deposits & Withdrawals</h4>
                            <p>Having issues with deposits or withdrawals? Check our processing times and requirements.</p>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <h4><i class="fas fa-chart-line"></i> Trading Issues</h4>
                            <p>Questions about copy trading, connections, or trade execution? We're here to help.</p>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <h4><i class="fas fa-user-check"></i> Account Verification</h4>
                            <p>Need help with KYC verification or account settings? Contact our support team.</p>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <h4><i class="fas fa-clock"></i> Support Hours</h4>
                            <p>Our team is available 24/7 to assist you with any questions or concerns.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Tickets -->
            <div class="support-card">
                <h3><i class="fas fa-ticket-alt"></i> My Support Tickets</h3>
                
                <?php if (!empty($user_tickets)): ?>
                    <div style="overflow-x: auto;">
                        <table class="tickets-table">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Messages</th>
                                    <th>Created</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_tickets as $ticket): ?>
                                    <tr>
                                        <td>
                                            <a href="ticket.php?id=<?php echo $ticket['ticket_id']; ?>" class="ticket-link">
                                                <?php echo htmlspecialchars($ticket['ticket_id']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                        <td>
                                            <span class="priority-badge <?php echo strtolower($ticket['priority']); ?>">
                                                <?php echo $ticket['priority']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower(str_replace('_', '', $ticket['status'])); ?>">
                                                <?php echo str_replace('_', ' ', $ticket['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $ticket['message_count']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($ticket['last_message_date'] ?? $ticket['updated_at'])); ?></td>
                                        <td>
                                            <a href="ticket.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn" style="padding: 6px 12px; font-size: 12px;">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-tickets">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                        <h3>No support tickets yet</h3>
                        <p>Create your first support ticket using the form above.</p>
                    </div>
                <?php endif; ?>
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
</body>
</html>