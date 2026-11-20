<?php
include 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("Invalid event.");
}

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    die("Event not found.");
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= e($event['title']) ?> - Eventify</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="navbar">
    <a class="logo" href="index.php">Event<span>ify.</span></a>
    <a href="index.php">← Back to Events</a>
</header>

<div class="details-container">

    <img class="details-image"
         src="<?= e($event['image']) ?>">

    <div class="details-info">

        <span class="event-category">
            <?= e($event['category']) ?>
        </span>

        <h1><?= e($event['title']) ?></h1>

        <p><?= e($event['description']) ?></p>

        <p>📅 Date: <?= e($event['event_date']) ?></p>

        <p>⏰ Time: <?= e($event['event_time']) ?></p>

        <p>📍 Venue: <?= e($event['venue']) ?></p>

        <p>🌆 City: <?= e($event['city']) ?></p>

        <h2>₹<?= number_format($event['price'], 2) ?></h2>

        <p>Available tickets:
            <?= (int)$event['available_tickets'] ?>
        </p>

        <?php if ($event['available_tickets'] > 0): ?>
            <a class="primary-btn"
               href="book.php?id=<?= (int)$event['id'] ?>">
               Book Tickets Now →
            </a>
        <?php else: ?>
            <p>Tickets Sold Out!</p>
        <?php endif; ?>

    </div>
</div>

</body>
</html>