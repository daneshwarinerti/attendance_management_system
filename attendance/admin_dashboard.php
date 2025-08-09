<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if user is not logged in or not an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION['user']['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f3f9ff;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #004aad;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 26px;
            font-weight: bold;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }

        h1 {
            color: #004aad;
            margin-bottom: 10px;
        }

        p {
            font-size: 16px;
            color: #333;
        }

        .menu {
            margin-top: 30px;
        }

        .button {
            display: block;
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            background-color: #004aad;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 18px;
            transition: background 0.3s ease;
        }

        .button:hover {
            background-color: #003080;
        }

        .logout {
            background-color: #c0392b;
        }

        .logout:hover {
            background-color: #922b21;
        }
    </style>
</head>
<body>

<header>Admin Dashboard - Attendance Management System</header>

<div class="container">
    <h1>Welcome, <?php echo htmlspecialchars($adminName); ?>!</h1>
    <p>You are logged in as <strong>Admin</strong>.</p>

    <div class="menu">
        <a href="manage_teachers.php" class="button">Manage Teachers</a>
        <a href="manage_students.php" class="button">Manage Students</a>
        <a href="manage_classes.php" class="button">Manage Classes</a>
        <a href="manage_subjects.php" class="button">Manage Subjects</a>
        <a href="view_attendance.php" class="button">View Attendance</a>
        <a href="logout.php" class="button logout">Logout</a>
    </div>
</div>

</body>
</html>

