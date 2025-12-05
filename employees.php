<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $first_name = $conn->real_escape_string($_POST['first_name']);
                $last_name = $conn->real_escape_string($_POST['last_name']);
                $email = $conn->real_escape_string($_POST['email']);
                $phone = $conn->real_escape_string($_POST['phone'] ?? '');
                $position = $conn->real_escape_string($_POST['position']);
                $department_id = intval($_POST['department_id']);
                $is_supervisor = isset($_POST['is_supervisor']) ? 1 : 0;
                $is_head = isset($_POST['is_head_of_department']) ? 1 : 0;
                $hire_date = $conn->real_escape_string($_POST['hire_date'] ?? date('Y-m-d'));
                
                // Validate head of department
                if ($is_head && !canSetAsHead($department_id)) {
                    $message = "This department already has a head!";
                    $message_type = "error";
                } else {
                    $sql = "INSERT INTO employees (first_name, last_name, email, phone, position, department_id, is_supervisor, is_head_of_department, hire_date) 
                            VALUES ('$first_name', '$last_name', '$email', '$phone', '$position', $department_id, $is_supervisor, $is_head, '$hire_date')";
                    if ($conn->query($sql)) {
                        $message = "Employee created successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error: " . $conn->error;
                        $message_type = "error";
                    }
                }
                break;
                
            case 'update':
                $id = intval($_POST['employee_id']);
                $first_name = $conn->real_escape_string($_POST['first_name']);
                $last_name = $conn->real_escape_string($_POST['last_name']);
                $email = $conn->real_escape_string($_POST['email']);
                $phone = $conn->real_escape_string($_POST['phone'] ?? '');
                $position = $conn->real_escape_string($_POST['position']);
                $department_id = intval($_POST['department_id']);
                $is_supervisor = isset($_POST['is_supervisor']) ? 1 : 0;
                $is_head = isset($_POST['is_head_of_department']) ? 1 : 0;
                $hire_date = $conn->real_escape_string($_POST['hire_date'] ?? '');
                
                // Validate head of department
                if ($is_head && !canSetAsHead($department_id, $id)) {
                    $message = "This department already has a head!";
                    $message_type = "error";
                } else {
                    $sql = "UPDATE employees SET 
                            first_name='$first_name', 
                            last_name='$last_name', 
                            email='$email', 
                            phone='$phone', 
                            position='$position', 
                            department_id=$department_id, 
                            is_supervisor=$is_supervisor, 
                            is_head_of_department=$is_head,
                            hire_date='$hire_date'
                            WHERE employee_id=$id";
                    if ($conn->query($sql)) {
                        $message = "Employee updated successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error: " . $conn->error;
                        $message_type = "error";
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['employee_id']);
                $sql = "DELETE FROM employees WHERE employee_id=$id";
                if ($conn->query($sql)) {
                    $message = "Employee deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $message_type = "error";
                }
                break;
        }
    }
    closeDBConnection($conn);
}

// Get employees data
$conn = getDBConnection();
$employees = $conn->query("
    SELECT e.*, d.department_name
    FROM employees e
    JOIN departments d ON e.department_id = d.department_id
    ORDER BY e.last_name, e.first_name
");
$departments = getDepartments();
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Email Subscription System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="nav-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <span>Employees</span>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-users"></i> Manage Employees</h2>
                <div class="table-actions">
                    <button class="btn btn-primary" onclick="openModal('createModal')">
                        <i class="fas fa-plus"></i> Add Employee
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
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Hire Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($employees && $employees->num_rows > 0): ?>
                        <?php while ($row = $employees->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['employee_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['position']); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($row['department_name']); ?></span></td>
                                <td>
                                    <?php if ($row['is_head_of_department']): ?>
                                        <span class="badge badge-success">Head</span>
                                    <?php endif; ?>
                                    <?php if ($row['is_supervisor']): ?>
                                        <span class="badge badge-warning">Supervisor</span>
                                    <?php endif; ?>
                                    <?php if (!$row['is_head_of_department'] && !$row['is_supervisor']): ?>
                                        <span class="badge badge-secondary">Employee</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($row['hire_date']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteEmployee(<?php echo $row['employee_id']; ?>, '<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px;">
                                No employees found. Add your first employee!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-user-plus"></i> Add Employee</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST" id="employeeForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="employee_id" id="employee_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="hire_date">Hire Date</label>
                        <input type="date" id="hire_date" name="hire_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="position">Position *</label>
                    <input type="text" id="position" name="position" required>
                </div>
                
                <div class="form-group">
                    <label for="department_id">Department *</label>
                    <select id="department_id" name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>">
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_supervisor" name="is_supervisor">
                        <label for="is_supervisor">Supervisor</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_head_of_department" name="is_head_of_department">
                        <label for="is_head_of_department">Head of Department</label>
                    </div>
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
            <p>Are you sure you want to delete employee "<strong id="deleteEmpName"></strong>"?</p>
            <form method="POST" id="deleteForm" style="margin-top: 20px;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="employee_id" id="deleteEmpId">
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
                document.getElementById('employeeForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add Employee';
                document.getElementById('hire_date').value = '<?php echo date('Y-m-d'); ?>';
            }
        }

        function editEmployee(emp) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('employee_id').value = emp.employee_id;
            document.getElementById('first_name').value = emp.first_name;
            document.getElementById('last_name').value = emp.last_name;
            document.getElementById('email').value = emp.email;
            document.getElementById('phone').value = emp.phone || '';
            document.getElementById('position').value = emp.position;
            document.getElementById('department_id').value = emp.department_id;
            document.getElementById('hire_date').value = emp.hire_date || '<?php echo date('Y-m-d'); ?>';
            document.getElementById('is_supervisor').checked = emp.is_supervisor == 1;
            document.getElementById('is_head_of_department').checked = emp.is_head_of_department == 1;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Employee';
            openModal('createModal');
        }

        function deleteEmployee(id, name) {
            document.getElementById('deleteEmpId').value = id;
            document.getElementById('deleteEmpName').textContent = name;
            openModal('deleteModal');
        }

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

