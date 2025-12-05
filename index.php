<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email List Subscription System - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1><i class="fas fa-envelope"></i> Email List Subscription System</h1>
                <p class="subtitle">Manage departments, employees, and email subscriptions</p>
            </div>
        </header>

        <main class="dashboard">
            <?php if (!testConnection()): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Database Connection Error!</strong> Please ensure:
                    <ul>
                        <li>XAMPP MySQL is running</li>
                        <li>Database 'email_subscription_db' exists</li>
                        <li>Schema has been imported from <code>database/schema.sql</code></li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-info">
                            <?php
                            $conn = getDBConnection();
                            $result = $conn->query("SELECT COUNT(*) as count FROM departments");
                            $dept_count = $result->fetch_assoc()['count'];
                            closeDBConnection($conn);
                            ?>
                            <h3><?php echo $dept_count; ?></h3>
                            <p>Departments</p>
                        </div>
                        <a href="departments.php" class="stat-link">Manage <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <?php
                            $conn = getDBConnection();
                            $result = $conn->query("SELECT COUNT(*) as count FROM employees");
                            $emp_count = $result->fetch_assoc()['count'];
                            closeDBConnection($conn);
                            ?>
                            <h3><?php echo $emp_count; ?></h3>
                            <p>Employees</p>
                        </div>
                        <a href="employees.php" class="stat-link">Manage <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <div class="stat-info">
                            <?php
                            $conn = getDBConnection();
                            $result = $conn->query("SELECT COUNT(*) as count FROM email_subscriptions");
                            $email_count = $result->fetch_assoc()['count'];
                            closeDBConnection($conn);
                            ?>
                            <h3><?php echo $email_count; ?></h3>
                            <p>Email Subscriptions</p>
                        </div>
                        <a href="subscriptions.php" class="stat-link">Manage <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <?php
                            $conn = getDBConnection();
                            $result = $conn->query("SELECT COUNT(*) as count FROM email_subscriptions WHERE status = 'active'");
                            $active_count = $result->fetch_assoc()['count'];
                            closeDBConnection($conn);
                            ?>
                            <h3><?php echo $active_count; ?></h3>
                            <p>Active Subscriptions</p>
                        </div>
                        <a href="subscriptions.php?status=active" class="stat-link">View <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="quick-actions">
                    <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    <div class="action-grid">
                        <a href="departments.php" class="action-card">
                            <i class="fas fa-building"></i>
                            <h3>Manage Departments</h3>
                            <p>Add, edit, or remove departments</p>
                        </a>
                        <a href="employees.php" class="action-card">
                            <i class="fas fa-users"></i>
                            <h3>Manage Employees</h3>
                            <p>View and manage employee records</p>
                        </a>
                        <a href="subscriptions.php" class="action-card">
                            <i class="fas fa-envelope"></i>
                            <h3>Email Subscriptions</h3>
                            <p>View and manage email list</p>
                        </a>
                        <a href="export.php" class="action-card">
                            <i class="fas fa-download"></i>
                            <h3>Export to CSV/Excel</h3>
                            <p>Download email list as CSV or Excel</p>
                        </a>
                        <a href="import.php" class="action-card">
                            <i class="fas fa-upload"></i>
                            <h3>Import from CSV</h3>
                            <p>Import emails from CSV file</p>
                        </a>
                        <a href="reports.php" class="action-card">
                            <i class="fas fa-chart-bar"></i>
                            <h3>Reports & Analytics</h3>
                            <p>View subscription statistics</p>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <footer class="footer">
            <p>&copy; 2024 Email List Subscription System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

