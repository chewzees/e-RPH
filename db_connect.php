<?php
// Database configuration
$db_host = "localhost";     // Database host (usually localhost)
$db_user = "root";         // Database username (default: root for XAMPP)
$db_pass = "";     // Database password (default: blank for XAMPP)
$db_name = "erph";      // Database name

// Create database connection
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Log the error (in a production environment, you'd want to log this more securely)
    error_log("Database connection error: " . $e->getMessage());
    
    // Show user-friendly error message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}

// Function to clean input data
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Secure password hashing functions
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hashed_password) {
    return password_verify($password, $hashed_password);
}

// Enhanced session security
function secure_session_start() {
    // Regenerate session ID to prevent session fixation
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        session_regenerate_id(true);
    }
}

// Password validation function - 完全自由的密码要求
function validate_password($password) {
    $errors = [];
    
    // 只检查密码不为空
    if (empty($password) || strlen($password) < 1) {
        $errors[] = "Password cannot be empty";
    }
    
    // 完全自由 - 没有其他限制
    
    return $errors;
}

// CSRF token functions for additional security
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to close database connection
function close_connection() {
    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('close_connection');
?> 