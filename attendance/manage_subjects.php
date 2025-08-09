<?php
include("includes/db.php");
include("includes/header.php");

// Handle insert
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["subject_name"], $_POST["subject_code"], $_POST["credit"])) {
    $subject_name = $_POST["subject_name"];
    $subject_code = $_POST["subject_code"];
    $credit = $_POST["credit"];

    $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, credit) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $subject_name, $subject_code, $credit);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_subjects.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM subjects WHERE id = $id");
    header("Location: manage_subjects.php");
    exit();
}

// Handle update
if (isset($_POST['edit_id'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['edit_subject_name'];
    $code = $_POST['edit_subject_code'];
    $credit = $_POST['edit_credit'];

    $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, subject_code=?, credit=? WHERE id=?");
    $stmt->bind_param("ssii", $name, $code, $credit, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_subjects.php");
    exit();
}

$result = $conn->query("SELECT * FROM subjects");
?>

<div class="container mt-5">
    <h2 class="mb-4">Manage Subjects</h2>

    <!-- Add Subject Form -->
    <form method="POST" class="row g-3 border p-3 rounded bg-light mb-4">
        <div class="col-md-4">
            <label class="form-label">Subject Name:</label>
            <input type="text" name="subject_name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Subject Code:</label>
            <input type="text" name="subject_code" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Credit:</label>
            <input type="number" name="credit" class="form-control" required min="1">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Add Subject</button>
        </div>
    </form>

    <!-- Subject Table -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>S No</th>
                <th>Subject Name</th>
                <th>Sub Code</th>
                <th>Credit</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sno = 1;
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$sno}</td>
                        <td>{$row['subject_name']}</td>
                        <td>{$row['subject_code']}</td>
                        <td>{$row['credit']}</td>
                        <td>
                            <button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editModal{$row['id']}'>Edit</button>
                            <a href='manage_subjects.php?delete={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                        </td>
                      </tr>";

                // Edit Modal
                echo "
                <div class='modal fade' id='editModal{$row['id']}' tabindex='-1'>
                  <div class='modal-dialog'>
                    <form method='POST' class='modal-content'>
                      <div class='modal-header'>
                        <h5 class='modal-title'>Edit Subject</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                      </div>
                      <div class='modal-body'>
                        <input type='hidden' name='edit_id' value='{$row['id']}'>
                        <div class='mb-3'>
                          <label class='form-label'>Subject Name:</label>
                          <input type='text' name='edit_subject_name' class='form-control' value='{$row['subject_name']}' required>
                        </div>
                        <div class='mb-3'>
                          <label class='form-label'>Subject Code:</label>
                          <input type='text' name='edit_subject_code' class='form-control' value='{$row['subject_code']}' required>
                        </div>
                        <div class='mb-3'>
                          <label class='form-label'>Credit:</label>
                          <input type='number' name='edit_credit' class='form-control' value='{$row['credit']}' required>
                        </div>
                      </div>
                      <div class='modal-footer'>
                        <button type='submit' class='btn btn-success'>Save Changes</button>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                      </div>
                    </form>
                  </div>
                </div>";
                $sno++;
            }
            ?>
        </tbody>
    </table>
    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">← Back to Dashboard</a>
</div>

<!-- Bootstrap JS (if not already included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
