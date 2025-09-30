<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copy Trading - Trading Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            color: #fff;
        }

        .container-fluid {
            width: 100%;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .menu-toggle {
            cursor: pointer;
            font-size: 1.2rem;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-icon {
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .nav-icon:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .kyc-badge {
            background: #e74c3c;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: -280px;
            top: 70px;
            width: 280px;
            height: calc(100vh - 70px);
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            transition: left 0.3s ease;
            z-index: 999;
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: #e74c3c;
        }

        .sidebar-overlay {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: calc(100vh - 70px);
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 998;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Main Content */
        .main-content {
            margin-top: 70px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        .dashboard-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-section {
            margin-bottom: 30px;
        }

        .welcome-text {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .text-light {
            opacity: 0.8;
            font-size: 1.1rem;
        }

        /* Card Styles */
        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.balance {
            background: linear-gradient(135deg, #4CAF50, #45a049);
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-info p {
            opacity: 0.8;
        }

        /* Alert Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.2);
            border-left-color: #e74c3c;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border-left-color: #4CAF50;
        }

        /* Level Section */
        .level-section {
            margin-bottom: 40px;
        }

        .level-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            padding: 15px 20px;
            font-weight: bold;
            font-size: 1.1rem;
            border-radius: 10px 10px 0 0;
        }

        .level-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-top: none;
            border-radius: 0 0 10px 10px;
        }

        .level-title {
            padding: 20px;
            font-weight: bold;
            font-size: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Table Styles */
        .traders-table {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            background: rgba(255, 255, 255, 0.05);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .trader-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .trader-category {
            background: rgba(52, 152, 219, 0.2);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .btn-connect {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-connect:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .unavailable-message {
            text-align: center;
            padding: 40px;
            font-size: 1.1rem;
            opacity: 0.7;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1rem;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-control:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
        }

        .text-muted {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-top: 5px;
            display: block;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 30px;
            margin-top: 50px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }

            .welcome-text {
                font-size: 2rem;
            }

            .sidebar {
                width: 250px;
            }

            .traders-table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 10px 8px;
            }

            .trader-avatar {
                width: 35px;
                height: 35px;
            }
        }

        /* Font Awesome replacement icons */
        .fas {
            font-style: normal;
            font-weight: 900;
            display: inline-block;
            width: 1.2em;
            text-align: center;
        }

        .fa-home::before { content: "🏠"; }
        .fa-user::before { content: "👤"; }
        .fa-chart-line::before { content: "📈"; }
        .fa-plus-circle::before { content: "💰"; }
        .fa-users::before { content: "👥"; }
        .fa-link::before { content: "🔗"; }
        .fa-history::before { content: "📜"; }
        .fa-user-friends::before { content: "👫"; }
        .fa-sign-out-alt::before { content: "🚪"; }
        .fa-bars::before { content: "☰"; }
        .fa-bell::before { content: "🔔"; }
        .fa-user-circle::before { content: "👤"; }
        .fa-wallet::before { content: "💼"; }

        /* Loading animation for smooth transitions */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <div class="header-content">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <span class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </span>
                    <div class="logo">
                        <i class="fas fa-chart-line" style="color: #e74c3c; font-size: 2rem;"></i>
                        <span>Empires Markets</span>
                    </div>
                </div>
                
                <div class="nav-icons">
                    <span class="nav-icon"><i class="fas fa-bell"></i></span>
                    <span class="kyc-badge">KYC</span>
                    <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
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
            <li><a href="plans.php" class="active"><i class="fas fa-chart-line"></i> Trade Plans</a></li>
            <li><a href="deposit.php"><i class="fas fa-plus-circle"></i> Deposit/Withdrawal</a></li>
            <li><a href="copy-trading.php" class="active"><i class="fas fa-users"></i> Request Connections</a></li>
           
            <li><a href="transactions.php"><i class="fas fa-history"></i> Transaction History</a></li>
         
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-content fade-in">
            <!-- Page Header -->
            <div class="welcome-section">
                <h1 class="welcome-text">Copy Trading</h1>
                <p class="text-light">Connect with professional traders and copy their strategies</p>
            </div>

            <!-- Current Balance -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Balance</h3>
                </div>
                <div style="padding: 20px;">
                    <div class="stat-card">
                        <div class="stat-icon balance"><i class="fas fa-wallet"></i></div>
                        <div class="stat-info">
                            <h3>$25,450.00</h3>
                            <p>Available for Investment</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <div id="alertContainer"></div>

            <!-- Trading Levels -->
            <div class="level-section">
                <div class="level-header">LEVEL 1: $100</div>
                <div class="level-card">
                    <div class="level-title">LEVEL 1: $100</div>
                    <div class="traders-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>AVATAR</th>
                                    <th>ID</th>
                                    <th>NAME</th>
                                    <th>CATEGORY</th>
                                    <th>PROCESSED</th>
                                    <th>ACTIVE CONNECTIONS</th>
                                    <th>RATING</th>
                                    <th>PERCENTAGE RATING</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><div class="trader-avatar" style="background: linear-gradient(135deg, #3498db, #2980b9); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">JD</div></td>
                                    <td>TRD001</td>
                                    <td class="trader-name">John Davis</td>
                                    <td><span class="trader-category">Forex</span></td>
                                    <td>1,250</td>
                                    <td class="active-connections">45</td>
                                    <td class="rating">4.8</td>
                                    <td class="percentage-rating">89%</td>
                                    <td>
                                        <button class="btn-connect" onclick="openCopyTradeModal('1', 'John Davis', '1')">CONNECT</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><div class="trader-avatar" style="background: linear-gradient(135deg, #e74c3c, #c0392b); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">SM</div></td>
                                    <td>TRD002</td>
                                    <td class="trader-name">Sarah Miller</td>
                                    <td><span class="trader-category">Crypto</span></td>
                                    <td>2,100</td>
                                    <td class="active-connections">67</td>
                                    <td class="rating">4.9</td>
                                    <td class="percentage-rating">92%</td>
                                    <td>
                                        <button class="btn-connect" onclick="openCopyTradeModal('2', 'Sarah Miller', '1')">CONNECT</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="level-section">
                <div class="level-header">LEVEL 2: $500</div>
                <div class="level-card">
                    <div class="level-title">LEVEL 2: $500</div>
                    <div class="traders-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>AVATAR</th>
                                    <th>ID</th>
                                    <th>NAME</th>
                                    <th>CATEGORY</th>
                                    <th>PROCESSED</th>
                                    <th>ACTIVE CONNECTIONS</th>
                                    <th>RATING</th>
                                    <th>PERCENTAGE RATING</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><div class="trader-avatar" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">MJ</div></td>
                                    <td>TRD003</td>
                                    <td class="trader-name">Michael Johnson</td>
                                    <td><span class="trader-category">Stocks</span></td>
                                    <td>5,650</td>
                                    <td class="active-connections">89</td>
                                    <td class="rating">4.7</td>
                                    <td class="percentage-rating">87%</td>
                                    <td>
                                        <button class="btn-connect" onclick="openCopyTradeModal('3', 'Michael Johnson', '2')">CONNECT</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="level-section">
                <div class="level-header">LEVEL 3: $1,000</div>
                <div class="level-card">
                    <div class="level-title">LEVEL 3: $1,000</div>
                    <div class="traders-table">
                        <div class="unavailable-message">No traders available for this level</div>
                    </div>
                </div>
            </div>

            <div class="level-section">
                <div class="level-header">LEVEL 4: $5,000</div>
                <div class="level-card">
                    <div class="level-title">LEVEL 4: $5,000</div>
                    <div class="traders-table">
                        <div class="unavailable-message">No traders available for this level</div>
                    </div>
                </div>
            </div>

            <div class="level-section">
                <div class="level-header">LEVEL 5: $10,000</div>
                <div class="level-card">
                    <div class="level-title">LEVEL 5: $10,000</div>
                    <div class="traders-table">
                        <div class="unavailable-message">LEVEL 5 IS UNAVAILABLE</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Copy Trade Modal -->
    <div id="copyTradeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Connect to Trader</h3>
                <button type="button" class="modal-close" onclick="closeCopyTradeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="copyTradeForm" onsubmit="handleCopyTrade(event)">
                    <input type="hidden" id="traderId" value="">
                    
                    <div class="form-group">
                        <label><strong>Trader:</strong> <span id="modalTraderName"></span></label>
                        <label><strong>Level:</strong> <span id="modalLevel"></span></label>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount" class="form-label">Investment Amount ($)</label>
                        <input type="number" id="amount" name="amount" class="form-control" min="10" step="0.01" required placeholder="Enter amount">
                        <small class="text-muted">Minimum investment: $10.00</small>
                        <small class="text-muted">Available balance: $25,450.00</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Connect & Invest
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeCopyTradeModal()" style="width: 100%; margin-top: 10px;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>All Rights Reserved © Empires Markets 2025</p>
        </div>
    </footer>

    <script>
        // Sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Modal functionality
        function openCopyTradeModal(traderId, traderName, level) {
            document.getElementById('traderId').value = traderId;
            document.getElementById('modalTraderName').textContent = traderName;
            document.getElementById('modalLevel').textContent = level;
            document.getElementById('copyTradeModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCopyTradeModal() {
            document.getElementById('copyTradeModal').classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('copyTradeForm').reset();
        }

        // Handle copy trade form submission
        function handleCopyTrade(event) {
            event.preventDefault();
            
            const amount = parseFloat(document.getElementById('amount').value);
            const traderId = document.getElementById('traderId').value;
            const traderName = document.getElementById('modalTraderName').textContent;
            const availableBalance = 25450; // This would come from PHP in real implementation
            
            // Validation
            if (amount < 10) {
                showAlert('Minimum investment amount is $10', 'danger');
                return;
            }
            
            if (amount > availableBalance) {
                showAlert('Insufficient balance', 'danger');
                return;
            }
            
            // Simulate successful connection
            closeCopyTradeModal();
            showAlert(`Successfully connected to ${traderName}! Investment amount: $${amount.toFixed(2)}`, 'success');
            
            // In real implementation, this would submit to the server
            console.log('Copy trade submitted:', { traderId, amount });
        }

        // Show alert messages
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            
            const alertElement = document.createElement('div');
            alertElement.className = `alert ${alertClass} fade-in`;
            alertElement.textContent = message;
            
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alertElement);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alertElement.style.opacity = '0';
                setTimeout(() => {
                    if (alertElement.parentNode) {
                        alertElement.parentNode.removeChild(alertElement);
                    }
                }, 300);
            }, 5000);
        }

        // Close modal when clicking outside
        document.getElementById('copyTradeModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeCopyTradeModal();
            }
        });

        // Close sidebar when clicking on links and allow navigation
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function(event) {
                // Close sidebar when any link is clicked
                toggleSidebar();
                
                // Remove active class from all links
                document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
                
                // Allow normal navigation to occur
                console.log(`Navigating to: ${this.getAttribute('href')}`);
            });
        });

        // Smooth loading effect
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in effect to tables
            document.querySelectorAll('.traders-table table').forEach(table => {
                table.classList.add('fade-in');
            });
            
            // Initialize tooltips or any other interactive elements
            console.log('Copy Trading page loaded successfully');
        });

        // Handle responsive behavior
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });

        // Prevent form submission on enter key in number inputs
        document.getElementById('amount').addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                document.querySelector('#copyTradeForm button[type="submit"]').click();
            }
        });

        // Format currency input
        document.getElementById('amount').addEventListener('input', function(event) {
            let value = event.target.value;
            if (value && !isNaN(value)) {
                // Optional: Add real-time formatting
                console.log('Amount entered:', value);
            }
        });
    </script>
</body>
</html>