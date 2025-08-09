<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    echo "<option value=''>Unauthorized</option>";
    exit();
}

include 'includes/db.php';

$teacher_id = $_SESSION['user']['id'];

if (!isset($_POST['class_id']) || empty($_POST['class_id'])) {
    echo "<option value=''>Invalid Class</option>";
    exit();
}

$class_id = $_POST['class_id'];

$stmt = $conn->prepare("SELECT s.id, s.subject_name FROM subjects s 
                        JOIN class_subject_teacher cst ON s.id = cst.subject_id 
                        WHERE cst.class_id = ? AND cst.teacher_id = ?");
$stmt->bind_param("ii", $class_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<option value=''>-- Select Subject --</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['id']}'>{$row['name']}</option>";
    }
} else {
    echo "<option value=''>No subjects found</option>";
}
?>
