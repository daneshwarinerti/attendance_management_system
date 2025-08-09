<?php
include("includes/db.php");
include("includes/header.php");

// Fetch teachers and subjects
$teachers = $conn->query("SELECT * FROM teachers");
$subjects = $conn->query("SELECT * FROM subjects");

// Handle Add
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_class"])) {
    $class_name = $_POST["class_name"];
    $teacher_id = $_POST["teacher_id"];
    $subject_id = $_POST["subject_id"];

    $stmt = $conn->prepare("INSERT INTO classes (class_name, teacher_id, subject_id) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $class_name, $teacher_id, $subject_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_classes.php");
    exit;
}

// Handle Edit Request
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_query = $conn->prepare("SELECT * FROM classes WHERE id = ?");
    $edit_query->bind_param("i", $edit_id);
    $edit_query->execute();
    $edit_data = $edit_query->get_result()->fetch_assoc();
    $edit_query->close();
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_class"])) {
    $id = $_POST["class_id"];
    $class_name = $_POST["class_name"];
    $teacher_id = $_POST["teacher_id"];
    $subject_id = $_POST["subject_id"];

    $stmt = $conn->prepare("UPDATE classes SET class_name = ?, teacher_id = ?, subject_id = ? WHERE id = ?");
    $stmt->bind_param("siii", $class_name, $teacher_id, $subject_id, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_classes.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM classes WHERE id = $delete_id");
    header("Location: manage_classes.php");
    exit;
}

// Fetch all class mappings
$result = $conn->query("SELECT c.id, c.class_name, t.teacher_name, s.subject_name, c.teacher_id, c.subject_id 
                        FROM classes c 
                        LEFT JOIN teachers t ON c.teacher_id = t.id
                        LEFT JOIN subjects s ON c.subject_id = s.id
                        ORDER BY c.class_name");
?>

<div class="container mt-5">
    <h2 class="mb-4">Manage Classes</h2>

    <form method="POST" class="border p-4 rounded shadow-sm mb-5 bg-light">
        <input type="hidden" name="class_id" value="<?= $edit_data['id'] ?? '' ?>">

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Class Name</label>
                <input type="text" name="class_name" class="form-control" required value="<?= $edit_data['class_name'] ?? '' ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Select Teacher</label>
                <select name="teacher_id" class="form-select" required>
                    <option value="">-- Select Teacher --</option>
                    <?php
                    $teachers_res = $conn->query("SELECT * FROM teachers");
                    while ($teacher = $teachers_res->fetch_assoc()):
                        $selected = (isset($edit_data) && $edit_data['teacher_id'] == $teacher['id']) ? 'selected' : '';
                    ?>
                        <option value="<?= $teacher['id'] ?>" <?= $selected ?>><?= $teacher['teacher_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Select Subject</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">-- Select Subject --</option>
                    <?php
                    $subjects_res = $conn->query("SELECT * FROM subjects");
                    while ($subject = $subjects_res->fetch_assoc()):
                        $selected = (isset($edit_data) && $edit_data['subject_id'] == $subject['id']) ? 'selected' : '';
                    ?>
                        <option value="<?= $subject['id'] ?>" <?= $selected ?>><?= $subject['subject_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" name="<?= isset($edit_data) ? 'update_class' : 'add_class' ?>" class="btn btn-primary">
                <?= isset($edit_data) ? 'Update Class' : 'Add Class' ?>
            </button>
            <?php if (isset($edit_data)): ?>
                <a href="manage_classes.php" class="btn btn-secondary ms-2">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>S No</th>
                    <th>Class Name</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sno = 1;
                mysqli_data_seek($result, 0);
                while ($row = $result->fetch_assoc()):
                ?>
                    <tr <?= (isset($edit_data) && $edit_data['id'] == $row['id']) ? 'class="table-warning"' : '' ?>>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars($row['class_name']) ?></td>
                        <td><?= htmlspecialchars($row['subject_name']) ?></td>
                        <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                        <td>
                            <a href="manage_classes.php?edit_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="manage_classes.php?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this entry?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">← Back to Dashboard</a>
</div>

<?php include("includes/footer.php"); ?>
