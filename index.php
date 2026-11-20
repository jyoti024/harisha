<?php
session_start();
include 'db.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT * FROM events WHERE event_date >= CURDATE()";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (title LIKE ? OR city LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($category !== "") {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY event_date ASC";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$events = $stmt->get_result();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventify</title>

    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="navbar">
    <a class="logo" href="index.php">Event<span>ify.</span></a>

   <nav>
    <a href="index.php">Home</a>
    <a href="index.php#events">Events</a>

    <?php if (isset($_SESSION['user_id'])): ?>

        <a href="dashboard.php">My Dashboard</a>
        <a href="logout.php">Logout</a>

    <?php else: ?>

        <a href="login.php">Login</a>
        <a href="register.php">Register</a>

    <?php endif; ?>
</nav>
</header>

<section class="hero">
    <div class="hero-content">
        <span class="tag">YOUR CITY. YOUR EVENTS.</span>

        <h1>Discover Events.<br>
        Create <span>Memories.</span></h1>

        <p>Find amazing concerts, sports, comedy shows
        and exciting experiences near you.</p>

        <a href="#events" class="primary-btn">
            Explore Events →
        </a>
    </div>
</section>

<section class="categories">
    <h2>Explore Categories</h2>

    <div class="category-list">
        <a href="index.php?category=Music">🎵 Music</a>
        <a href="index.php?category=Sports">⚽ Sports</a>
        <a href="index.php?category=Comedy">😂 Comedy</a>
        <a href="index.php?category=Workshop">💡 Workshop</a>
        <a href="index.php">🌟 All Events</a>
    </div>
</section>

<section class="events-section" id="events">

    <div class="section-heading">
        <div>
            <span class="tag">HANDPICKED FOR YOU</span>
            <h2>Upcoming Events</h2>
        </div>
    </div>

    <form method="GET" class="search-box">
        <input type="text" name="search"
        placeholder="Search events or city..."
        value="<?= e($search) ?>">

        <button type="submit">Search</button>
    </form>

    <div class="event-grid">

        <?php while ($event = $events->fetch_assoc()): ?>

        <div class="event-card">

            <img src="<?= e($event['image']) ?>"
                 alt="<?= e($event['title']) ?>">

            <div class="event-info">

                <span class="event-category">
                    <?= e($event['category']) ?>
                </span>

                <h3><?= e($event['title']) ?></h3>

                <p>📅 <?= e($event['event_date']) ?></p>

                <p>📍 <?= e($event['city']) ?></p>

                <div class="card-bottom">
                    <h3>₹<?= number_format($event['price'], 2) ?></h3>

                    <a href="event.php?id=<?= (int)$event['id'] ?>">
                        View Event →
                    </a>
                </div>

            </div>
        </div>

        <?php endwhile; ?>

    </div>

</section>

<footer>
    <h2>Eventify.</h2>
    <p>Discover experiences. Book tickets. Make memories.</p>
    <p>© 2026 Eventify. All Rights Reserved.</p>
</footer>

</body>
</html>