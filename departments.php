<?php
require_once 'config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $name = $conn->real_escape_string($_POST['department_name']);
                $description = $conn->real_escape_string($_POST['description'] ?? '');
                $sql = "INSERT INTO departments (department_name, description) VALUES ('$name', '$description')";
                if ($conn->query($sql)) {
                    $message = "Department created successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $message_type = "error";
                }
                break;
                
            case 'update':
                $id = intval($_POST['department_id']);
                $name = $conn->real_escape_string($_POST['department_name']);
                $description = $conn->real_escape_string($_POST['description'] ?? '');
                $sql = "UPDATE departments SET department_name='$name', description='$description' WHERE department_id=$id";
                if ($conn->query($sql)) {
                    $message = "Department updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                $id = intval($_POST['department_id']);
                // Check if department has employees
                $check = $conn->query("SELECT COUNT(*) as count FROM employees WHERE department_id=$id");
                $result = $check->fetch_assoc();
                if ($result['count'] > 0) {
                    $message = "Cannot delete department with assigned employees!";
                    $message_type = "error";
                } else {
                    $sql = "DELETE FROM departments WHERE department_id=$id";
                    if ($conn->query($sql)) {
                        $message = "Department deleted successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error: " . $conn->error;
                        $message_type = "error";
                    }
                }
                break;
        }
    }
    closeDBConnection($conn);
}

// Get departments data
$conn = getDBConnection();
$departments = $conn->query("
    SELECT
      d.*,
      COUNT(e.employee_id) AS employee_count,
      SUM(CASE WHEN e.is_supervisor = 1 THEN 1 ELSE 0 END) AS supervisors_count,
      h.employee_id AS head_id,
      CONCAT(h.first_name, ' ', h.last_name) AS head_name
    FROM departments d
    LEFT JOIN employees e ON d.department_id = e.department_id
    LEFT JOIN employees h ON d.head_employee_id = h.employee_id
    GROUP BY d.department_id
    ORDER BY d.department_name
");
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments - Email Subscription System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="nav-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <span>Departments</span>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-building"></i> Manage Departments</h2>
                <div class="table-actions">
                    <button class="btn btn-primary" onclick="openModal('createModal')">
                        <i class="fas fa-plus"></i> Add Department
                    </button>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Department Name</th>
                        <th>Description</th>
                        <th>Employees</th>
                        <th>Supervisors</th>
                        <th>Head</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($departments && $departments->num_rows > 0): ?>
                        <?php while ($row = $departments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['department_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['department_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['description'] ?? 'N/A'); ?></td>
                                <td><span class="badge badge-info"><?php echo $row['employee_count']; ?></span></td>
                                <td><span class="badge badge-warning"><?php echo $row['supervisors_count']; ?></span></td>
                                <td>
                                    <?php if (!empty($row['head_id'])): ?>
                                        <span class="badge badge-success"><?php echo htmlspecialchars($row['head_name']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="editDepartment(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDepartment(<?php echo $row['department_id']; ?>, '<?php echo htmlspecialchars($row['department_name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px;">
                                No departments found. Create your first department!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-building"></i> Add Department</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST" id="departmentForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="department_id" id="department_id">
                
                <div class="form-group">
                    <label for="department_name">Department Name *</label>
                    <input type="text" id="department_name" name="department_name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <!-- Optional: choose head employee (if you want) -->
                <!--
                <div class="form-group">
                    <label for="head_employee_id">Head (Employee ID)</label>
                    <input type="number" id="head_employee_id" name="head_employee_id" placeholder="Employee ID (optional)">
                </div>
                -->
                
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
            <p>Are you sure you want to delete department "<strong id="deleteDeptName"></strong>"?</p>
            <p style="color: var(--danger-color); margin-top: 10px;"><small>This action cannot be undone. Make sure the department has no employees.</small></p>
            <form method="POST" id="deleteForm" style="margin-top: 20px;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="department_id" id="deleteDeptId">
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
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            if (modalId === 'createModal') {
                document.getElementById('departmentForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-building"></i> Add Department';
            }
        }

        function editDepartment(dept) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('department_id').value = dept.department_id;
            document.getElementById('department_name').value = dept.department_name;
            document.getElementById('description').value = dept.description || '';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Department';
            openModal('createModal');
        }

        function deleteDepartment(id, name) {
            document.getElementById('deleteDeptId').value = id;
            document.getElementById('deleteDeptName').textContent = name;
            openModal('deleteModal');
        }

        // Close modal on outside click
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>
