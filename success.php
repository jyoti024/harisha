<?php
include 'db.php';

$ref = $_GET['ref'] ?? '';

$stmt = $conn->prepare(
    "SELECT b.*, e.title, e.event_date, e.venue
     FROM bookings b
     JOIN events e ON b.event_id = e.id
     WHERE b.booking_ref = ?"
);

$stmt->bind_param("s", $ref);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking Successful</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="success-card">

    <div class="success-icon">✓</div>

    <h1>Booking Successful!</h1>

    <p>Your event tickets have been booked.</p>

    <hr>

    <h2><?= htmlspecialchars($booking['title']) ?></h2>

    <p>Name: <?= htmlspecialchars($booking['customer_name']) ?></p>

    <p>Date: <?= htmlspecialchars($booking['event_date']) ?></p>

    <p>Venue: <?= htmlspecialchars($booking['venue']) ?></p>

    <p>Tickets: <?= (int)$booking['quantity'] ?></p>

    <h3>Total: ₹<?= number_format($booking['total_amount'], 2) ?></h3>

    <p>Booking ID:
        <strong><?= htmlspecialchars($booking['booking_ref']) ?></strong>
    </p>

    <button onclick="window.print()" class="primary-btn">
        Print Ticket
    </button>

    <a href="index.php">Back to Home</a>

</div>

</body>
</html>