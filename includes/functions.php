<?php
/**
 * Common Functions
 * Email List Subscription Project
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get all departments for dropdown
 */
function getDepartments() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM departments ORDER BY department_name");
    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    closeDBConnection($conn);
    return $departments;
}

/**
 * Get department name by ID
 */
function getDepartmentName($id) {
    $conn = getDBConnection();
    $result = $conn->query("SELECT department_name FROM departments WHERE department_id = " . intval($id));
    $row = $result->fetch_assoc();
    closeDBConnection($conn);
    return $row ? $row['department_name'] : 'N/A';
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Check if department can have another head
 */
function canSetAsHead($department_id, $employee_id = null) {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as count FROM employees WHERE department_id = " . intval($department_id) . " AND is_head_of_department = TRUE";
    if ($employee_id) {
        $sql .= " AND employee_id != " . intval($employee_id);
    }
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    closeDBConnection($conn);
    return $row['count'] == 0;
}

/**
 * Format date for display
 */
function formatDate($date) {
    if (!$date) return 'N/A';
    return date('Y-m-d', strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime) {
    if (!$datetime) return 'N/A';
    return date('Y-m-d H:i:s', strtotime($datetime));
}
?>

