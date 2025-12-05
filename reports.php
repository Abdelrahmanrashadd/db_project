<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Get overall statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
        SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
    FROM email_subscriptions
");
$overall_stats = $stats->fetch_assoc();

// Get subscriptions by source
$source_stats = $conn->query("
    SELECT 
        source,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed
    FROM email_subscriptions
    GROUP BY source
    ORDER BY total DESC
");

// Get subscriptions by month
$monthly_stats = $conn->query("
    SELECT 
        DATE_FORMAT(subscribed_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM email_subscriptions
    GROUP BY DATE_FORMAT(subscribed_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

// Get department employee statistics
// NOTE: replaced is_head_of_department check with comparison to departments.head_employee_id
$dept_stats = $conn->query("
    SELECT 
        d.department_name,
        d.head_employee_id,
        CONCAT(h.first_name, ' ', h.last_name) AS head_name,
        COUNT(e.employee_id) as total_employees,
        SUM(CASE WHEN e.is_supervisor = 1 THEN 1 ELSE 0 END) as supervisors,
        SUM(CASE WHEN e.employee_id = d.head_employee_id THEN 1 ELSE 0 END) as heads
    FROM departments d
    LEFT JOIN employees e ON d.department_id = e.department_id
    LEFT JOIN employees h ON d.head_employee_id = h.employee_id
    GROUP BY d.department_id, d.department_name, d.head_employee_id
    ORDER BY total_employees DESC
");

// Get recent subscriptions
$recent = $conn->query("
    SELECT email, first_name, last_name, status, source, subscribed_at
    FROM email_subscriptions
    ORDER BY subscribed_at DESC
    LIMIT 10
");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Email Subscription System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div class="nav-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <span>Reports & Analytics</span>
        </div>

        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;"><i class="fas fa-chart-bar"></i> Reports & Analytics Dashboard</h2>
                <button class="btn btn-primary" onclick="refreshCharts()" title="Refresh Charts">
                    <i class="fas fa-sync-alt"></i> Refresh Data
                </button>
            </div>

            <!-- Overall Statistics -->
            <div class="stats-grid" style="margin-top: 20px;">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $overall_stats['total']; ?></h3>
                        <p>Total Subscriptions</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $overall_stats['active']; ?></h3>
                        <p>Active</p>
                        <small style="font-size: 0.8rem; color: var(--text-secondary);">
                            <?php echo $overall_stats['total'] > 0 ? round(($overall_stats['active'] / $overall_stats['total']) * 100, 1) : 0; ?>%
                        </small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $overall_stats['unsubscribed']; ?></h3>
                        <p>Unsubscribed</p>
                        <small style="font-size: 0.8rem; color: var(--text-secondary);">
                            <?php echo $overall_stats['total'] > 0 ? round(($overall_stats['unsubscribed'] / $overall_stats['total']) * 100, 1) : 0; ?>%
                        </small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $overall_stats['bounced']; ?></h3>
                        <p>Bounced</p>
                        <small style="font-size: 0.8rem; color: var(--text-secondary);">
                            <?php echo $overall_stats['total'] > 0 ? round(($overall_stats['bounced'] / $overall_stats['total']) * 100, 1) : 0; ?>%
                        </small>
                    </div>
                </div>
            </div>

            <!-- Status Chart -->
            <div style="background: white; border-radius: 12px; padding: 30px; margin-top: 30px; box-shadow: var(--shadow-lg);">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-chart-pie"></i> Subscription Status Distribution</h3>
                <div id="statusChartContainer">
                    <canvas id="statusChart" style="max-height: 300px;"></canvas>
                </div>
                <div id="statusChartEmpty" style="display: none; text-align: center; padding: 40px; color: var(--text-secondary);">
                    <i class="fas fa-chart-pie" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No subscription data available yet.</p>
                    <p style="font-size: 0.9rem; margin-top: 10px;">Add some email subscriptions to see the chart.</p>
                </div>
            </div>

            <!-- Subscriptions by Source -->
            <div class="table-container" style="margin-top: 30px;">
                <h3><i class="fas fa-tags"></i> Subscriptions by Source</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Total</th>
                            <th>Active</th>
                            <th>Unsubscribed</th>
                            <th>Active Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $source_stats->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['source']); ?></strong></td>
                                <td><?php echo $row['total']; ?></td>
                                <td><span class="badge badge-success"><?php echo $row['active']; ?></span></td>
                                <td><span class="badge badge-danger"><?php echo $row['unsubscribed']; ?></span></td>
                                <td>
                                    <?php 
                                    $rate = $row['total'] > 0 ? round(($row['active'] / $row['total']) * 100, 1) : 0;
                                    echo $rate . '%';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Monthly Trends -->
            <div style="background: white; border-radius: 12px; padding: 30px; margin-top: 30px; box-shadow: var(--shadow-lg);">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-chart-line"></i> Monthly Subscription Trends (Last 12 Months)</h3>
                <div id="monthlyChartContainer">
                    <canvas id="monthlyChart" style="max-height: 300px;"></canvas>
                </div>
                <div id="monthlyChartEmpty" style="display: none; text-align: center; padding: 40px; color: var(--text-secondary);">
                    <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No monthly data available yet.</p>
                    <p style="font-size: 0.9rem; margin-top: 10px;">Subscriptions will appear here as they are added.</p>
                </div>
            </div>

            <!-- Department Statistics -->
            <div class="table-container" style="margin-top: 30px;">
                <h3><i class="fas fa-building"></i> Department Employee Statistics</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Total Employees</th>
                            <th>Supervisors</th>
                            <th>Heads</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $dept_stats->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['department_name']); ?></strong></td>
                                <td><?php echo $row['total_employees']; ?></td>
                                <td><span class="badge badge-warning"><?php echo $row['supervisors']; ?></span></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php 
                                          echo intval($row['heads']) > 0 ? htmlspecialchars($row['head_name'] ?: 'Head set') : 'Not set';
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Subscriptions -->
            <div class="table-container" style="margin-top: 30px;">
                <h3><i class="fas fa-clock"></i> Recent Subscriptions</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Subscribed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $recent->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'active' => 'badge-success',
                                        'unsubscribed' => 'badge-danger',
                                        'bounced' => 'badge-warning'
                                    ];
                                    $class = $status_class[$row['status']] ?? 'badge-secondary';
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo ucfirst($row['status']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['source']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['subscribed_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="export.php" class="btn btn-primary">
                    <i class="fas fa-download"></i> Export Data
                </a>
            </div>
        </div>
    </div>

    <script>
        // Global chart instances for updating
        let statusChart = null;
        let monthlyChart = null;

        // Function to initialize or update Status Chart
        function initStatusChart() {
            const ctx = document.getElementById('statusChart');
            if (!ctx) return;
            
            // Destroy existing chart if it exists
            if (statusChart) {
                statusChart.destroy();
            }

            // Get data from PHP (will be updated via AJAX)
            const active = <?php echo intval($overall_stats['active']); ?>;
            const unsubscribed = <?php echo intval($overall_stats['unsubscribed']); ?>;
            const bounced = <?php echo intval($overall_stats['bounced']); ?>;
            const total = active + unsubscribed + bounced;

            // Show empty state if no data
            const chartContainer = document.getElementById('statusChartContainer');
            const emptyState = document.getElementById('statusChartEmpty');
            if (total === 0) {
                if (chartContainer) chartContainer.style.display = 'none';
                if (emptyState) emptyState.style.display = 'block';
                return;
            } else {
                if (chartContainer) chartContainer.style.display = 'block';
                if (emptyState) emptyState.style.display = 'none';
            }

            statusChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Unsubscribed', 'Bounced'],
                    datasets: [{
                        data: [active, unsubscribed, bounced],
                        backgroundColor: [
                            '#10b981',
                            '#ef4444',
                            '#f59e0b'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Function to initialize or update Monthly Chart
        function initMonthlyChart() {
            const ctx = document.getElementById('monthlyChart');
            if (!ctx) return;
            
            // Destroy existing chart if it exists
            if (monthlyChart) {
                monthlyChart.destroy();
            }

            <?php
            $months = [];
            $counts = [];
            if ($monthly_stats) {
                $monthly_stats->data_seek(0);
                while ($row = $monthly_stats->fetch_assoc()) {
                    $months[] = date('M Y', strtotime($row['month'] . '-01'));
                    $counts[] = $row['count'];
                }
                $months = array_reverse($months);
                $counts = array_reverse($counts);
            }
            ?>

            // Show empty state if no data
            const chartContainer = document.getElementById('monthlyChartContainer');
            const emptyState = document.getElementById('monthlyChartEmpty');
            const hasData = <?php echo !empty($months) ? 'true' : 'false'; ?>;
            
            if (!hasData || <?php echo json_encode($counts); ?>.reduce((a, b) => a + b, 0) === 0) {
                if (chartContainer) chartContainer.style.display = 'none';
                if (emptyState) emptyState.style.display = 'block';
                return;
            } else {
                if (chartContainer) chartContainer.style.display = 'block';
                if (emptyState) emptyState.style.display = 'none';
            }

            monthlyChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'New Subscriptions',
                        data: <?php echo json_encode($counts); ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#4f46e5',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                        duration: 1000
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });
        }

        // Function to refresh chart data
        function refreshCharts() {
            const refreshBtn = document.querySelector('button[onclick="refreshCharts()"]');
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
                refreshBtn.disabled = true;
            }

            fetch('api/get_chart_data.php')
                .then(response => response.json())
                .then(data => {
                    // Update status chart
                    if (data.status) {
                        const total = (data.status.active || 0) + (data.status.unsubscribed || 0) + (data.status.bounced || 0);
                        const chartContainer = document.getElementById('statusChartContainer');
                        const emptyState = document.getElementById('statusChartEmpty');
                        
                        if (total === 0) {
                            if (chartContainer) chartContainer.style.display = 'none';
                            if (emptyState) emptyState.style.display = 'block';
                        } else {
                            if (chartContainer) chartContainer.style.display = 'block';
                            if (emptyState) emptyState.style.display = 'none';
                            if (statusChart) {
                                statusChart.data.datasets[0].data = [
                                    data.status.active || 0,
                                    data.status.unsubscribed || 0,
                                    data.status.bounced || 0
                                ];
                                statusChart.update('active');
                            } else {
                                initStatusChart();
                            }
                        }
                    }

                    // Update monthly chart
                    if (data.monthly) {
                        const hasData = data.monthly.counts && data.monthly.counts.reduce((a, b) => a + b, 0) > 0;
                        const chartContainer = document.getElementById('monthlyChartContainer');
                        const emptyState = document.getElementById('monthlyChartEmpty');
                        
                        if (!hasData) {
                            if (chartContainer) chartContainer.style.display = 'none';
                            if (emptyState) emptyState.style.display = 'block';
                        } else {
                            if (chartContainer) chartContainer.style.display = 'block';
                            if (emptyState) emptyState.style.display = 'none';
                            if (monthlyChart) {
                                monthlyChart.data.labels = data.monthly.labels || [];
                                monthlyChart.data.datasets[0].data = data.monthly.counts || [];
                                monthlyChart.update('active');
                            } else {
                                initMonthlyChart();
                            }
                        }
                    }

                    // Update statistics cards
                    if (data.stats) {
                        const statCards = document.querySelectorAll('.stat-card h3');
                        if (statCards.length >= 4) {
                            statCards[0].textContent = data.stats.total || 0;
                            statCards[1].textContent = data.stats.active || 0;
                            statCards[2].textContent = data.stats.unsubscribed || 0;
                            statCards[3].textContent = data.stats.bounced || 0;
                            
                            // Update percentages
                            const total = parseInt(data.stats.total) || 0;
                            if (total > 0) {
                                const activePercent = ((parseInt(data.stats.active) || 0) / total * 100).toFixed(1);
                                const unsubPercent = ((parseInt(data.stats.unsubscribed) || 0) / total * 100).toFixed(1);
                                const bouncedPercent = ((parseInt(data.stats.bounced) || 0) / total * 100).toFixed(1);
                                
                                const smalls = document.querySelectorAll('.stat-card small');
                                if (smalls.length >= 3) {
                                    smalls[0].textContent = activePercent + '%';
                                    smalls[1].textContent = unsubPercent + '%';
                                    smalls[2].textContent = bouncedPercent + '%';
                                }
                            }
                        }
                    }
                    
                    if (refreshBtn) {
                        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Data';
                        refreshBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.log('Chart refresh failed:', error);
                    // If API fails, reload page
                    if (confirm('Failed to refresh data. Reload page?')) {
                        window.location.reload();
                    }
                    if (refreshBtn) {
                        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Data';
                        refreshBtn.disabled = false;
                    }
                });
        }

        // Initialize charts on page load
        document.addEventListener('DOMContentLoaded', function() {
            initStatusChart();
            initMonthlyChart();
            
            // Auto-refresh charts every 30 seconds
            setInterval(refreshCharts, 30000);
            
            // Also refresh when window regains focus
            window.addEventListener('focus', refreshCharts);
        });

        // Refresh charts when visibility changes (tab switch)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                refreshCharts();
            }
        });
    </script>
</body>
</html>
