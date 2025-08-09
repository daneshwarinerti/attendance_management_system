<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-body text-center">
                <h2 class="mb-4">Welcome, <?php echo $_SESSION['user']['name']; ?></h2>
                <a href="mark_attendance.php" class="btn btn-primary m-2">Mark Attendance</a>
                <a href="teacher_view_attendance.php" class="btn btn-success m-2">View Attendance</a>
                <a href="logout.php" class="btn btn-outline-danger m-2">Logout</a>
                
            </div>
       
        </div>
    </div>
   
    
</body>
</html>
