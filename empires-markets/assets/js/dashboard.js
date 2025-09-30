// Dashboard JavaScript - COMPLETE Fixed Version
// All functionality preserved but optimized to prevent hanging

document.addEventListener('DOMContentLoaded', function () {
    // Initialize dashboard components with error handling
    try {
        initializeDashboard();
        initializeCopyTrading();
        initializeSidebarToggle();

        // Initialize TradingView widgets with delay and error handling
        setTimeout(initializeTradingViewWidgets, 2000);

        // Initialize real-time updates with longer intervals to prevent hanging
        setTimeout(initializeRealTimeUpdates, 5000);

    } catch (error) {
        console.error('Dashboard initialization error:', error);
    }
});

// Dashboard Initialization
function initializeDashboard() {
    // Update dashboard stats
    updateDashboardStats();

    // Initialize sidebar navigation
    initializeSidebarNavigation();

    // Load trader data
    loadTraderData();

    // Initialize refresh functionality
    initializeRefresh();

    // Initialize charts
    initializeCharts();
}

// Sidebar Navigation
function initializeSidebarNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    const currentPage = window.location.pathname.split('/').pop();

    sidebarLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        }

        link.addEventListener('click', function (e) {
            // Remove active class from all links
            sidebarLinks.forEach(l => l.classList.remove('active'));
            // Add active class to clicked link
            this.classList.add('active');
        });
    });
}

// Safer sidebar toggle
function initializeSidebarToggle() {
    // Mobile sidebar toggle
    window.toggleSidebar = function () {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');

            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    };

    // Close sidebar when clicking overlay
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', function () {
            window.toggleSidebar();
        });
    }

    // Close sidebar on window resize if desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (sidebar && overlay) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });

    // Prevent clicks on sidebar from closing it
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
}

// Copy Trading Functionality - Complete but optimized
function initializeCopyTrading() {
    const connectButtons = document.querySelectorAll('.btn-connect');

    connectButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            const traderId = this.getAttribute('data-trader-id');
            const traderName = this.getAttribute('data-trader-name');
            const level = this.getAttribute('data-level');

            showCopyTradeModal(traderId, traderName, level);
        });
    });
}

function showCopyTradeModal(traderId, traderName, level) {
    const modal = document.getElementById('copyTradeModal') || createCopyTradeModal();

    // Update modal content
    const modalTitle = modal.querySelector('.modal-title');
    const traderNameSpan = modal.querySelector('.trader-name');
    const levelSpan = modal.querySelector('.level');
    const traderIdInput = modal.querySelector('input[name="trader_id"]');

    if (modalTitle) modalTitle.textContent = 'Connect to Trader';
    if (traderNameSpan) traderNameSpan.textContent = traderName;
    if (levelSpan) levelSpan.textContent = `Level ${level}`;
    if (traderIdInput) traderIdInput.value = traderId;

    // Show modal - simplified method to prevent hanging
    showModal(modal);
}

function createCopyTradeModal() {
    const modal = document.createElement('div');
    modal.id = 'copyTradeModal';
    modal.className = 'modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: none;
        z-index: 10000;
        align-items: center;
        justify-content: center;
    `;
    modal.innerHTML = `
        <div class="modal-content" style="
            background: #1a1f2e;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        ">
            <div class="modal-header" style="
                background: linear-gradient(135deg, #e74c3c, #c0392b);
                color: white;
                padding: 1rem;
                border-radius: 12px 12px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            ">
                <h3 class="modal-title" style="margin: 0;">Connect to Trader</h3>
                <button type="button" class="modal-close" style="
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.5rem;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">&times;</button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <form id="copyTradeForm" method="POST" action="copy-trading.php">
                    <input type="hidden" name="trader_id" value="">
                    <input type="hidden" name="csrf_token" value="${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''}">
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <p style="color: #fff; margin-bottom: 0.5rem;">
                            <strong>Trader:</strong> <span class="trader-name" style="color: #3498db;"></span>
                        </p>
                        <p style="color: #fff; margin-bottom: 1rem;">
                            <strong>Level:</strong> <span class="level" style="color: #e74c3c;"></span>
                        </p>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="amount" style="
                            display: block;
                            color: #fff;
                            margin-bottom: 0.5rem;
                            font-weight: 600;
                        ">Investment Amount ($)</label>
                        <input type="number" id="amount" name="amount" style="
                            width: 100%;
                            padding: 12px;
                            border: 1px solid rgba(255,255,255,0.2);
                            border-radius: 6px;
                            background: rgba(255,255,255,0.1);
                            color: #fff;
                            font-size: 1rem;
                        " min="10" step="0.01" required placeholder="Enter amount">
                        <small style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">Minimum investment: $10</small>
                    </div>
                    
                    <div class="form-group" style="
                        display: flex;
                        gap: 1rem;
                        justify-content: flex-end;
                    ">
                        <button type="button" class="btn btn-secondary modal-close" style="
                            background: rgba(255,255,255,0.1);
                            color: #fff;
                            border: none;
                            padding: 10px 20px;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                        ">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="
                            background: linear-gradient(135deg, #3498db, #2980b9);
                            color: white;
                            border: none;
                            padding: 10px 20px;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                        ">Connect & Invest</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Add event listeners
    const closeButtons = modal.querySelectorAll('.modal-close');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => hideModal(modal));
    });

    // Close on outside click
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            hideModal(modal);
        }
    });

    // Add form submission handler
    const form = modal.querySelector('#copyTradeForm');
    form.addEventListener('submit', handleCopyTradeSubmission);

    return modal;
}

