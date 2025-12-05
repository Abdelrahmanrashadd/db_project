<?php
require_once 'config/database.php';

$format = $_GET['format'] ?? 'csv';
$status = $_GET['status'] ?? 'all';
$export_type = $_GET['export_type'] ?? 'subscriptions'; // 'subscriptions' or 'employees'
$department_id = $_GET['department_id'] ?? 'all';

// Get subscriptions or employees based on export type
$conn = getDBConnection();

if ($format === 'csv' || $format === 'excel') {
    if ($export_type === 'employees') {
        // Export employees
        $where = [];
        if ($department_id !== 'all') {
            $where[] = "e.department_id = " . intval($department_id);
        }
        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get department name first for filename
        $dept_name = '';
        if ($department_id !== 'all') {
            $dept_result = $conn->query("SELECT department_name FROM departments WHERE department_id = " . intval($department_id));
            if ($dept_row = $dept_result->fetch_assoc()) {
                $dept_name = '_' . str_replace(' ', '_', $dept_row['department_name']);
            }
        }
        
        $result = $conn->query("
            SELECT e.*, d.department_name 
            FROM employees e 
            LEFT JOIN departments d ON e.department_id = d.department_id 
            $where_sql
            ORDER BY e.last_name, e.first_name
        ");
        
        // Set headers for download
        $filename = 'employees' . $dept_name . '_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for Excel UTF-8 support
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers for employees
        fputcsv($output, [
            'ID',
            'First Name',
            'Last Name',
            'Full Name',
            'Email',
            'Phone',
            'Position',
            'Department',
            'Is Supervisor',
            'Is Head of Department',
            'Hire Date'
        ]);
        
        // Add data rows
        while ($row = $result->fetch_assoc()) {
            $full_name = trim($row['first_name'] . ' ' . $row['last_name']);
            fputcsv($output, [
                $row['employee_id'],
                $row['first_name'],
                $row['last_name'],
                $full_name,
                $row['email'],
                $row['phone'] ?? '',
                $row['position'],
                $row['department_name'] ?? '',
                $row['is_supervisor'] ? 'Yes' : 'No',
                $row['is_head_of_department'] ? 'Yes' : 'No',
                $row['hire_date'] ?? ''
            ]);
        }
        
        fclose($output);
        closeDBConnection($conn);
        exit;
    } else {
        // Export subscriptions (original functionality)
        $where = [];
        if ($status !== 'all') {
            $where[] = "status = '" . $conn->real_escape_string($status) . "'";
        }
        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $result = $conn->query("SELECT * FROM email_subscriptions $where_sql ORDER BY subscribed_at DESC");
        
        // Set headers for download
        $filename = 'email_subscriptions_' . date('Y-m-d_His') . ($format === 'excel' ? '.csv' : '.csv');
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for Excel UTF-8 support
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, [
            'ID',
            'Email',
            'First Name',
            'Last Name',
            'Full Name',
            'Status',
            'Source',
            'Subscribed At',
            'IP Address',
            'Notes'
        ]);
        
        // Add data rows
        while ($row = $result->fetch_assoc()) {
            $full_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            fputcsv($output, [
                $row['subscription_id'],
                $row['email'],
                $row['first_name'] ?? '',
                $row['last_name'] ?? '',
                $full_name,
                $row['status'],
                $row['source'] ?? '',
                $row['subscribed_at'],
                $row['ip_address'] ?? '',
                $row['notes'] ?? ''
            ]);
        }
        
        fclose($output);
        closeDBConnection($conn);
        exit;
    }
}

