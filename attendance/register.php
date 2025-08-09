<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("includes/db.php"); // Ensure this path is correct (adjust if needed)

// If already logged in → send to dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']); // teacher or student

    if ($name == "" || $email == "" || $password == "" || $role == "") {
        $message = "All fields are required!";
    } else {
        // Check if user already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "Email is already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                // ✅ Redirect to login with success message
                header("Location: login.php?registered=1");
                exit();

            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Attendance System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f8fa;
        }
        .container {
            background: white;
            max-width: 400px;
            margin: 50px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin-top: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
        }
        button:hover {
            background: #0056b3;
        }
        .message {
            text-align: center;
            color: red;
            margin-top: 10px;
        }

        /* Password toggle styles */
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 40px; /* space for the toggle button */
        }
        .password-toggle-btn {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: #555;
            padding: 0;
            margin: 0;
            height: 30px;
            width: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            outline: none;
        }
        .password-toggle-btn:focus {
            outline: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Create Account</h2>
    <?php if ($message != ""): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>

        <div class="password-wrapper">
            <input type="password" name="password" id="passwordField" placeholder="Password" required>
            <button type="button" class="password-toggle-btn" aria-label="Toggle password visibility" onclick="togglePassword()">👁️</button>
        </div>

        <select name="role" required>
            <option value="">Select Role</option>
            <option value="teacher">Teacher</option>
            <option value="student">Student</option>
        </select>
        <button type="submit">Register</button>
    </form>
    <p style="text-align:center; margin-top:15px;">Already have an account? <a href="login.php">Login</a></p>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('passwordField');
        const btn = document.querySelector('.password-toggle-btn');
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '🙈';
        } else {
            input.type = 'password';
            btn.textContent = '👁️';
        }
    }
</script>
</body>
</html>