function showModal(modal) {
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);
}

function hideModal(modal) {
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function handleCopyTradeSubmission(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');

    // Show loading state
    submitButton.disabled = true;
    submitButton.textContent = 'Processing...';

    // Submit form with timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        signal: controller.signal
    })
        .then(response => {
            clearTimeout(timeoutId);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                hideModal(document.getElementById('copyTradeModal'));

                // Refresh page data
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = 'Connect & Invest';
        });
}

// Simple alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 6px;
        z-index: 10001;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        max-width: 400px;
    `;
    alertDiv.textContent = message;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Real-time Updates - Optimized with longer intervals
function initializeRealTimeUpdates() {
    // Reduce frequency to prevent hanging
    const marketInterval = setInterval(updateMarketData, 300000); // 5 minutes
    const balanceInterval = setInterval(updateUserBalance, 600000); // 10 minutes
    const traderInterval = setInterval(updateTraderStats, 900000); // 15 minutes

    // Store intervals for cleanup
    window.dashboardIntervals = {
        market: marketInterval,
        balance: balanceInterval,
        trader: traderInterval
    };

    // Clear intervals when page is hidden
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            Object.values(window.dashboardIntervals).forEach(clearInterval);
        } else if (!document.hidden) {
            // Restart intervals when page becomes visible
            setTimeout(initializeRealTimeUpdates, 1000);
        }
    });
}

function updateMarketData() {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);

    fetch('../api/get-market-data.php', {
        signal: controller.signal,
        headers: { 'Content-Type': 'application/json' }
    })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success && data.market_data) {
                updateMarketPrices(data.market_data);
            }
        })
        .catch(error => {
            console.log('Market data update failed (will retry later):', error.message);
        });
}

function updateMarketPrices(marketData) {
    if (!Array.isArray(marketData)) return;

    marketData.forEach(item => {
        try {
            const priceElement = document.querySelector(`[data-symbol="${item.symbol}"] .chart-price`);
            const changeElement = document.querySelector(`[data-symbol="${item.symbol}"] .chart-change`);

            if (priceElement && item.price) {
                priceElement.textContent = formatCurrency(item.price);
            }

            if (changeElement && item.change_percent) {
                const changePercent = parseFloat(item.change_percent);
                changeElement.textContent = `${changePercent >= 0 ? '+' : ''}${changePercent.toFixed(2)}%`;
                changeElement.className = `chart-change ${changePercent >= 0 ? 'positive' : 'negative'}`;
            }
        } catch (error) {
            console.log('Error updating market price for', item.symbol, ':', error);
        }
    });
}

function updateUserBalance() {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000);

    fetch('dashboard.php?action=get_balance', {
        signal: controller.signal
    })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success && data.balance !== undefined) {
                const balanceElements = document.querySelectorAll('.user-balance');
                balanceElements.forEach(element => {
                    element.textContent = formatCurrency(data.balance);
                });
            }
        })
        .catch(error => {
            console.log('Balance update failed (will retry later):', error.message);
        });
}

function updateTraderStats() {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);

    fetch('../api/get-traders.php', {
        signal: controller.signal
    })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success && data.traders) {
                updateTraderDisplay(data.traders);
            }
        })
        .catch(error => {
            console.log('Trader stats update failed (will retry later):', error.message);
        });
}

function updateTraderDisplay(traders) {
    if (!Array.isArray(traders)) return;

    traders.forEach(trader => {
        try {
            const traderRow = document.querySelector(`[data-trader-id="${trader.id}"]`);
            if (traderRow) {
                const connectionsCell = traderRow.querySelector('.active-connections');
                const ratingCell = traderRow.querySelector('.rating');
                const percentageCell = traderRow.querySelector('.percentage-rating');

                if (connectionsCell) connectionsCell.textContent = trader.active_connections;
                if (ratingCell) ratingCell.textContent = trader.rating;
                if (percentageCell) percentageCell.textContent = trader.percentage_rating + '%';
            }
        } catch (error) {
            console.log('Error updating trader display:', error);
        }
    });
}

// Dashboard Stats - Safer animation
function updateDashboardStats() {
    const statCards = document.querySelectorAll('.stat-card h3');

    statCards.forEach(card => {
        try {
            const text = card.textContent;
            const value = parseFloat(text.replace(/[^0-9.-]+/g, ''));

            if (!isNaN(value) && value > 0) {
                // Safe counting animation
                animateValue(card, 0, value, 1000);
            }
        } catch (error) {
            console.log('Animation error for stat card:', error);
        }
    });
}

function animateValue(element, start, end, duration) {
    if (!element) return;

    const startTime = performance.now();
    const originalText = element.textContent;
    const isPrice = originalText.includes('$');

    function update(currentTime) {
        try {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const currentValue = start + (end - start) * easeOutQuart(progress);

            if (isPrice) {
                element.textContent = formatCurrency(currentValue);
            } else {
                element.textContent = Math.round(currentValue).toLocaleString();
            }

            if (progress < 1) {
                requestAnimationFrame(update);
            }
        } catch (error) {
            console.log('Animation update error:', error);
            element.textContent = originalText; // Restore original text
        }
    }

    requestAnimationFrame(update);
}

function easeOutQuart(t) {
    return 1 - Math.pow(1 - t, 4);
}

// Trader Data Loading
function loadTraderData() {
    const traderTables = document.querySelectorAll('.traders-table');

    traderTables.forEach(table => {
        const level = table.getAttribute('data-level');
        if (level) {
            loadTradersForLevel(level, table);
        }
    });
}

function loadTradersForLevel(level, table) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);

    fetch(`../api/get-traders.php?level=${level}`, {
        signal: controller.signal
    })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success && data.traders) {
                updateTraderTable(table, data.traders);
            }
        })
        .catch(error => {
            console.error(`Failed to load traders for level ${level}:`, error);
        });
}

function updateTraderTable(table, traders) {
    const tbody = table.querySelector('tbody');
    if (!tbody || !Array.isArray(traders) || traders.length === 0) return;

    tbody.innerHTML = '';

    traders.forEach(trader => {
        const row = document.createElement('tr');
        row.setAttribute('data-trader-id', trader.id);
        row.innerHTML = `
            <td>
                <img src="../assets/images/avatars/${trader.avatar || 'default.jpg'}" 
                     alt="${trader.name}" 
                     class="trader-avatar"
                     onerror="this.src='../assets/images/avatars/default.jpg'">
            </td>
            <td>${trader.trader_id}</td>
            <td class="trader-name">${trader.name}</td>
            <td><span class="trader-category">${trader.category}</span></td>
            <td>${parseFloat(trader.processed_amount || 0).toLocaleString()}</td>
            <td class="active-connections">${trader.active_connections || 0}</td>
            <td class="rating">${trader.rating || 0}</td>
            <td class="percentage-rating">${trader.percentage_rating || 0}%</td>
            <td>
                <button class="btn-connect" 
                        data-trader-id="${trader.id}" 
                        data-trader-name="${trader.name}" 
                        data-level="${trader.level}">
                    CONNECT
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Reinitialize connect buttons
    initializeCopyTrading();
}

