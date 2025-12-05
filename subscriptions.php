<?php
require_once 'config/database.php';

// Get current filter parameters for redirects
$current_status = $_GET['status'] ?? $_POST['status'] ?? 'all';
$current_search = $_GET['search'] ?? $_POST['search'] ?? '';
$current_page = $_GET['page'] ?? $_POST['page'] ?? 1;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $email = $conn->real_escape_string($_POST['email']);
                $first_name = $conn->real_escape_string($_POST['first_name'] ?? '');
                $last_name = $conn->real_escape_string($_POST['last_name'] ?? '');
                $source = $conn->real_escape_string($_POST['source'] ?? 'website');
                $status = $conn->real_escape_string($_POST['status'] ?? 'active');
                $notes = $conn->real_escape_string($_POST['notes'] ?? '');
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                $user_agent = $conn->real_escape_string($_SERVER['HTTP_USER_AGENT'] ?? '');
                
                // Handle subscribed_at date
                $subscribed_at = '';
                if (!empty($_POST['subscribed_at'])) {
                    // Convert datetime-local format (YYYY-MM-DDTHH:mm) to MySQL datetime format
                    $date_value = $_POST['subscribed_at'];
                    // Replace 'T' with space and add seconds if not present
                    $datetime = str_replace('T', ' ', $date_value);
                    if (strlen($datetime) === 16) {
                        $datetime .= ':00'; // Add seconds if missing
                    }
                    $subscribed_at = "'" . $conn->real_escape_string($datetime) . "'";
                } else {
                    $subscribed_at = 'CURRENT_TIMESTAMP';
                }
                
                $sql = "INSERT INTO email_subscriptions (email, first_name, last_name, status, source, notes, ip_address, user_agent, subscribed_at) 
                        VALUES ('$email', '$first_name', '$last_name', '$status', '$source', '$notes', '$ip_address', '$user_agent', $subscribed_at)";
                if ($conn->query($sql)) {
                    // Redirect to prevent form resubmission
                    header("Location: subscriptions.php?msg=created&status=" . urlencode($current_status) . "&search=" . urlencode($current_search));
                    exit;
                } else {
                    $message = "Error: " . ($conn->error ?: "Email already exists!");
                    $message_type = "error";
                }
                break;
                
            case 'update':
                $id = intval($_POST['subscription_id']);
                if ($id <= 0) {
                    $message = "Invalid subscription ID!";
                    $message_type = "error";
                    break;
                }
                
                $email = $conn->real_escape_string($_POST['email']);
                $first_name = $conn->real_escape_string($_POST['first_name'] ?? '');
                $last_name = $conn->real_escape_string($_POST['last_name'] ?? '');
                $status = $conn->real_escape_string($_POST['status']);
                $source = $conn->real_escape_string($_POST['source'] ?? '');
                $notes = $conn->real_escape_string($_POST['notes'] ?? '');
                
                // Build the SET clause
                $set_clause = "email='$email', 
                               first_name='$first_name', 
                               last_name='$last_name', 
                               status='$status',
                               source='$source',
                               notes='$notes'";
                
                // Add subscribed_at if provided - convert datetime-local to MySQL format
                if (!empty($_POST['subscribed_at'])) {
                    $date_value = $_POST['subscribed_at'];
                    // Convert datetime-local format (YYYY-MM-DDTHH:mm) to MySQL datetime format
                    $datetime = str_replace('T', ' ', $date_value);
                    if (strlen($datetime) === 16) {
                        $datetime .= ':00'; // Add seconds if missing
                    }
                    $subscribed_at_value = $conn->real_escape_string($datetime);
                    $set_clause .= ", subscribed_at='$subscribed_at_value'";
                }
                
                $set_clause .= ", updated_at=CURRENT_TIMESTAMP";
                
                $sql = "UPDATE email_subscriptions SET $set_clause WHERE subscription_id=$id";
                if ($conn->query($sql)) {
                    // Redirect to prevent form resubmission and refresh data
                    header("Location: subscriptions.php?msg=updated&status=" . urlencode($current_status) . "&search=" . urlencode($current_search) . "&page=$current_page");
                    exit;
                } else {
                    $message = "Error: " . $conn->error;
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                $id = intval($_POST['subscription_id']);
                $sql = "DELETE FROM email_subscriptions WHERE subscription_id=$id";
                if ($conn->query($sql)) {
                    // Redirect to prevent form resubmission
                    header("Location: subscriptions.php?msg=deleted&status=" . urlencode($current_status) . "&search=" . urlencode($current_search) . "&page=$current_page");
                    exit;
                } else {
                    $message = "Error: " . $conn->error;
                    $message_type = "error";
                }
                break;
                
            case 'bulk_unsubscribe':
                $ids = $_POST['subscription_ids'] ?? [];
                if (!empty($ids)) {
                    $ids_str = implode(',', array_map('intval', $ids));
                    $sql = "UPDATE email_subscriptions SET status='unsubscribed' WHERE subscription_id IN ($ids_str)";
                    if ($conn->query($sql)) {
                        $message = count($ids) . " subscription(s) unsubscribed successfully!";
                        $message_type = "success";
                    }
                }
                break;
        }
    }
    closeDBConnection($conn);
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Handle redirect messages
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'created':
            $message = "Email subscription added successfully!";
            $message_type = "success";
            break;
        case 'updated':
            $message = "Subscription updated successfully!";
            $message_type = "success";
            break;
        case 'deleted':
            $message = "Subscription deleted successfully!";
            $message_type = "success";
            break;
    }
}

