<?php
include 'includes/db.php';

// Admin credentials
$name = "Admin";
$email = "admin@test.com";
$password = "12345";  // plain text for now
$role = "admin";

// Hash the password
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Insert or update admin user
$sql = "INSERT INTO users (name, email, password, role)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE password = VALUES(password)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $name, $email, $hashed, $role);

if ($stmt->execute()) {
    echo "Admin user created/updated successfully.<br>";
    echo "Email: $email<br>Password: $password<br>";
} else {
    echo "Error: " . $stmt->error;
}
?>

