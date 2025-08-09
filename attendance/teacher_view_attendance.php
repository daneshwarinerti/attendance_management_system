<?php
session_start();
include("includes/db.php");

// Only allow teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['user']['id'];
$selected_class = $_POST['class_id'] ?? '';
$selected_subject = $_POST['subject_id'] ?? '';
$selected_date = $_POST['attendance_date'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Attendance - Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f8;
            font-family: 'Segoe UI', sans-serif;
            padding-top: 50px;
        }
        .card {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border-radius: 1rem;
            box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.1);
        }
        table {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h3 class="text-center mb-4">View Attendance</h3>
        <form method="post" class="row g-3">
            <!-- Class Dropdown -->
            <div class="col-md-4">
                <label for="class_id" class="form-label fw-semibold">Class</label>
                <select name="class_id" id="class_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php
                    $class_query = "SELECT DISTINCT c.id, c.class_name 
                                    FROM classes c 
                                    JOIN class_subject_teacher cst ON c.id = cst.class_id 
                                    WHERE cst.teacher_id = ?";
                    $stmt = $conn->prepare($class_query);
                    $stmt->bind_param("i", $teacher_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $selected = ($row['id'] == $selected_class) ? 'selected' : '';
                        echo "<option value='{$row['id']}' $selected>{$row['class_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Subject Dropdown -->
            <div class="col-md-4">
                <label for="subject_id" class="form-label fw-semibold">Subject</label>
                <select name="subject_id" id="subject_id" class="form-select" required>
                    <option value="">-- Select Subject --</option>
                    <?php
                    if (!empty($selected_class)) {
                        $subject_query = "SELECT DISTINCT s.id, s.subject_name 
                                          FROM subjects s 
                                          JOIN class_subject_teacher cst ON s.id = cst.subject_id 
                                          WHERE cst.teacher_id = ? AND cst.class_id = ?";
                        $stmt = $conn->prepare($subject_query);
                        $stmt->bind_param("ii", $teacher_id, $selected_class);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $selected = ($row['id'] == $selected_subject) ? 'selected' : '';
                            echo "<option value='{$row['id']}' $selected>{$row['subject_name']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Date Picker -->
            <div class="col-md-4">
                <label for="attendance_date" class="form-label fw-semibold">Date</label>
                <input type="date" name="attendance_date" id="attendance_date" class="form-control" required value="<?= $selected_date ?>">
            </div>

            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary mt-2">View Attendance</button>
            </div>
        </form>

        <!-- Display Attendance -->
        <?php
        if (!empty($selected_class) && !empty($selected_subject) && !empty($selected_date)) {
            $attendance_query = "SELECT s.student_name, s.usn, a.status 
                                 FROM attendance a 
                                 JOIN students s ON a.student_id = s.id 
                                 WHERE a.class_id = ? AND a.subject_id = ? AND a.date = ?";
            $stmt = $conn->prepare($attendance_query);
            $stmt->bind_param("iis", $selected_class, $selected_subject, $selected_date);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<table class='table table-striped table-bordered mt-4'>";
                echo "<thead><tr><th>Name</th><th>USN</th><th>Status</th></tr></thead><tbody>";
                while ($row = $result->fetch_assoc()) {
                    $status_badge = $row['status'] === 'present'
                        ? "<span class='badge bg-success'>Present</span>"
                        : "<span class='badge bg-danger'>Absent</span>";
                    echo "<tr>
                            <td>{$row['student_name']}</td>
                            <td>{$row['usn']}</td>
                            <td>$status_badge</td>
                          </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<div class='alert alert-warning mt-4'>No attendance data found for the selected options.</div>";
            }
        }
        ?>
    </div>
</div>
 <div class="text-center mt-4">
            <a href="student_dashboard.php" class="btn btn-secondary">🔙 Back to Dashboard</a>
        </div>
</body>
</html>
