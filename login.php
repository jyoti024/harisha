<?php
session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = "Please enter your email and password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password FROM users WHERE email = ?"
        );

        if (!$stmt) {
            die("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $stmt->bind_result($id, $name, $user_email, $hashed_password);

        if ($stmt->fetch() && password_verify($password, $hashed_password)) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $user_email;

            $stmt->close();

            header("Location: dashboard.php");
            exit;

        } else {
            $message = "Invalid email or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Eventify</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body class="auth-page">

<div class="auth-container">

    <div class="auth-logo">Event<span>ify</span></div>

    <h1>Welcome Back!</h1>

    <p class="auth-subtitle">
        Login to discover and book amazing events.
    </p>

    <?php if (isset($_GET['registered'])): ?>
        <div class="success-message">
            Account created successfully! Please login.
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="error-message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <label>Email Address</label>
        <input type="email"
               name="email"
               placeholder="Enter your email"
               required>

        <label>Password</label>
        <input type="password"
               name="password"
               placeholder="Enter your password"
               required>

        <button type="submit">Login to Eventify →</button>

    </form>

    <p class="auth-switch">
        Don't have an account?
        <a href="register.php">Create Account</a>
    </p>

    <a href="index.php" class="back-home">← Back to Home</a>

</div>

</body>
</html>
