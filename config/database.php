<?php
/**
 * Database Configuration
 * Email List Subscription Project
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'email_subscription_db');

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        die("Database Error: " . $e->getMessage() . 
            "<br>Please make sure you have created the database and imported the schema.");
    }
}

// Close database connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Test database connection
function testConnection() {
    try {
        $conn = getDBConnection();
        closeDBConnection($conn);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>

