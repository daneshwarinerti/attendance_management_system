<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch all class-subject pairs assigned to this teacher
$query = "
    SELECT 
        cst.class_id,
        cst.subject_id,
        classes.class_name, 
        subjects.subject_name
    FROM class_subject_teacher cst
    JOIN classes ON cst.class_id = classes.id
    JOIN subjects ON cst.subject_id = subjects.id
    WHERE cst.teacher_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

$class_subjects = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['class_id'] . '_' . $row['subject_id'];
    $class_subjects[$key] = $row;
}

// Form handling
$students = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];
    $date = date('Y-m-d');

    if (isset($_POST['submit_attendance'])) {
        // Save attendance
        foreach ($_POST['attendance'] as $student_id => $status) {
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, subject_id, date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $student_id, $class_id, $subject_id, $date, $status);
            $stmt->execute();
        }
        echo "<script>alert('Attendance saved successfully!');</script>";
    } else {
        // Load students
        $stmt = $conn->prepare("SELECT * FROM students WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Attendance</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; padding: 20px; }
        h2 { color: #333; }
        form, table { background: white; padding: 20px; border-radius: 8px; }
        select, button { padding: 8px 12px; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: left; }
    </style>
</head>
<body>

<h2>Take Attendance</h2>

<form method="post">
    <label>Select Class and Subject:</label><br>
    <select name="class_id" required>
        <option value="">-- Select Class --</option>
        <?php foreach ($class_subjects as $key => $data): ?>
            <option value="<?= $data['class_id'] ?>" <?= (isset($class_id) && $class_id == $data['class_id']) ? 'selected' : '' ?>>
                <?= $data['class_name'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="subject_id" required>
        <option value="">-- Select Subject --</option>
        <?php foreach ($class_subjects as $key => $data): ?>
            <option value="<?= $data['subject_id'] ?>" <?= (isset($subject_id) && $subject_id == $data['subject_id']) ? 'selected' : '' ?>>
                <?= $data['subject_name'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if (empty($students)): ?>
        <br><br><button type="submit" name="load_students">Load Students</button>
    <?php endif; ?>

    <?php if (!empty($students)): ?>
        <table>
            <tr><th>S No</th><th>Student Name</th><th>USN</th><th>Status</th></tr>
            <?php foreach ($students as $i => $student): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($student['student_name']) ?></td>
                    <td><?= htmlspecialchars($student['usn']) ?></td>
                    <td>
                        <select name="attendance[<?= $student['id'] ?>]">
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                            <option value="Late">Late</option>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <input type="hidden" name="class_id" value="<?= $class_id ?>">
        <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
        <br><button type="submit" name="submit_attendance">Submit Attendance</button>
    <?php endif; ?>
</form>

</body>
</html>
