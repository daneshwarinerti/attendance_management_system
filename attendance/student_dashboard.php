<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['user']['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            background-image: url('https://www.transparenttextures.com/patterns/cubes.png');
        }
        .profile-section {
            background: #2563eb;
            color: white;
            padding: 40px 20px;
            text-align: center;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
        }
        .profile-section h2 {
            font-weight: 600;
            margin-top: 10px;
        }
        .profile-section p {
            margin-bottom: 0;
            font-size: 1rem;
            opacity: 0.9;
        }
        .dashboard {
            margin-top: -30px;
            padding: 20px;
        }
        .dashboard-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease-in-out;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }
        .dashboard-card .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #2563eb;
        }
        .dashboard-card h5 {
            font-weight: 600;
            margin-bottom: 10px;
        }
        a.btn-custom {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 500;
            text-decoration: none;
            background-color: #2563eb;
            color: white;
            transition: background 0.3s ease;
        }
        a.btn-custom:hover {
            background-color: #1e40af;
        }
        .logout-btn {
            background-color: #ef4444;
        }
        .logout-btn:hover {
            background-color: #b91c1c;
        }
    </style>
</head>
<body>

<!-- Profile Header -->
<div class="profile-section">
    <div class="icon" style="font-size: 3rem;">🎓</div>
    <h2>Welcome, <?= htmlspecialchars($student_name) ?>!</h2>
    <p>Student Dashboard</p>
</div>

<!-- Dashboard Cards -->
<div class="container dashboard">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="icon">📅</div>
                <h5>View My Attendance</h5>
                <p>Check your attendance records and stay updated.</p>
                <a href="student_view_attendance.php" class="btn-custom">View Attendance</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="icon">🚪</div>
                <h5>Logout</h5>
                <p>Securely log out from your account.</p>
                <a href="logout.php" class="btn-custom logout-btn">Logout</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