// Build query
$conn = getDBConnection();
$where = [];
if ($status_filter !== 'all') {
    $where[] = "status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($search) {
    $search_term = $conn->real_escape_string($search);
    $where[] = "(email LIKE '%$search_term%' OR first_name LIKE '%$search_term%' OR last_name LIKE '%$search_term%')";
}
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM email_subscriptions $where_sql");
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

// Get subscriptions
$subscriptions = $conn->query("
    SELECT * FROM email_subscriptions 
    $where_sql
    ORDER BY subscribed_at DESC 
    LIMIT $per_page OFFSET $offset
");

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
        SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
    FROM email_subscriptions
");
$statistics = $stats->fetch_assoc();
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Subscriptions - Email Subscription System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="nav-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <span>Email Subscriptions</span>
        </div>

        <!-- Statistics Cards -->
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
                    <p>Active</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $statistics['unsubscribed']; ?></h3>
                    <p>Unsubscribed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $statistics['bounced']; ?></h3>
                    <p>Bounced</p>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-envelope-open-text"></i> Email Subscriptions</h2>
                <div class="table-actions">
                    <a href="export.php" class="btn btn-success">
                        <i class="fas fa-download"></i> Export CSV/Excel
                    </a>
                    <a href="import.php" class="btn btn-secondary">
                        <i class="fas fa-upload"></i> Import CSV
                    </a>
                    <button class="btn btn-primary" onclick="openModal('createModal')">
                        <i class="fas fa-plus"></i> Add Subscription
                    </button>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Email, name...">
                </div>
                <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="unsubscribed" <?php echo $status_filter === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                        <option value="bounced" <?php echo $status_filter === 'bounced' ? 'selected' : ''; ?>>Bounced</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <?php if ($status_filter !== 'all' || $search): ?>
                    <a href="subscriptions.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>

            <form method="POST" id="bulkForm">
                <input type="hidden" name="action" value="bulk_unsubscribe">
                <div style="margin-bottom: 10px;">
                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Unsubscribe selected emails?')">
                        <i class="fas fa-ban"></i> Unsubscribe Selected
                    </button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Subscribed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($subscriptions && $subscriptions->num_rows > 0): ?>
                            <?php while ($row = $subscriptions->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="subscription_ids[]" value="<?php echo $row['subscription_id']; ?>"></td>
                                    <td><?php echo $row['subscription_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['email']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name']) ?: 'N/A'); ?></td>
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
                                    <td>
                                        <button type="button" class="btn btn-sm btn-secondary" 
                                                data-subscription='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>'
                                                onclick="editSubscriptionFromButton(this)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-sub-id="<?php echo $row['subscription_id']; ?>"
                                                data-sub-email="<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>"
                                                onclick="deleteSubscriptionFromButton(this)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px;">
                                    No subscriptions found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px; align-items: center;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total; ?> total)</span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-secondary">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-envelope"></i> Add Email Subscription</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST" id="subscriptionForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="subscription_id" id="subscription_id">
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="unsubscribed">Unsubscribed</option>
                            <option value="bounced">Bounced</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="source">Source</label>
                        <select id="source" name="source">
                            <option value="website">Website</option>
                            <option value="newsletter">Newsletter</option>
                            <option value="campaign">Campaign</option>
                            <option value="manual">Manual</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subscribed_at">Subscribed Date & Time</label>
                    <input type="datetime-local" id="subscribed_at" name="subscribed_at">
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                        Leave empty to keep current date, or change to update subscription date.
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="form-group" style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <p>Are you sure you want to delete subscription for "<strong id="deleteEmail"></strong>"?</p>
            <form method="POST" id="deleteForm" style="margin-top: 20px;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="subscription_id" id="deleteSubId">
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="subscription_ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            if (modalId === 'createModal') {
                document.getElementById('subscriptionForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-envelope"></i> Add Email Subscription';
            }
        }

        function editSubscriptionFromButton(button) {
            try {
                const subData = button.getAttribute('data-subscription');
                if (!subData) {
                    alert('Subscription data not found. Please refresh the page.');
                    return;
                }
                
                const sub = JSON.parse(subData);
                editSubscription(sub);
            } catch (e) {
                console.error('Error parsing subscription data:', e);
                alert('Error loading subscription data. Please try again.');
            }
        }
        
        function editSubscription(sub) {
            try {
                const form = document.getElementById('subscriptionForm');
                if (!form) {
                    alert('Form not found. Please refresh the page.');
                    return;
                }
                
                document.getElementById('formAction').value = 'update';
                document.getElementById('subscription_id').value = sub.subscription_id || '';
                document.getElementById('email').value = sub.email || '';
                document.getElementById('first_name').value = sub.first_name || '';
                document.getElementById('last_name').value = sub.last_name || '';
                document.getElementById('status').value = sub.status || 'active';
                document.getElementById('source').value = sub.source || 'website';
                document.getElementById('notes').value = sub.notes || '';
                
                // Set subscribed_at date if available
                const subscribedAtField = document.getElementById('subscribed_at');
                if (subscribedAtField) {
                    if (sub.subscribed_at) {
                        // Convert MySQL datetime to datetime-local format (YYYY-MM-DDTHH:mm)
                        const date = new Date(sub.subscribed_at);
                        if (!isNaN(date.getTime())) {
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');
                            const hours = String(date.getHours()).padStart(2, '0');
                            const minutes = String(date.getMinutes()).padStart(2, '0');
                            subscribedAtField.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                        } else {
                            subscribedAtField.value = '';
                        }
                    } else {
                        subscribedAtField.value = '';
                    }
                }
                
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Subscription';
                openModal('createModal');
            } catch (e) {
                console.error('Error editing subscription:', e);
                alert('Error loading subscription data. Please try again.');
            }
        }

        function deleteSubscriptionFromButton(button) {
            try {
                const id = button.getAttribute('data-sub-id');
                const email = button.getAttribute('data-sub-email');
                
                if (!id) {
                    alert('Subscription ID not found. Please refresh the page.');
                    return;
                }
                
                deleteSubscription(id, email);
            } catch (e) {
                console.error('Error getting delete data:', e);
                alert('Error loading delete form. Please try again.');
            }
        }
        
        function deleteSubscription(id, email) {
            try {
                const deleteForm = document.getElementById('deleteForm');
                const deleteSubId = document.getElementById('deleteSubId');
                const deleteEmail = document.getElementById('deleteEmail');
                
                if (!deleteForm || !deleteSubId || !deleteEmail) {
                    alert('Delete form not found. Please refresh the page.');
                    return;
                }
                
                deleteSubId.value = id;
                deleteEmail.textContent = email || 'this subscription';
                openModal('deleteModal');
            } catch (e) {
                console.error('Error deleting subscription:', e);
                alert('Error loading delete form. Please try again.');
            }
        }

        // Fix modal closing - only close when clicking the backdrop, not the modal content
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                // Only close if clicking the modal backdrop itself, not the modal-content
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }
        
        // Prevent modal from closing when clicking inside modal-content
        document.addEventListener('DOMContentLoaded', function() {
            const modalContents = document.querySelectorAll('.modal-content');
            modalContents.forEach(content => {
                content.addEventListener('click', function(event) {
                    event.stopPropagation(); // Prevent click from bubbling to modal backdrop
                });
            });
        });
    </script>
</body>
</html>

