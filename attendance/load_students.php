<?php
include 'includes/db.php';

$class_id = $_GET['class_id'] ?? 0;

// Debug log (optional)
// echo "Class ID: $class_id";

// Fix: Check if connection is valid
if (!$conn) {
    die("Database connection failed.");
}

$sql = "SELECT id, student_name, usn FROM students WHERE class_id = ? ORDER BY usn";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Show detailed error message if query is invalid
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0): ?>
    <table>
        <tr><th>USN</th><th>Name</th><th>Status</th></tr>
        <?php while ($student = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $student['usn']; ?></td>
                <td><?php echo $student['student_name']; ?></td>
                <td>
                    <label><input type="radio" name="status[<?php echo $student['id']; ?>]" value="Present" checked> Present</label>
                    <label><input type="radio" name="status[<?php echo $student['id']; ?>]" value="Absent"> Absent</label>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>No students found for this class.</p>
<?php endif;
?>
