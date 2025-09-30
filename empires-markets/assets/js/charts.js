// Charts JavaScript
// Trading Charts and Data Visualization

document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts when page loads
    initializeCharts();
    
    // Initialize chart controls
    initializeChartControls();
    
    // Setup real-time chart updates
    setupRealTimeChartUpdates();
});

// Chart Configuration
const chartConfig = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        }
    },
    scales: {
        x: {
            display: true,
            grid: {
                color: '#4a5568'
            },
            ticks: {
                color: '#bdc3c7'
            }
        },
        y: {
            display: true,
            grid: {
                color: '#4a5568'
            },
            ticks: {
                color: '#bdc3c7'
            }
        }
    }
};

// Chart Colors
const chartColors = {
    primary: '#3498db',
    success: '#27ae60',
    danger: '#e74c3c',
    warning: '#f39c12',
    dark: '#2c3e50',
    light: '#bdc3c7'
};

// Initialize All Charts
function initializeCharts() {
    // Market price charts
    initializeMarketCharts();
    
    // Portfolio charts
    initializePortfolioCharts();
    
    // Performance charts
    initializePerformanceCharts();
    
    // Trading volume charts
    initializeVolumeCharts();
}

// Market Charts
function initializeMarketCharts() {
    const marketCharts = document.querySelectorAll('.market-chart');
    
    marketCharts.forEach(chartElement => {
        const symbol = chartElement.getAttribute('data-symbol');
        if (symbol) {
            createMarketChart(chartElement, symbol);
        }
    });
}

function createMarketChart(element, symbol) {
    const ctx = element.getContext('2d');
    
    // Generate sample data (in real implementation, fetch from API)
    const data = generateSamplePriceData(symbol);
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: symbol,
                data: data.prices,
                borderColor: chartColors.primary,
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4
            }]
        },
        options: {
            ...chartConfig,
            plugins: {
                ...chartConfig.plugins,
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: '#2c3e50',
                    titleColor: '#ffffff',
                    bodyColor: '#bdc3c7',
                    borderColor: '#4a5568',
                    borderWidth: 1
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
    
    // Store chart instance for updates
    element.chartInstance = chart;
    
    return chart;
}

// Portfolio Charts
function initializePortfolioCharts() {
    const portfolioChart = document.getElementById('portfolioChart');
    if (portfolioChart) {
        createPortfolioChart(portfolioChart);
    }
    
    const allocationChart = document.getElementById('allocationChart');
    if (allocationChart) {
        createAllocationChart(allocationChart);
    }
}

function createPortfolioChart(element) {
    const ctx = element.getContext('2d');
    
    const data = generatePortfolioData();
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Portfolio Value',
                data: data.values,
                borderColor: chartColors.success,
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            ...chartConfig,
            scales: {
                ...chartConfig.scales,
                y: {
                    ...chartConfig.scales.y,
                    ticks: {
                        ...chartConfig.scales.y.ticks,
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    element.chartInstance = chart;
    return chart;
}

function createAllocationChart(element) {
    const ctx = element.getContext('2d');
    
    const data = {
        labels: ['Stocks', 'Crypto', 'Forex', 'Commodities'],
        datasets: [{
            data: [40, 30, 20, 10],
            backgroundColor: [
                chartColors.primary,
                chartColors.warning,
                chartColors.success,
                chartColors.danger
            ],
            borderWidth: 2,
            borderColor: '#2c3e50'
        }]
    };
    
    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: '#bdc3c7',
                        padding: 20
                    }
                }
            }
        }
    });
    
    element.chartInstance = chart;
    return chart;
}

// Performance Charts
function initializePerformanceCharts() {
    const performanceChart = document.getElementById('performanceChart');
    if (performanceChart) {
        createPerformanceChart(performanceChart);
    }
}

