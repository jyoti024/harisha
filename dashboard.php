<?php
session_start();

include 'db.php';

// Check whether the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$message = "";

// Get user bookings
$sql = "SELECT b.*, e.title, e.event_date, e.venue
        FROM bookings b
        INNER JOIN events e ON b.event_id = e.id
        WHERE b.email = ?
        ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$email = $_SESSION['user_email'];

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Dashboard - Eventify</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="auth-container">

    <h1>Welcome, 
        <?= htmlspecialchars($_SESSION['user_name']) ?>!
    </h1>

    <p>Welcome to your Eventify dashboard.</p>

    <hr>

    <h2>My Bookings</h2>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($booking = $result->fetch_assoc()): ?>

            <div class="booking-card">

                <h3>
                    <?= htmlspecialchars($booking['title']) ?>
                </h3>

                <p>
                    <strong>Event Date:</strong>
                    <?= htmlspecialchars($booking['event_date']) ?>
                </p>

                <p>
                    <strong>Venue:</strong>
                    <?= htmlspecialchars($booking['venue']) ?>
                </p>

                <?php if (isset($booking['quantity'])): ?>
                    <p>
                        <strong>Tickets:</strong>
                        <?= (int)$booking['quantity'] ?>
                    </p>
                <?php endif; ?>

                <?php if (isset($booking['total_amount'])): ?>
                    <p>
                        <strong>Total Amount:</strong>
                        ₹<?= number_format($booking['total_amount'], 2) ?>
                    </p>
                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>You have not booked any events yet.</p>

        <a href="index.php">Explore Events</a>

    <?php endif; ?>

    <hr>

    <p>
        <a href="index.php">Home</a>
        |
        <a href="logout.php">Logout</a>
    </p>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>