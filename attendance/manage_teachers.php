<?php
session_start();
if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require_once "includes/db.php";

$message = '';
$editing = false;
$editId = 0;
$editName = '';
$editEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $pwd = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'teacher')");
        $stmt->bind_param("sss", $name, $email, $pwd);
        $message = $stmt->execute() ? "Teacher added." : "Error: " . $conn->error;
        $stmt->close();

    } elseif (isset($_POST['update'])) {
        $editId = intval($_POST['edit_id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=? AND role='teacher'");
        $stmt->bind_param("ssi", $name, $email, $editId);
        $message = $stmt->execute() ? "Updated." : "Error: " . $conn->error;
        $stmt->close();
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id=$id AND role='teacher'");
    header("Location: manage_teachers.php");
    exit;
}

// Load edit form
if (isset($_GET['edit'])) {
    $editing = true;
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT name,email FROM users WHERE id=? AND role='teacher'");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $editName = $row['name'];
    $editEmail = $row['email'];
    $stmt->close();
}

// Fetch list
$teachers = $conn->query("SELECT id, name, email FROM users WHERE role='teacher'");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Teachers</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .card { margin: 20px auto; max-width: 800px; background: white; padding: 20px; border-radius: 8px; }
    h2 { color: #004aad; }
    form > div { margin-bottom: 15px; }
    input { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
    button { padding: 10px 20px; background: #004aad; color: white; border: none; border-radius: 5px; cursor: pointer; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
    th { background: #004aad; color: white; }
    .btn { padding: 6px 12px; color: white; border-radius: 4px; text-decoration: none; }
    .btn.edit { background: #28a745; }
    .btn.del { background: #c0392b; }
    .btn:hover { opacity: 0.9; }
    .message { color: green; margin-top: 10px; }
    .back { margin-top: 20px; display: inline-block; }
  </style>
</head>
<body>

<header>Attendance System</header>
<div class="card">
  <h2>Manage Teachers</h2>
  <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>

  <form method="POST">
    <h3><?= $editing ? 'Edit Teacher' : 'Add New Teacher' ?></h3>
    <input type="hidden" name="edit_id" value="<?= $editId ?>">
    <div><label>Name</label><input type="text" name="name" required value="<?= htmlspecialchars($editing ? $editName : '') ?>"></div>
    <div><label>Email</label><input type="email" name="email" required value="<?= htmlspecialchars($editing ? $editEmail : '') ?>"></div>
    <?php if (!$editing): ?>
      <div><label>Password</label><input type="password" name="password" required></div>
    <?php endif; ?>
    <button name="<?= $editing ? 'update' : 'add' ?>"><?= $editing ? 'Update' : 'Add Teacher' ?></button>
    <?php if ($editing): ?><a href="manage_teachers.php">Cancel</a><?php endif; ?>
  </form>

  <h3>Existing Teachers</h3>
  <table>
    <tr><th>Name</th><th>Email</th><th>Actions</th></tr>
    <?php while ($t = $teachers->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($t['name']) ?></td>
        <td><?= htmlspecialchars($t['email']) ?></td>
        <td>
          <a class="btn edit" href="?edit=<?= $t['id'] ?>">Edit</a>
          <a class="btn del" href="?delete=<?= $t['id'] ?>" onclick="return confirm('Delete this teacher?')">Delete</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

  <a class="back" href="admin_dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>