function createPerformanceChart(element) {
    const ctx = element.getContext('2d');
    
    const data = generatePerformanceData();
    
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Monthly Returns (%)',
                data: data.returns,
                backgroundColor: data.returns.map(value => 
                    value >= 0 ? chartColors.success : chartColors.danger
                ),
                borderColor: data.returns.map(value => 
                    value >= 0 ? '#27ae60' : '#e74c3c'
                ),
                borderWidth: 1
            }]
        },
        options: {
            ...chartConfig,
            scales: {
                ...chartConfig.scales,
                y: {
                    ...chartConfig.scales.y,
                    ticks: {
                        ...chartConfig.scales.y.ticks,
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
    
    element.chartInstance = chart;
    return chart;
}

// Volume Charts
function initializeVolumeCharts() {
    const volumeCharts = document.querySelectorAll('.volume-chart');
    
    volumeCharts.forEach(chartElement => {
        const symbol = chartElement.getAttribute('data-symbol');
        if (symbol) {
            createVolumeChart(chartElement, symbol);
        }
    });
}

function createVolumeChart(element, symbol) {
    const ctx = element.getContext('2d');
    
    const data = generateVolumeData(symbol);
    
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Volume',
                data: data.volumes,
                backgroundColor: 'rgba(52, 152, 219, 0.6)',
                borderColor: chartColors.primary,
                borderWidth: 1
            }]
        },
        options: {
            ...chartConfig,
            scales: {
                ...chartConfig.scales,
                y: {
                    ...chartConfig.scales.y,
                    ticks: {
                        ...chartConfig.scales.y.ticks,
                        callback: function(value) {
                            return formatVolume(value);
                        }
                    }
                }
            }
        }
    });
    
    element.chartInstance = chart;
    return chart;
}

