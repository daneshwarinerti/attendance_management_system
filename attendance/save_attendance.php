<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$class_id = $_POST['class_id'] ?? null;
$subject_id = $_POST['subject_id'] ?? null;
$attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
$status_data = $_POST['status'] ?? [];

if (!$class_id || !$subject_id || !$attendance_date || empty($status_data)) {
    die("❌ Error: Missing attendance data.");
}

foreach ($status_data as $student_id => $status) {
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, subject_id, date, status)
                            VALUES (?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE status = VALUES(status)");
    $stmt->bind_param("iiiss", $student_id, $class_id, $subject_id, $attendance_date, $status);
    $stmt->execute();
}

echo "<div style='text-align:center;margin-top:40px;font-size:18px;color:green;'>✅ Attendance saved successfully!</div>";
echo "<div style='text-align:center;margin-top:20px;'><a href='mark_attendance.php'>🔙 Back to Attendance Page</a></div>";
?>
