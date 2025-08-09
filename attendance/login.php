<?php
session_start();
require 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();
            if (password_verify($pass, $user['password'])) {
                $_SESSION['user'] = $user;

                if ($user['role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['role'] == 'teacher') {
                    header("Location: teacher_dashboard.php");
                } elseif ($user['role'] == 'student') {
                    header("Location: student_dashboard.php");
                }
                exit;
            } else {
                $error = "❌ Invalid password.";
            }
        } else {
            $error = "❌ User not found.";
        }
    } else {
        $error = "Database error.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Attendance System</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            width: 350px;
            padding: 30px;
            background: #ffffff;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 40px 10px 10px; /* right padding for icon */
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #0056b3;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
        }
        .success {
            color: green;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: #777;
        }
        /* Password toggle styles */
        .password-toggle-btn {
            position: absolute;
            top: 70%;
            right: 10px;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 18px;
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
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>

        <?php if (isset($_GET['registered'])): ?>
            <div class="success">✅ Registration successful! Please log in.</div>
        <?php endif; ?>

        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="POST">
            <div class="input-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="input-group">
                <label>Password:</label>
                <input type="password" name="password" id="passwordField" required>
                <button type="button" class="password-toggle-btn" aria-label="Toggle password visibility" onclick="togglePassword()">👁️</button>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="footer">
            &copy; <?= date('Y') ?> Attendance System
        </div>
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