// Chart Controls
function initializeChartControls() {
    // Time range selectors
    const timeRangeButtons = document.querySelectorAll('.chart-timerange button');
    timeRangeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const timeRange = this.getAttribute('data-range');
            const chartContainer = this.closest('.chart-card');
            const chart = chartContainer.querySelector('canvas');
            
            if (chart && chart.chartInstance) {
                updateChartTimeRange(chart.chartInstance, timeRange);
            }
            
            // Update button states
            timeRangeButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Chart type selectors
    const chartTypeButtons = document.querySelectorAll('.chart-type button');
    chartTypeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const chartType = this.getAttribute('data-type');
            const chartContainer = this.closest('.chart-card');
            const chart = chartContainer.querySelector('canvas');
            
            if (chart && chart.chartInstance) {
                updateChartType(chart.chartInstance, chartType);
            }
            
            // Update button states
            chartTypeButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

function updateChartTimeRange(chart, timeRange) {
    // Generate new data based on time range
    let newData;
    
    switch(timeRange) {
        case '1D':
            newData = generateSamplePriceData('symbol', 24); // 24 hours
            break;
        case '1W':
            newData = generateSamplePriceData('symbol', 7); // 7 days
            break;
        case '1M':
            newData = generateSamplePriceData('symbol', 30); // 30 days
            break;
        case '3M':
            newData = generateSamplePriceData('symbol', 90); // 90 days
            break;
        case '1Y':
            newData = generateSamplePriceData('symbol', 365); // 365 days
            break;
        default:
            newData = generateSamplePriceData('symbol', 30);
    }
    
    chart.data.labels = newData.labels;
    chart.data.datasets[0].data = newData.prices;
    chart.update();
}

function updateChartType(chart, chartType) {
    chart.config.type = chartType;
    
    if (chartType === 'candlestick') {
        // Configure for candlestick chart
        chart.data.datasets[0] = {
            ...chart.data.datasets[0],
            type: 'candlestick'
        };
    } else if (chartType === 'line') {
        // Configure for line chart
        chart.data.datasets[0] = {
            ...chart.data.datasets[0],
            type: 'line',
            fill: true,
            tension: 0.4
        };
    } else if (chartType === 'area') {
        // Configure for area chart
        chart.data.datasets[0] = {
            ...chart.data.datasets[0],
            type: 'line',
            fill: true,
            backgroundColor: 'rgba(52, 152, 219, 0.2)'
        };
    }
    
    chart.update();
}

// Real-time Updates
function setupRealTimeChartUpdates() {
    // Update charts every 30 seconds
    setInterval(updateAllCharts, 30000);
    
    // Update volume charts every minute
    setInterval(updateVolumeCharts, 60000);
}

function updateAllCharts() {
    const charts = document.querySelectorAll('canvas[data-symbol]');
    
    charts.forEach(canvas => {
        if (canvas.chartInstance) {
            const symbol = canvas.getAttribute('data-symbol');
            updateChartData(canvas.chartInstance, symbol);
        }
    });
}

function updateChartData(chart, symbol) {
    // In real implementation, fetch new data from API
    EmpiresMarkets.makeRequest(`../api/get-market-data.php?symbol=${symbol}`)
        .then(data => {
            if (data.success) {
                addNewDataPoint(chart, data.price, data.timestamp);
            }
        })
        .catch(error => {
            console.error('Failed to update chart data:', error);
        });
}

function addNewDataPoint(chart, price, timestamp) {
    const maxDataPoints = 50;
    
    // Add new data point
    chart.data.labels.push(formatTimestamp(timestamp));
    chart.data.datasets[0].data.push(price);
    
    // Remove old data points if we have too many
    if (chart.data.labels.length > maxDataPoints) {
        chart.data.labels.shift();
        chart.data.datasets[0].data.shift();
    }
    
    chart.update('none'); // Update without animation for real-time feel
}

function updateVolumeCharts() {
    const volumeCharts = document.querySelectorAll('.volume-chart');
    
    volumeCharts.forEach(chart => {
        if (chart.chartInstance) {
            const symbol = chart.getAttribute('data-symbol');
            // Update volume data
            const newVolumeData = Math.random() * 1000000;
            addNewDataPoint(chart.chartInstance, newVolumeData, new Date());
        }
    });
}

// Data Generation Functions (for demo purposes)
function generateSamplePriceData(symbol, points = 30) {
    const labels = [];
    const prices = [];
    const basePrice = getBasePrice(symbol);
    
    for (let i = points; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        labels.push(formatDate(date));
        
        // Generate realistic price movement
        const variation = (Math.random() - 0.5) * basePrice * 0.1;
        const price = basePrice + variation + (Math.sin(i * 0.1) * basePrice * 0.05);
        prices.push(Math.max(price, basePrice * 0.8));
    }
    
    return { labels, prices };
}

function generatePortfolioData() {
    const labels = [];
    const values = [];
    let currentValue = 10000;
    
    for (let i = 30; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        labels.push(formatDate(date));
        
        // Simulate portfolio growth with some volatility
        const change = (Math.random() - 0.4) * 500;
        currentValue += change;
        values.push(Math.max(currentValue, 5000));
    }
    
    return { labels, values };
}

function generatePerformanceData() {
    const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const returns = [];
    
    for (let i = 0; i < labels.length; i++) {
        // Generate random returns between -10% and +15%
        returns.push((Math.random() * 25 - 10).toFixed(2));
    }
    
    return { labels, returns };
}

function generateVolumeData(symbol) {
    const labels = [];
    const volumes = [];
    
    for (let i = 20; i >= 0; i--) {
        const date = new Date();
        date.setHours(date.getHours() - i);
        labels.push(formatTime(date));
        
        // Generate random volume
        volumes.push(Math.random() * 1000000);
    }
    
    return { labels, volumes };
}

// Utility Functions
function getBasePrice(symbol) {
    const basePrices = {
        'AAPL': 216.00,
        'BTCUSDT': 106902.46,
        'FEDFUNC': 4.33,
        'EURUSD': 1.0542,
        'GOLD': 2654.32
    };
    
    return basePrices[symbol] || 100;
}

function formatDate(date) {
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatTime(date) {
    return date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

function formatVolume(volume) {
    if (volume >= 1000000) {
        return (volume / 1000000).toFixed(1) + 'M';
    } else if (volume >= 1000) {
        return (volume / 1000).toFixed(1) + 'K';
    }
    return volume.toString();
}

// Export chart functions
window.Charts = {
    initializeCharts,
    createMarketChart,
    createPortfolioChart,
    updateChartData,
    updateChartTimeRange,
    updateChartType,
    generateSamplePriceData,
    formatVolume
};

// Check if Chart.js is available
if (typeof Chart === 'undefined') {
    console.warn('Chart.js library not found. Charts will not be rendered.');
    
    // Provide fallback functionality
    window.Charts = {
        initializeCharts: () => {
            console.log('Chart.js not available - using fallback');
            const chartContainers = document.querySelectorAll('.chart-container, canvas');
            chartContainers.forEach(container => {
                if (!container.innerHTML || container.tagName === 'CANVAS') {
                    const fallback = document.createElement('div');
                    fallback.className = 'chart-fallback';
                    fallback.style.cssText = `
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        height: 200px;
                        background-color: #2a3441;
                        border-radius: 4px;
                        color: #7f8c8d;
                        font-style: italic;
                    `;
                    fallback.textContent = 'Chart data visualization requires Chart.js library';
                    
                    if (container.tagName === 'CANVAS') {
                        container.parentNode.replaceChild(fallback, container);
                    } else {
                        container.appendChild(fallback);
                    }
                }
            });
        }
    };
}
