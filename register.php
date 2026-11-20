<?php
session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === "" || $email === "" || $password === "") {

        $message = "Please fill all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $message = "This email is already registered.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "sss",
                $name,
                $email,
                $hashed_password
            );

            if ($stmt->execute()) {

                $stmt->close();
                $check->close();

                header("Location: login.php?registered=1");
                exit;

            } else {

                $message = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Eventify</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body class="auth-page">

<div class="auth-container">

    <div class="auth-logo">Event<span>ify</span></div>

    <h1>Create Account</h1>

    <p class="auth-subtitle">
        Join Eventify and discover amazing events.
    </p>

    <?php if (!empty($message)): ?>
        <div class="error-message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">

        <label>Full Name</label>
        <input type="text"
               name="name"
               placeholder="Enter your full name"
               required>

        <label>Email Address</label>
        <input type="email"
               name="email"
               placeholder="Enter your email"
               required>

        <label>Password</label>
        <input type="password"
               name="password"
               placeholder="Create password (minimum 8 characters)"
               minlength="8"
               required>

        <button type="submit">Create My Account →</button>

    </form>

    <p class="auth-switch">
        Already have an account?
        <a href="login.php">Login Now</a>
    </p>

    <a href="index.php" class="back-home">← Back to Home</a>

</div>

</body>
</html>
