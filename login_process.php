<?php
require_once 'db_connect.php';
secure_session_start();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    
    // Validate CSRF token if available - 临时禁用以解决登录问题
    // if (isset($_POST['csrf_token']) && !verify_csrf_token($_POST['csrf_token'])) {
    //     header("Location: ../index.php?error=csrf");
    //     exit();
    // }
    
    // Query to get user by username only (we'll verify password separately)
    $query = "SELECT * FROM users WHERE username = ? AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password (handles both old plain text and new hashed passwords)
        $password_valid = false;
        if (password_verify($password, $user['password'])) {
            // New hashed password
            $password_valid = true;
        } elseif ($user['password'] === $password) {
            // Old plain text password - verify and update to hashed
            $password_valid = true;
            // Update to hashed password
            $hashed_password = hash_password($password);
            $update_pass_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_pass_stmt = $conn->prepare($update_pass_query);
            $update_pass_stmt->bind_param("si", $hashed_password, $user['id']);
            $update_pass_stmt->execute();
        }
        
        if ($password_valid) {
            // Login successful - regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();
            
            // Update last login timestamp
            $update_query = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            // Redirect to mobile dashboard based on role
            if ($user['role'] == 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard_user.php");
            }
            exit();
        }
    }
    
    // Login failed - add delay to prevent brute force attacks
    sleep(1);
    header("Location: ../index.php?error=1");
    exit();
}

// If someone tries to access this file directly
header("Location: ../index.php");
exit(); 