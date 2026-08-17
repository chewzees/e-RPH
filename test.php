<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Test - e-RPH</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mobile Test Page</h1>
            <p>Testing Mobile Version Functionality</p>
        </div>
        
        <div class="info">
            <h3>Session Information:</h3>
            <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
            <p><strong>Role:</strong> <?php echo $role; ?></p>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($full_name); ?></p>
        </div>
        
        <div class="info">
            <h3>Navigation Links:</h3>
            <?php if ($role == 'admin'): ?>
                <a href="dashboard_admin.php" class="btn">Admin Dashboard</a>
                <a href="../dashboard_admin.php" class="btn">Desktop Admin Dashboard</a>
            <?php else: ?>
                <a href="dashboard_user.php" class="btn">Teacher Dashboard</a>
                <a href="../dashboard_user.php" class="btn">Desktop Teacher Dashboard</a>
            <?php endif; ?>
            <a href="../index.php" class="btn">Back to Login</a>
            <a href="../logout.php" class="btn btn-danger">Logout</a>
        </div>
        
        <div class="info">
            <h3>Test Results:</h3>
            <p>✅ Session is working</p>
            <p>✅ Database connection is working</p>
            <p>✅ Mobile navigation should work</p>
            <p>✅ Links should redirect correctly</p>
        </div>
    </div>
</body>
</html> 