<?php
// Admin Tickets Management
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

// Handle quick status updates
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    $ticket_id = $_POST['ticket_id'] ?? '';
    
    if ($action == 'update_status' && $ticket_id) {
        $new_status = $_POST['status'] ?? '';
        if (in_array($new_status, ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])) {
            try {
                $ticket = get_ticket_by_id($ticket_id);
                if ($ticket && update_ticket_status($ticket['id'], $new_status)) {
                    $message = "Ticket status updated successfully.";
                    $message_type = 'success';
                } else {
                    $message = "Error updating ticket status.";
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                $message = "Error updating ticket status.";
                $message_type = 'error';
            }
        }
    }
}

// Get filter parameters
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';

// Get tickets and stats
$tickets = get_all_tickets($search, $status_filter, $priority_filter, $per_page, $offset);
$total_tickets = count_all_tickets($search, $status_filter, $priority_filter);
$total_pages = ceil($total_tickets / $per_page);
$stats = get_ticket_stats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - <?php echo SITE_NAME; ?></title>
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
                <li><a href="tickets.php" class="active"><i class="fas fa-headset"></i> Support Tickets</a></li>
                <li><a href="transactions.php"><i class="fas fa-history"></i> Transactions</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-content">
            <div class="admin-page-header">
                <h1>Support Tickets</h1>
                <p>Manage customer support tickets and communications</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #3498db;">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <p>Total Tickets</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e74c3c;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['open_tickets']); ?></h3>
                        <p>Open Tickets</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f39c12;">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['in_progress']); ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #27ae60;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['resolved']); ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #9b59b6;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['urgent_tickets']); ?></h3>
                        <p>Urgent</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="admin-filters">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search tickets..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="OPEN" <?php echo $status_filter == 'OPEN' ? 'selected' : ''; ?>>Open</option>
                            <option value="IN_PROGRESS" <?php echo $status_filter == 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="RESOLVED" <?php echo $status_filter == 'RESOLVED' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="CLOSED" <?php echo $status_filter == 'CLOSED' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="priority">
                            <option value="">All Priority</option>
                            <option value="LOW" <?php echo $priority_filter == 'LOW' ? 'selected' : ''; ?>>Low</option>
                            <option value="MEDIUM" <?php echo $priority_filter == 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
                            <option value="HIGH" <?php echo $priority_filter == 'HIGH' ? 'selected' : ''; ?>>High</option>
                            <option value="URGENT" <?php echo $priority_filter == 'URGENT' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="tickets.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>

            <!-- Tickets Table -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Messages</th>
                            <th>Created</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tickets)): ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <a href="ticket-details.php?id=<?php echo $ticket['ticket_id']; ?>" style="color: #e74c3c; text-decoration: none; font-weight: 600;">
                                            <?php echo htmlspecialchars($ticket['ticket_id']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($ticket['username']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($ticket['email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($ticket['subject']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($ticket['priority']); ?>">
                                            <?php echo $ticket['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                            <select name="status" onchange="this.form.submit()" style="border: 1px solid #ddd; padding: 4px; border-radius: 4px; font-size: 12px;">
                                                <option value="OPEN" <?php echo $ticket['status'] == 'OPEN' ? 'selected' : ''; ?>>Open</option>
                                                <option value="IN_PROGRESS" <?php echo $ticket['status'] == 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="RESOLVED" <?php echo $ticket['status'] == 'RESOLVED' ? 'selected' : ''; ?>>Resolved</option>
                                                <option value="CLOSED" <?php echo $ticket['status'] == 'CLOSED' ? 'selected' : ''; ?>>Closed</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?php echo $ticket['message_count']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($ticket['last_message_date'] ?? $ticket['updated_at'])); ?></td>
                                    <td class="actions">
                                        <div class="action-buttons">
                                            <a href="ticket-details.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No tickets found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="admin-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&priority=<?php echo urlencode($priority_filter); ?>" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                        (<?php echo number_format($total_tickets); ?> total tickets)
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&priority=<?php echo urlencode($priority_filter); ?>" class="btn btn-secondary">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>