// Refresh Functionality
function initializeRefresh() {
    const refreshButton = document.getElementById('refreshData');

    if (refreshButton) {
        refreshButton.addEventListener('click', function () {
            this.disabled = true;
            this.textContent = 'Refreshing...';

            Promise.all([
                updateMarketData(),
                updateUserBalance(),
                updateTraderStats()
            ]).finally(() => {
                this.disabled = false;
                this.textContent = 'Refresh';
                showAlert('Data refreshed successfully!', 'success');
            });
        });
    }
}

// Charts Initialization
function initializeCharts() {
    const chartContainers = document.querySelectorAll('.chart-container');

    chartContainers.forEach(container => {
        if (container.innerHTML.trim() === '') {
            container.innerHTML = '<div class="chart-placeholder" style="display: flex; align-items: center; justify-content: center; height: 200px; color: #666;">Chart data loading...</div>';
        }
    });

    setTimeout(() => {
        chartContainers.forEach(container => {
            const placeholder = container.querySelector('.chart-placeholder');
            if (placeholder) {
                placeholder.innerHTML = `
                    <div style="text-align: center;">
                        <i class="fas fa-chart-area" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                        <p>Live chart data would appear here</p>
                    </div>
                `;
                placeholder.style.color = '#7f8c8d';
            }
        });
    }, 2000);
}

