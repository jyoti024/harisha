<?php
session_start();
include 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$id || !$name || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !preg_match('/^[0-9]{10}$/', $phone)
        || !$quantity || $quantity < 1 || $quantity > 10) {
        die("Please enter valid booking details.");
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "SELECT price FROM events WHERE id = ? FOR UPDATE"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();

        if (!$event) {
            throw new Exception("Event not found.");
        }

        $price = (float)$event['price'];
        $total = $price * $quantity;

        $update = $conn->prepare(
            "UPDATE events
             SET available_tickets = available_tickets - ?
             WHERE id = ? AND available_tickets >= ?"
        );

        $update->bind_param("iii", $quantity, $id, $quantity);
        $update->execute();

        if ($update->affected_rows !== 1) {
            throw new Exception("Not enough tickets available.");
        }

        $ref = "EVT" . strtoupper(bin2hex(random_bytes(4)));

        $insert = $conn->prepare(
            "INSERT INTO bookings
            (booking_ref, event_id, customer_name, email,
             phone, quantity, total_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $insert->bind_param(
            "sisssid",
            $ref, $id, $name, $email, $phone, $quantity, $total
        );

        $insert->execute();
        $conn->commit();

        header("Location: success.php?ref=" . urlencode($ref));
        exit;

    } catch (Throwable $error) {
        $conn->rollback();
        $message = $error->getMessage();
    }
}

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    die("Event not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Tickets - Eventify</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="navbar">
    <a class="logo" href="index.php">Event<span>ify.</span></a>
    <a href="event.php?id=<?= (int)$id ?>">← Back</a>
</header>

<div class="booking-container">

    <h1>Book Your Tickets</h1>

    <h2><?= htmlspecialchars($event['title']) ?></h2>

    <p>Ticket Price: ₹<?= number_format($event['price'], 2) ?></p>

    <?php if (!empty($message)): ?>
        <p class="error">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden" name="event_id"
               value="<?= (int)$event['id'] ?>">

        <label>Full Name</label>
        <input type="text" name="name" required
               value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">

        <label>Email Address</label>
        <input type="email" name="email" required
               value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">

        <label>Mobile Number</label>
        <input type="tel" name="phone"
               pattern="[0-9]{10}" maxlength="10" required>

        <label>Number of Tickets</label>
        <input type="number" name="quantity"
               min="1" max="10" value="1" required>

        <button type="submit" class="primary-btn">
            Confirm Booking
        </button>

    </form>

</div>

</body>
</html>