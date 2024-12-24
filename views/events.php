<?php include __DIR__ . '/../config/db_connect.php'; ?>

<?php
// Fetch all events
$stmt = $conn->prepare("
    SELECT e.event_id, e.title, e.event_date, e.banner, e.additional_link, 
           REPLACE(LOWER(e.title), ' ', '-') AS slug
    FROM events e
    ORDER BY e.event_date DESC
");
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php include '../includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/event.css">

    <style>
        
    </style>

    
</head>
<body>


<section class="events">
    <div class="upcoming">
        <h2>Upcoming Events</h2>
        <div class="row">
            <?php
                // Fetch all upcoming events
                $stmt = $pdo->query("SELECT * FROM events WHERE status = 'upcoming' ORDER BY event_date ASC");
                while ($event = $stmt->fetch()) {
                    echo "<div class='col-md-3'>"; // Bootstrap column
                    echo "<div class='event-card'>";

                    $slug = htmlspecialchars($event['slug']);
                    $banner = htmlspecialchars($event['banner'] ?: '../assets/uploads/default-banner.jpg');
                    $bannerPath = file_exists("../assets/uploads/banners/{$banner}") ? "../assets/uploads/banners/{$banner}" : '../assets/uploads/default-banner.jpg';
                    $eventDate = new DateTime($event['event_date']);
                    $now = new DateTime();

                    // Prepare the event date for JavaScript
                    $eventDateJS = $eventDate->format('Y-m-d H:i:s');

                    echo "<a href='../events/{$slug}.php'>";
                    echo "<img src='{$bannerPath}' alt='" . htmlspecialchars($event['title']) . " Banner' class='img-fluid'>";
                    echo "</a>";

                    echo "<h3><a href='events/{$slug}.php'>" . htmlspecialchars($event['e_name']) . "</a></h3>";

                    echo "<p id='countdown-{$event['event_id']}' data-event-date='{$eventDateJS}'>Calculating time...</p>"; // Placeholder for countdown

                    echo "</div>"; // event-card
                    echo "</div>"; // col-md-3
                }
            ?>
        </div>
    </div>
    <div class="Past">
        <h2>Past Events</h2>
        <div class="row">
            <?php
                // Fetch all past events
                $stmt = $pdo->query("SELECT * FROM events WHERE status = 'past' ORDER BY event_date DESC");
                while ($event = $stmt->fetch()) {
                    echo "<div class='col-md-3'>"; // Bootstrap column
                    echo "<div class='event-card'>";

                    $slug = htmlspecialchars($event['slug']);
                    $banner = htmlspecialchars($event['banner'] ?: '../assets/uploads/default-banner.jpg');
                    $bannerPath = file_exists("../assets/uploads/banners/{$banner}") ? "../assets/uploads/banners/{$banner}" : '../assets/uploads/default-banner.jpg';
                    $eventDate = new DateTime($event['event_date']);
                    
                    // Event banner and link
                    echo "<div class='event-photo-container position-relative'>";
                    echo "<a href='../events/$slug.php'>";
                    echo "<img src='$bannerPath' alt='" . htmlspecialchars($event['title']) . "' class='img-fluid rounded'>";
                    echo "</a>";
                    echo "</div>";
                    
                    // Event details
                    echo "<div class='event-card-info mt-3'>";
                    echo "<h3 class='h5'><a href='events/$slug.php'>" . htmlspecialchars($event['e_name']) . "</a></h3>";
                    echo "<p><strong>Date:</strong> " . $eventDate->format('F j, Y, g:i A') . "</p>";
                    echo "<p>Event Completed</p>"; // Status message
                    echo "</div>";

                    echo "</div>";
                    echo "</div>";
                }
            ?>
        </div>
    </div>

</section>



<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Update countdowns every second
        setInterval(function() {
            // Find all countdown elements
            const countdownElements = document.querySelectorAll('[id^="countdown-"]');
            
            countdownElements.forEach(function(el) {
                const eventDate = new Date(el.getAttribute('data-event-date')).getTime();
                const now = new Date().getTime();
                const timeLeft = eventDate - now;

                if (timeLeft <= 0) {
                    el.textContent = "Event is live now!";
                    return;
                }

                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                el.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            });
        }, 1000); // Update every second
    });
</script>


</body>
</html>
