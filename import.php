<?php
require_once 'config/database.php';

$message = '';
$message_type = '';
$import_stats = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $conn = getDBConnection();
    
    $file = $_FILES['csv_file'];
    $skip_duplicates = isset($_POST['skip_duplicates']);
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $handle = fopen($file['tmp_name'], 'r');
        
        if ($handle !== false) {
            // Skip header row if exists
            $first_line = fgets($handle);
            $has_header = strpos(strtolower($first_line), 'email') !== false;
            if (!$has_header) {
                rewind($handle);
            }
            
            $imported = 0;
            $skipped = 0;
            $errors = [];
            $row_num = $has_header ? 1 : 0;
            
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $row_num++;
                
                if (count($data) < 1) continue;
                
                // Map CSV columns (Email is required)
                $email = trim($data[0] ?? '');
                
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    $errors[] = "Row $row_num: Invalid email address";
                    continue;
                }
                
                // Optional fields
                $first_name = isset($data[1]) ? trim($conn->real_escape_string($data[1])) : '';
                $last_name = isset($data[2]) ? trim($conn->real_escape_string($data[2])) : '';
                $status = isset($data[3]) ? trim($conn->real_escape_string($data[3])) : 'active';
                $source = isset($data[4]) ? trim($conn->real_escape_string($data[4])) : 'import';
                $subscribed_at = isset($data[5]) ? trim($data[5]) : '';
                
                // Validate status
                if (!in_array($status, ['active', 'unsubscribed', 'bounced'])) {
                    $status = 'active';
                }
                
                // Handle subscribed_at date
                $subscribed_at_sql = 'CURRENT_TIMESTAMP';
                if (!empty($subscribed_at)) {
                    // If it's already in MySQL datetime format (YYYY-MM-DD HH:MM:SS)
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $subscribed_at)) {
                        $subscribed_at_sql = "'" . $conn->real_escape_string($subscribed_at) . "'";
                    } 
                    // If it's in datetime-local format (YYYY-MM-DDTHH:MM)
                    elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $subscribed_at)) {
                        $datetime = str_replace('T', ' ', $subscribed_at) . ':00';
                        $subscribed_at_sql = "'" . $conn->real_escape_string($datetime) . "'";
                    }
                    // If it's just a date (YYYY-MM-DD)
                    elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $subscribed_at)) {
                        $subscribed_at_sql = "'" . $conn->real_escape_string($subscribed_at) . " 12:00:00'";
                    }
                }
                
                $email_escaped = $conn->real_escape_string($email);
                
                // Check if email exists
                $check = $conn->query("SELECT subscription_id FROM email_subscriptions WHERE email = '$email_escaped'");
                
                if ($check->num_rows > 0 && $skip_duplicates) {
                    $skipped++;
                    continue;
                } elseif ($check->num_rows > 0) {
                    // Update existing
                    $sql = "UPDATE email_subscriptions SET 
                            first_name = '$first_name',
                            last_name = '$last_name',
                            status = '$status',
                            source = '$source',
                            subscribed_at = $subscribed_at_sql,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE email = '$email_escaped'";
                } else {
                    // Insert new
                    $sql = "INSERT INTO email_subscriptions (email, first_name, last_name, status, source, subscribed_at) 
                            VALUES ('$email_escaped', '$first_name', '$last_name', '$status', '$source', $subscribed_at_sql)";
                }
                
                if ($conn->query($sql)) {
                    $imported++;
                } else {
                    $skipped++;
                    $errors[] = "Row $row_num: " . $conn->error;
                }
            }
            
            fclose($handle);
            
            $import_stats = [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ];
            
            if ($imported > 0) {
                $message = "Successfully imported $imported email(s)!";
                $message_type = 'success';
                if ($skipped > 0) {
                    $message .= " $skipped row(s) skipped.";
                }
            } else {
                $message = "No emails were imported. Please check your CSV file format.";
                $message_type = 'error';
            }
        } else {
            $message = "Error reading CSV file.";
            $message_type = 'error';
        }
    } else {
        $message = "File upload error: " . $file['error'];
        $message_type = 'error';
    }
    
    closeDBConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Email List - Email Subscription System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="nav-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <span>Import Email List</span>
        </div>

        <div class="form-container">
            <h2><i class="fas fa-upload"></i> Import Email Subscriptions from CSV</h2>
            <p style="color: var(--text-secondary); margin-bottom: 30px;">
                Import email addresses from a CSV file into your subscription list.
            </p>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($import_stats && !empty($import_stats['errors'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Import Errors:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach (array_slice($import_stats['errors'], 0, 10) as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                        <?php if (count($import_stats['errors']) > 10): ?>
                            <li>... and <?php echo count($import_stats['errors']) - 10; ?> more errors</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">CSV File *</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required>
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                        Accepted formats: .csv, .txt (Maximum file size: 10MB)
                    </small>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="skip_duplicates" name="skip_duplicates" checked>
                        <label for="skip_duplicates">Skip duplicate emails (don't update existing)</label>
                    </div>
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                        If unchecked, existing emails will be updated with new data.
                    </small>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>CSV File Format:</strong>
                    <p style="margin: 10px 0 0 0;">Your CSV file should have the following columns (in order):</p>
                    <ol style="margin: 10px 0 0 20px;">
                        <li><strong>Email</strong> (required) - Valid email address</li>
                        <li><strong>First Name</strong> (optional)</li>
                        <li><strong>Last Name</strong> (optional)</li>
                        <li><strong>Status</strong> (optional) - active, unsubscribed, or bounced (default: active)</li>
                        <li><strong>Source</strong> (optional) - Source of subscription (default: import)</li>
                        <li><strong>Subscribed At</strong> (optional) - Date/time in format: YYYY-MM-DD HH:MM:SS or YYYY-MM-DDTHH:MM (default: current time)</li>
                    </ol>
                    <p style="margin-top: 10px;"><strong>Example:</strong></p>
                    <pre style="background: #f3f4f6; padding: 10px; border-radius: 5px; margin-top: 5px;">email,first_name,last_name,status,source,subscribed_at
john.doe@example.com,John,Doe,active,website,2024-01-15 10:30:00
jane.smith@example.com,Jane,Smith,active,newsletter,2024-02-20 14:00:00</pre>
                </div>

                <div class="form-group" style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-upload"></i> Import CSV File
                    </button>
                    <a href="subscriptions.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subscriptions
                    </a>
                </div>
            </form>
        </div>

        <div class="form-container" style="margin-top: 30px;">
            <h3><i class="fas fa-question-circle"></i> Import Guidelines</h3>
            <div style="margin-top: 20px;">
                <h4>Best Practices:</h4>
                <ul style="margin-left: 20px; line-height: 2;">
                    <li>Ensure email addresses are valid and properly formatted</li>
                    <li>First row can contain headers (will be automatically detected)</li>
                    <li>Use commas to separate columns</li>
                    <li>Enclose text with commas in quotes (e.g., "Doe, John")</li>
                    <li>File encoding should be UTF-8 for best results</li>
                    <li>Large files will be processed row by row</li>
                </ul>

                <h4 style="margin-top: 20px;">Common Issues:</h4>
                <ul style="margin-left: 20px; line-height: 2;">
                    <li><strong>Invalid emails:</strong> Rows with invalid emails will be skipped</li>
                    <li><strong>Duplicates:</strong> Enable "Skip duplicates" to avoid importing existing emails</li>
                    <li><strong>Format errors:</strong> Make sure your CSV follows the specified format</li>
                    <li><strong>Large files:</strong> Files over 10MB may timeout - split into smaller batches</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>