// If not exporting, show the export page
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Email List - Email Subscription System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="nav-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <span>Export Email List</span>
        </div>

        <div class="form-container">
            <h2><i class="fas fa-download"></i> Export Email Subscriptions</h2>
            <p style="color: var(--text-secondary); margin-bottom: 30px;">
                Export your email subscription list to CSV or Excel format for use in email marketing tools.
            </p>

            <?php
            $conn = getDBConnection();
            $stats = $conn->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
                FROM email_subscriptions
            ");
            $statistics = $stats->fetch_assoc();
            
            // Get departments for filtering
            $departments = $conn->query("SELECT * FROM departments ORDER BY department_name");
            closeDBConnection($conn);
            ?>

            <div class="stats-grid" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $statistics['total']; ?></h3>
                        <p>Total Subscriptions</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $statistics['active']; ?></h3>
                        <p>Active Subscriptions</p>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 30px;">
                <label>Export Type</label>
                <div style="display: flex; gap: 15px; margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="export_type_radio" value="subscriptions" checked onchange="updateExportForm('subscriptions')">
                        <span>Email Subscriptions</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="export_type_radio" value="employees" onchange="updateExportForm('employees')">
                        <span>Employees by Department</span>
                    </label>
                </div>
            </div>

            <form method="GET" action="export.php" id="exportForm" onsubmit="return false;">
                <input type="hidden" name="export_type" id="export_type" value="subscriptions">
                
                <!-- Subscriptions Export Options -->
                <div id="subscriptionsOptions">
                    <div class="form-group">
                        <label for="status">Filter by Status</label>
                        <select id="status" name="status" required>
                            <option value="all">All Subscriptions</option>
                            <option value="active">Active Only</option>
                            <option value="unsubscribed">Unsubscribed Only</option>
                            <option value="bounced">Bounced Only</option>
                        </select>
                    </div>
                </div>

                <!-- Employees Export Options -->
                <div id="employeesOptions" style="display: none;">
                    <div class="form-group">
                        <label for="department_id">Filter by Department</label>
                        <select id="department_id" name="department_id">
                            <option value="all">All Departments</option>
                            <?php 
                            $departments->data_seek(0); // Reset pointer
                            while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $dept['department_id']; ?>">
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                            Select a department to export only employees from that department, or "All Departments" for complete list.
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="format">Export Format</label>
                    <select id="format" name="format" required>
                        <option value="csv">CSV (Comma Separated Values)</option>
                        <option value="excel">Excel Compatible CSV</option>
                    </select>
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                        Both formats are CSV files. Excel format includes UTF-8 BOM for better Excel compatibility.
                    </small>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong id="exportInfoTitle">Export Information:</strong>
                    <ul id="exportInfoList" style="margin: 10px 0 0 20px;">
                        <li>The exported file will contain all subscription data</li>
                        <li>File includes: Email, Name, Status, Source, Subscription Date</li>
                        <li>Compatible with most email marketing platforms (MailChimp, Constant Contact, etc.)</li>
                        <li>File will be downloaded automatically</li>
                    </ul>
                </div>

                <div class="form-group" style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="button" class="btn btn-primary" style="flex: 1;" onclick="return showExportModal(event);">
                        <i class="fas fa-download"></i> Export Now
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>

        <div class="form-container" style="margin-top: 30px;">
            <h3><i class="fas fa-question-circle"></i> How to Use Exported Files</h3>
            <div style="margin-top: 20px;">
                <h4>For Email Marketing Platforms:</h4>
                <ol style="margin-left: 20px; line-height: 2;">
                    <li>Download the CSV file using the export button above</li>
                    <li>Log into your email marketing platform (MailChimp, Constant Contact, etc.)</li>
                    <li>Navigate to "Import Contacts" or "Add Subscribers"</li>
                    <li>Upload the downloaded CSV file</li>
                    <li>Map the columns (Email, First Name, Last Name)</li>
                    <li>Complete the import process</li>
                </ol>

                <h4 style="margin-top: 20px;">For Microsoft Excel:</h4>
                <ol style="margin-left: 20px; line-height: 2;">
                    <li>Download the Excel Compatible CSV format</li>
                    <li>Open Microsoft Excel</li>
                    <li>Go to File > Open and select the CSV file</li>
                    <li>Excel will automatically format the data into columns</li>
                    <li>Save as .xlsx if needed for further editing</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Export Confirmation Modal -->
    <div id="exportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-download"></i> Confirm Export</h3>
                <button class="modal-close" onclick="closeExportModal()">&times;</button>
            </div>
            <div id="exportModalBody">
                <p>Please wait...</p>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeExportModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmExport()">
                    <i class="fas fa-download"></i> Confirm & Download
                </button>
            </div>
        </div>
    </div>

    <script>
        function updateExportForm(type) {
            document.getElementById('export_type').value = type;
            
            if (type === 'subscriptions') {
                document.getElementById('subscriptionsOptions').style.display = 'block';
                document.getElementById('employeesOptions').style.display = 'none';
                document.getElementById('exportInfoTitle').textContent = 'Export Information:';
                document.getElementById('exportInfoList').innerHTML = `
                    <li>The exported file will contain all subscription data</li>
                    <li>File includes: Email, Name, Status, Source, Subscription Date</li>
                    <li>Compatible with most email marketing platforms (MailChimp, Constant Contact, etc.)</li>
                    <li>File will be downloaded automatically</li>
                `;
            } else {
                document.getElementById('subscriptionsOptions').style.display = 'none';
                document.getElementById('employeesOptions').style.display = 'block';
                document.getElementById('exportInfoTitle').textContent = 'Employee Export Information:';
                document.getElementById('exportInfoList').innerHTML = `
                    <li>The exported file will contain employee data</li>
                    <li>File includes: Name, Email, Phone, Position, Department, Role</li>
                    <li>You can filter by department or export all employees</li>
                    <li>File will be downloaded automatically</li>
                `;
            }
        }

        function showExportModal(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            const exportType = document.querySelector('input[name="export_type_radio"]:checked').value;
            const form = document.getElementById('exportForm');
            const modalBody = document.getElementById('exportModalBody');
            
            if (!modalBody) {
                alert('Modal not found. Please refresh the page.');
                return false;
            }
            
            let summary = '<p><strong>Export Summary:</strong></p><ul style="margin: 15px 0 0 20px; line-height: 1.8;">';
            
            if (exportType === 'subscriptions') {
                const status = document.getElementById('status').value;
                summary += `<li><strong>Type:</strong> Email Subscriptions</li>`;
                summary += `<li><strong>Status Filter:</strong> ${status === 'all' ? 'All Subscriptions' : status.charAt(0).toUpperCase() + status.slice(1)}</li>`;
            } else {
                const deptId = document.getElementById('department_id').value;
                const deptSelect = document.getElementById('department_id');
                const deptName = deptId === 'all' ? 'All Departments' : deptSelect.options[deptSelect.selectedIndex].text;
                summary += `<li><strong>Type:</strong> Employees</li>`;
                summary += `<li><strong>Department Filter:</strong> ${deptName}</li>`;
            }
            
            const format = document.getElementById('format').value;
            summary += `<li><strong>Format:</strong> ${format === 'excel' ? 'Excel Compatible CSV' : 'CSV'}</li>`;
            summary += '</ul><p style="margin-top: 15px;">Ready to download?</p>';
            
            modalBody.innerHTML = summary;
            const modal = document.getElementById('exportModal');
            if (modal) {
                modal.classList.add('active');
            }
            
            return false;
        }

        function closeExportModal() {
            document.getElementById('exportModal').classList.remove('active');
        }

        function confirmExport() {
            const form = document.getElementById('exportForm');
            if (form) {
                closeExportModal();
                // Submit form after closing modal
                setTimeout(function() {
                    form.submit();
                }, 200);
            }
        }
        
        // Prevent form submission on Enter key
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('exportForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    return false;
                });
            }
        });

        window.onclick = function(event) {
            const modal = document.getElementById('exportModal');
            if (event.target === modal) {
                closeExportModal();
            }
        }
    </script>
</body>
</html>

