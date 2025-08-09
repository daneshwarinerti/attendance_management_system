<?php
include("includes/db.php");
include("includes/header.php");

// Add Student
if (isset($_POST['add_student'])) {
    $name = $_POST['student_name'];
    $usn = $_POST['usn'];
    $class_id = $_POST['class_id'];
    $stmt = $conn->prepare("INSERT INTO students (student_name, usn, class_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $usn, $class_id);
    $stmt->execute();
    header("Location: manage_students.php");
    exit;
}

// Edit Student
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM students WHERE id = $edit_id";
    $edit_result = $conn->query($edit_query);
    $edit_row = $edit_result->fetch_assoc();
}

// Update Student
if (isset($_POST['update_student'])) {
    $id = $_POST['student_id'];
    $name = $_POST['student_name'];
    $usn = $_POST['usn'];
    $class_id = $_POST['class_id'];
    $stmt = $conn->prepare("UPDATE students SET student_name = ?, usn = ?, class_id = ? WHERE id = ?");
    $stmt->bind_param("ssii", $name, $usn, $class_id, $id);
    $stmt->execute();
    header("Location: manage_students.php");
    exit;
}

// Delete Student
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $conn->query("DELETE FROM students WHERE id = $delete_id");
    header("Location: manage_students.php");
    exit;
}

// Fetch Classes for Dropdown
$class_query = "SELECT MIN(id) as id, class_name FROM classes GROUP BY class_name";

$class_result = $conn->query($class_query);

// Fetch Students for Table
$query = "SELECT students.id, student_name, usn, classes.class_name 
          FROM students 
          JOIN classes ON students.class_id = classes.id 
          ORDER BY students.id ASC";
$result = $conn->query($query);
?>

<div class="container mt-4">
    <h2 class="mb-4">Manage Students</h2>

    <form method="post" class="mb-4">
        <?php if (isset($_GET['edit'])): ?>
            <input type="hidden" name="student_id" value="<?= $edit_row['id'] ?>">
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" name="student_name" class="form-control" placeholder="Name" required value="<?= isset($edit_row) ? $edit_row['student_name'] : '' ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="usn" class="form-control" placeholder="USN" required value="<?= isset($edit_row) ? $edit_row['usn'] : '' ?>">
            </div>
            <div class="col-md-3">
                <select name="class_id" class="form-select" required>
                    <option value="">Select Class</option>
                    <?php while ($row = $class_result->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= (isset($edit_row) && $edit_row['class_id'] == $row['id']) ? 'selected' : '' ?>>
                            <?= $row['class_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" name="<?= isset($_GET['edit']) ? 'update_student' : 'add_student' ?>" class="btn btn-primary w-100">
                    <?= isset($_GET['edit']) ? 'Update' : 'Add Student' ?>
                </button>
            </div>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>S.No</th>
                <th>Name</th>
                <th>USN</th>
                <th>Class</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['usn']) ?></td>
                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                    <td>
                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-secondary mt-3">← Back to Dashboard</a>
</div>

<?php include("includes/footer.php"); ?>