// TradingView Widgets - With comprehensive error handling
function initializeTradingViewWidgets() {
    if (typeof TradingView === 'undefined') {
        console.log('TradingView not loaded, retrying...');
        setTimeout(initializeTradingViewWidgets, 3000);
        return;
    }

    try {
        initializeWidget('tradingview_aapl', 'NASDAQ:AAPL', 'Apple Inc');
        initializeWidget('tradingview_btc', 'BINANCE:BTCUSDT', 'Bitcoin');
        initializeWidget('tradingview_fed', 'ECONOMICS:USINTR', 'Federal Funds Rate');
    } catch (error) {
        console.error('TradingView widget initialization failed:', error);
        showTradingViewFallback();
    }
}

function initializeWidget(containerId, symbol, title) {
    const container = document.getElementById(containerId);
    if (!container) return;

    try {
        new TradingView.widget({
            "width": "100%",
            "height": 350,
            "symbol": symbol,
            "interval": symbol.includes('ECONOMICS') ? "M" : "D",
            "timezone": "Etc/UTC",
            "theme": "dark",
            "style": symbol.includes('ECONOMICS') ? "2" : "1",
            "locale": "en",
            "toolbar_bg": "#f1f3f6",
            "enable_publishing": false,
            "allow_symbol_change": false,
            "container_id": containerId,
            "details": true,
            "hotlist": false,
            "calendar": false,
            "hide_side_toolbar": true,
            "withdateranges": true,
            "hide_volume": symbol.includes('ECONOMICS'),
            "save_image": false,
            "loading_screen": {
                "backgroundColor": "#1a1f2e",
                "foregroundColor": "#ffffff"
            }
        });
    } catch (error) {
        console.error(`Failed to initialize ${title} widget:`, error);
        container.innerHTML = `<div style="height: 350px; display: flex; align-items: center; justify-content: center; color: #666; background: rgba(255,255,255,0.05); border-radius: 8px;">
            <div style="text-align: center;">
                <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                <p style="margin: 0; font-size: 1.1rem;">${title} Chart</p>
                <small style="opacity: 0.7;">Chart temporarily unavailable</small>
            </div>
        </div>`;
    }
}

function showTradingViewFallback() {
    const widgets = ['tradingview_aapl', 'tradingview_btc', 'tradingview_fed'];
    const titles = ['Apple Inc', 'Bitcoin', 'Federal Funds Rate'];

    widgets.forEach((widgetId, index) => {
        const container = document.getElementById(widgetId);
        if (container) {
            container.innerHTML = `<div style="height: 350px; display: flex; align-items: center; justify-content: center; color: #666; background: rgba(255,255,255,0.05); border-radius: 8px;">
                <div style="text-align: center;">
                    <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0; font-size: 1.1rem;">${titles[index]} Chart</p>
                    <small style="opacity: 0.7;">Loading chart data...</small>
                </div>
            </div>`;
        }
    });
}

// Utility Functions
function formatTraderData(trader) {
    return {
        ...trader,
        processed_amount: parseFloat(trader.processed_amount || 0),
        percentage_rating: parseFloat(trader.percentage_rating || 0),
        active_connections: parseInt(trader.active_connections || 0),
        rating: parseInt(trader.rating || 0)
    };
}

function calculateROI(invested, current) {
    if (invested === 0) return 0;
    return ((current - invested) / invested * 100).toFixed(2);
}

function formatCurrency(amount) {
    return '$' + parseFloat(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Enhanced button interactions - Simplified
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.btn-connect, .btn, .button, button:not(.menu-toggle)');

    buttons.forEach(function (button) {
        button.addEventListener('mouseenter', function () {
            this.style.opacity = '0.8';
        });

        button.addEventListener('mouseleave', function () {
            this.style.opacity = '1';
        });

        button.addEventListener('mousedown', function () {
            this.style.transform = 'translateY(1px)';
        });

        button.addEventListener('mouseup', function () {
            this.style.transform = '';
        });
    });
});

// Cleanup function to prevent memory leaks
window.addEventListener('beforeunload', function () {
    if (window.dashboardIntervals) {
        Object.values(window.dashboardIntervals).forEach(interval => {
            clearInterval(interval);
        });
    }
});

// Export dashboard functions
window.Dashboard = {
    updateMarketData,
    updateUserBalance,
    updateTraderStats,
    showCopyTradeModal,
    loadTraderData,
    formatTraderData,
    calculateROI,
    formatCurrency,
    initializeTradingViewWidgets,
    showAlert
};