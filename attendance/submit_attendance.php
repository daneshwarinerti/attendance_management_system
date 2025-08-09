<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

if (!isset($_POST['class_id'], $_POST['subject_id'], $_POST['date'], $_POST['attendance'], $_POST['map_id'])) {
    die("Required data missing.");
}

$class_id = (int) $_POST['class_id'];
$subject_id = (int) $_POST['subject_id'];
$date = $_POST['date'];
$attendance = $_POST['attendance']; // array studentId => 'Present'/'Absent'
$map_id = (int) $_POST['map_id'];
$teacher_id = (int) $_SESSION['user']['id'];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    die("Invalid date format.");
}

// verify mapping belongs to teacher
$stmt = $conn->prepare("SELECT id FROM class_subject_teacher WHERE id = ? AND class_id = ? AND subject_id = ? AND teacher_id = ?");
if (!$stmt) die("DB error: " . $conn->error);
$stmt->bind_param("iiii", $map_id, $class_id, $subject_id, $teacher_id);
$stmt->execute();
$mapRes = $stmt->get_result();
if ($mapRes->num_rows === 0) {
    $stmt->close();
    die("Invalid mapping or not authorized.");
}
$stmt->close();

// validate attendance array: ensure all values are set and valid
foreach ($attendance as $sid => $val) {
    if ($val !== 'Present' && $val !== 'Absent') {
        die("Invalid attendance value for student {$sid}.");
    }
}

// transaction
$conn->begin_transaction();

try {
    $checkStmt = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND class_id = ? AND subject_id = ? AND date = ?");
    $updateStmt = $conn->prepare("UPDATE attendance SET status = ? WHERE id = ?");
    $insertStmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, subject_id, date, status) VALUES (?, ?, ?, ?, ?)");

    if (!$checkStmt || !$updateStmt || !$insertStmt) {
        throw new Exception("DB prepare failed: " . $conn->error);
    }

    foreach ($attendance as $studentId => $status) {
        $studentId = (int)$studentId;
        $status = ($status === 'Present') ? 'Present' : 'Absent';

        // check
        $checkStmt->bind_param("iiis", $studentId, $class_id, $subject_id, $date);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($row = $checkRes->fetch_assoc()) {
            $existingId = (int)$row['id'];
            $updateStmt->bind_param("si", $status, $existingId);
            $updateStmt->execute();
        } else {
            $insertStmt->bind_param("iiiss", $studentId, $class_id, $subject_id, $date, $status);
            $insertStmt->execute();
        }
    }

    $conn->commit();

    $checkStmt->close();
    $updateStmt->close();
    $insertStmt->close();

    // redirect back to mark_attendance showing saved values
    header("Location: mark_attendance.php?map_id={$map_id}&date={$date}&saved=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    die("Failed to save attendance: " . $e->getMessage());
}
