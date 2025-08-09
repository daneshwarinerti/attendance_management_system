<?php
session_start();
include("includes/db.php");

// auth
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}
$teacher_id = (int)$_SESSION['user']['id'];

function is_valid_date($d) {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

/* Fetch class-subject mappings for this teacher */
$mappings = [];
$stmt = $conn->prepare("
    SELECT cst.id, classes.class_name, subjects.subject_name
    FROM class_subject_teacher cst
    JOIN classes ON cst.class_id = classes.id
    JOIN subjects ON cst.subject_id = subjects.id
    WHERE cst.teacher_id = ?
    ORDER BY classes.class_name, subjects.subject_name
");
if (!$stmt) {
    die("DB error (mappings prepare): " . $conn->error);
}
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $mappings[] = $r;
$stmt->close();

/* STATE */
$action = $_POST['action'] ?? '';
$map_id = isset($_POST['map_id']) ? (int)$_POST['map_id'] : (isset($_GET['map_id']) ? (int)$_GET['map_id'] : 0);
$date = $_POST['date'] ?? ($_GET['date'] ?? '');
$message = '';
$error = '';
$load_students = false;
$attendance_exists = false;
$students_result = null; // mysqli_result
$existing_attendance = []; // student_id => status
$class_id = 0;
$subject_id = 0;

/* Helper to validate and populate mapping/class/subject */
function validate_mapping($conn, $map_id, $teacher_id, &$class_id, &$subject_id, &$error) {
    if (!$map_id) { $error = "Please select a class & subject mapping."; return false; }
    $stmt = $conn->prepare("SELECT class_id, subject_id FROM class_subject_teacher WHERE id = ? AND teacher_id = ?");
    if (!$stmt) { $error = "DB error: " . $conn->error; return false; }
    $stmt->bind_param("ii", $map_id, $teacher_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) { $error = "Invalid mapping or not assigned to you."; $stmt->close(); return false; }
    $row = $res->fetch_assoc();
    $class_id = (int)$row['class_id'];
    $subject_id = (int)$row['subject_id'];
    $stmt->close();
    return true;
}

/* ACTION: Load students (teacher clicked 'Load Students') */
if ($action === 'load_students') {
    // map_id and date should be present in POST
    if (!is_valid_date($date)) {
        $error = "Invalid or missing date.";
    } else {
        if (validate_mapping($conn, $map_id, $teacher_id, $class_id, $subject_id, $error)) {
            // check if attendance exists
            $chk = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE class_id = ? AND subject_id = ? AND date = ?");
            if (!$chk) { $error = "DB error: " . $conn->error; }
            else {
                $chk->bind_param("iis", $class_id, $subject_id, $date);
                $chk->execute();
                $res = $chk->get_result();
                $row = $res->fetch_assoc();
                $attendance_exists = ($row['total'] > 0);
                $chk->close();

                // If attendance doesn't exist or teacher wants to edit later, we may load students accordingly:
                // For initial marking (no existing attendance) we load students to allow marking.
                // If attendance exists, we won't load students here (we'll show edit button instead).
                if (!$attendance_exists) {
                    $stmt = $conn->prepare("SELECT id, student_name, usn FROM students WHERE class_id = ? ORDER BY student_name");
                    if (!$stmt) { $error = "DB error: " . $conn->error; }
                    else {
                        $stmt->bind_param("i", $class_id);
                        $stmt->execute();
                        $students_result = $stmt->get_result();
                        $stmt->close();
                    }
                }
                $load_students = true;
            }
        }
    }
}

/* ACTION: Edit existing (teacher clicked Edit Attendance) */
if ($action === 'edit_existing') {
    if (!is_valid_date($date)) {
        $error = "Invalid or missing date.";
    } else {
        if (validate_mapping($conn, $map_id, $teacher_id, $class_id, $subject_id, $error)) {
            // load students
            $stmt = $conn->prepare("SELECT id, student_name, usn FROM students WHERE class_id = ? ORDER BY student_name");
            if (!$stmt) { $error = "DB error: " . $conn->error; }
            else {
                $stmt->bind_param("i", $class_id);
                $stmt->execute();
                $students_result = $stmt->get_result();
                $stmt->close();
            }
            // load existing attendance map
            $stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE class_id = ? AND subject_id = ? AND date = ?");
            if (!$stmt) { $error = "DB error: " . $conn->error; }
            else {
                $stmt->bind_param("iis", $class_id, $subject_id, $date);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) $existing_attendance[(int)$r['student_id']] = $r['status'];
                $stmt->close();
            }
            $load_students = true;
            // mark attendance_exists true so UI shows it's editing existing
            $attendance_exists = true;
        }
    }
}

/* ACTION: Save new attendance (insert) */
if ($action === 'save_new') {
    // required POST: class_id, subject_id, date, attendance[]
    if (!isset($_POST['class_id'], $_POST['subject_id'], $_POST['date'], $_POST['attendance'])) {
        $error = "Missing required fields to save attendance.";
    } else {
        $class_id = (int)$_POST['class_id'];
        $subject_id = (int)$_POST['subject_id'];
        $date = $_POST['date'];
        $attendance_array = $_POST['attendance']; // studentId => Present/Absent

        if (!is_valid_date($date)) $error = "Invalid date.";
        else {
            // validate mapping ownership
            if (!validate_mapping($conn, $map_id, $teacher_id, $class_id, $subject_id, $error)) {
                // error set by validate_mapping
            } else {
                // Insert in transaction
                $conn->begin_transaction();
                try {
                    $insert = $conn->prepare("INSERT INTO attendance (student_id, class_id, subject_id, date, status) VALUES (?, ?, ?, ?, ?)");
                    if (!$insert) throw new Exception("DB prepare error: " . $conn->error);

                    foreach ($attendance_array as $sid => $status) {
                        $sid_i = (int)$sid;
                        $status_s = ($status === 'Present') ? 'Present' : 'Absent';
                        $insert->bind_param("iiiss", $sid_i, $class_id, $subject_id, $date, $status_s);
                        $insert->execute();
                    }
                    $insert->close();
                    $conn->commit();
                    $message = "Attendance saved successfully.";
                    $attendance_exists = true;
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Failed to save attendance: " . $e->getMessage();
                }
            }
        }
    }
}

/* ACTION: Update existing attendance (edit) */
if ($action === 'update_existing') {
    if (!isset($_POST['class_id'], $_POST['subject_id'], $_POST['date'], $_POST['attendance'])) {
        $error = "Missing required fields to update attendance.";
    } else {
        $class_id = (int)$_POST['class_id'];
        $subject_id = (int)$_POST['subject_id'];
        $date = $_POST['date'];
        $attendance_array = $_POST['attendance'];

        if (!is_valid_date($date)) $error = "Invalid date.";
        else {
            if (!validate_mapping($conn, $map_id, $teacher_id, $class_id, $subject_id, $error)) {
                // error already set
            } else {
                $conn->begin_transaction();
                try {
                    // prepare check/update/insert statements
                    $check = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND class_id = ? AND subject_id = ? AND date = ?");
                    $update = $conn->prepare("UPDATE attendance SET status = ? WHERE id = ?");
                    $insert = $conn->prepare("INSERT INTO attendance (student_id, class_id, subject_id, date, status) VALUES (?, ?, ?, ?, ?)");

                    if (!$check || !$update || !$insert) {
                        throw new Exception("DB prepare failed: " . $conn->error);
                    }

                    foreach ($attendance_array as $sid => $status) {
                        $sid_i = (int)$sid;
                        $status_s = ($status === 'Present') ? 'Present' : 'Absent';

                        // check existing
                        $check->bind_param("iiis", $sid_i, $class_id, $subject_id, $date);
                        $check->execute();
                        $cres = $check->get_result();
                        if ($row = $cres->fetch_assoc()) {
                            $att_id = (int)$row['id'];
                            $update->bind_param("si", $status_s, $att_id);
                            $update->execute();
                        } else {
                            $insert->bind_param("iiiss", $sid_i, $class_id, $subject_id, $date, $status_s);
                            $insert->execute();
                        }
                    }

                    $check->close();
                    $update->close();
                    $insert->close();
                    $conn->commit();
                    $message = "Attendance updated successfully.";
                    $attendance_exists = true;
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Failed to update attendance: " . $e->getMessage();
                }
            }
        }
    }
}

/* If user just visited with GET map_id & date (after save or via redirect), allow showing edit button if attendance exists */
if (!$load_students && $map_id && $date && is_valid_date($date)) {
    if (validate_mapping($conn, $map_id, $teacher_id, $class_id, $subject_id, $err)) {
        $chk = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE class_id = ? AND subject_id = ? AND date = ?");
        if ($chk) {
            $chk->bind_param("iis", $class_id, $subject_id, $date);
            $chk->execute();
            $cres = $chk->get_result();
            $crow = $cres->fetch_assoc();
            $attendance_exists = ($crow['total'] > 0);
            $chk->close();
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Mark Attendance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; background:#f4f6f9; padding:20px; }
    .card { max-width:1100px; margin:24px auto; border-radius:12px; padding:22px; box-shadow:0 6px 20px rgba(0,0,0,0.06); background:#fff; }
    .btn-status { padding:8px 14px; border-radius:8px; border:2px solid; font-weight:600; cursor:pointer; min-width:110px; }
    .present-outline { border-color:#198754; color:#198754; background:transparent; }
    .present-filled { border-color:#198754; background:#198754; color:#fff; }
    .absent-outline { border-color:#dc3545; color:#dc3545; background:transparent; }
    .absent-filled { border-color:#dc3545; background:#dc3545; color:#fff; }
    .message { padding:10px 14px; border-radius:8px; margin-bottom:14px; }
    .success { background:#d1e7dd; color:#0f5132; }
    .error { background:#f8d7da; color:#842029; }
    .note { font-size:0.95rem; color:#495057; }
    @media (max-width:576px) {
        .btn-status { min-width:80px; padding:6px 10px; font-size:0.9rem; }
        select, input[type="date"] { width:100%; margin-bottom:10px; }
    }
</style>
</head>
<body>
<div class="card">
    <h3 class="mb-3 text-center">Mark Attendance</h3>

    <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Selection form -->
    <form method="post" class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label">Class & Subject</label>
            <select name="map_id" class="form-select" required>
                <option value="">-- Select Class & Subject --</option>
                <?php foreach ($mappings as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= ($map_id && $map_id == $m['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['class_name'] . ' - ' . $m['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date ?: date('Y-m-d')) ?>" required>
        </div>

        <div class="col-md-3">
            <button type="submit" name="action" value="load_students" class="btn btn-primary w-100">Load Students</button>
        </div>
    </form>

    <?php if ($load_students): ?>
        <hr class="my-4">
        <?php if ($attendance_exists && $action !== 'edit_existing'): ?>
            <!-- Attendance exists & teacher hasn't clicked Edit -->
            <div class="message note">Attendance has already been submitted for this class and date.</div>
            <form method="post">
                <input type="hidden" name="map_id" value="<?= (int)$map_id ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                <button type="submit" name="action" value="edit_existing" class="btn btn-outline-primary">✏️ Edit Attendance</button>
            </form>

        <?php elseif ($students_result && $students_result->num_rows > 0): ?>
            <!-- Display the table (either initial marking or editing with prefilled data) -->
            <form method="post" id="attendanceForm">
                <input type="hidden" name="class_id" value="<?= (int)$class_id ?>">
                <input type="hidden" name="subject_id" value="<?= (int)$subject_id ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                <input type="hidden" name="map_id" value="<?= (int)$map_id ?>">
                <input type="hidden" name="action" value="<?= ($attendance_exists ? 'update_existing' : 'save_new') ?>">

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-secondary">
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>USN</th>
                                <th>Attendance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1;
                            // iterate students_result
                            while ($stu = $students_result->fetch_assoc()):
                                $sid = (int)$stu['id'];
                                // Determine initial status:
                                // - if we have an existing_attendance value, use it
                                // - otherwise empty string (outline by default)
                                $initial = isset($existing_attendance[$sid]) ? $existing_attendance[$sid] : '';
                                $presentClass = ($initial === 'Present') ? 'present-filled' : 'present-outline';
                                $absentClass  = ($initial === 'Absent')  ? 'absent-filled'  : 'absent-outline';
                            ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($stu['student_name']) ?></td>
                                    <td><?= htmlspecialchars($stu['usn']) ?></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button"
                                                    id="present-<?= $sid ?>"
                                                    class="btn-status <?= $presentClass ?>"
                                                    onclick="setStatus(<?= $sid ?>, 'Present')">
                                                Present
                                            </button>

                                            <button type="button"
                                                    id="absent-<?= $sid ?>"
                                                    class="btn-status <?= $absentClass ?>"
                                                    onclick="setStatus(<?= $sid ?>, 'Absent')">
                                                Absent
                                            </button>
                                        </div>

                                        <input type="hidden" name="attendance[<?= $sid ?>]" id="status-<?= $sid ?>" value="<?= htmlspecialchars($initial) ?>">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" id="saveBtn" class="btn btn-success btn-lg" disabled>
                        <?= $attendance_exists ? 'Update Attendance' : 'Save Attendance' ?>
                    </button>
                </div>
            </form>

            <script>
            function setStatus(studentId, status) {
                const presentBtn = document.getElementById('present-' + studentId);
                const absentBtn = document.getElementById('absent-' + studentId);
                const hidden = document.getElementById('status-' + studentId);

                if (status === 'Present') {
                    presentBtn.classList.remove('present-outline'); presentBtn.classList.add('present-filled');
                    absentBtn.classList.remove('absent-filled'); absentBtn.classList.add('absent-outline');
                } else {
                    absentBtn.classList.remove('absent-outline'); absentBtn.classList.add('absent-filled');
                    presentBtn.classList.remove('present-filled'); presentBtn.classList.add('present-outline');
                }
                hidden.value = status;
                checkAllSelected();
            }

            function checkAllSelected() {
                const hiddenInputs = document.querySelectorAll('[id^="status-"]');
                let allSelected = true;
                hiddenInputs.forEach(inp => {
                    if (inp.value === '') allSelected = false;
                });
                document.getElementById('saveBtn').disabled = !allSelected;
            }

            // On load, if some statuses are prefilled (editing), enable the save button.
            document.addEventListener('DOMContentLoaded', function() {
                checkAllSelected();
            });
            </script>

        <?php else: ?>
            <div class="message error">No students found for the selected class.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
