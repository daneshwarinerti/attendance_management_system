<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get session values
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
    <p>You are logged in as <strong><?php echo htmlspecialchars($userRole); ?></strong>.</p>

    <h3>Menu</h3>
    <ul>
        <?php if ($userRole === 'admin'): ?>
            <li><a href="manage_teachers.php">Manage Teachers</a></li>
            <li><a href="manage_students.php">Manage Students</a></li>
            <li><a href="manage_classes.php">Manage Classes</a></li>
            <li><a href="manage_subjects.php">Manage Subjects</a></li>
            <li><a href="view_attendance.php">View Attendance</a></li>
        <?php elseif ($userRole === 'teacher'): ?>
            <li><a href="mark_attendance.php">Mark Attendance</a></li>
            <li><a href="view_attendance.php">View Attendance</a></li>
        <?php else: ?>
            <li><a href="view_my_attendance.php">View My Attendance</a></li>
        <?php endif; ?>
    </ul>

    <p><a href="logout.php">Logout</a></p>
</body>
</html>



