<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$studentId = $_SESSION['student_id'];

$stmt = $conn->prepare("
    SELECT a.date, a.status, c.class_name 
    FROM attendance a
    JOIN classes c ON a.class_id = c.id
    WHERE a.student_id = ?
    ORDER BY a.date DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #e3f2fd, #f1f8e9);
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            margin-top: 60px;
        }
        .card {
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .table th {
            background-color: #4a90e2;
            color: white;
        }
        .status-present {
            color: green;
            font-weight: 600;
        }
        .status-absent {
            color: red;
            font-weight: 600;
        }
        .status-leave {
            color: orange;
            font-weight: 600;
        }
        .back-link {
            text-decoration: none;
            font-weight: 500;
            color: #4a90e2;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h3 class="text-center mb-4">📅 My Attendance</h3>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="text-center">
                    <tr>
                        <th>Date</th>
                        <th>Class</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="text-center">
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['class_name']) ?></td>
                            <td class="<?php
                                $statusClass = strtolower($row['status']);
                                echo 'status-' . $statusClass;
                            ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <a href="student_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>




