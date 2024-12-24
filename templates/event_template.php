<?php
include __DIR__ . '/../config/db_connect.php';

// Get the event slug from the URL
$eslug = isset($eslug) ? $eslug : ''; // Using the preferred method to get the slug

// Fetch event details based on the slug
$stmt = $conn->prepare("SELECT * FROM events WHERE slug = ?");
$stmt->bind_param("s", $eslug);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If event not found
if (!$event) {
    die("Event not found.");
}

// Check the event status
$isPast = ($event['status'] === 'Past');

// Fetch fights based on the event status
if ($isPast) {
    // Fetch results for past events
    $fightsQuery = "
        SELECT 
            f1.name AS fighter1, 
            f1.photo AS fighter1_photo, 
            f2.name AS fighter2, 
            f2.photo AS fighter2_photo,
            ef.result
        FROM event_fights ef
        JOIN fighters f1 ON ef.fighter1_id = f1.fighter_id
        JOIN fighters f2 ON ef.fighter2_id = f2.fighter_id
        WHERE ef.event_id = ?
    ";
} else {
    // Fetch upcoming fights
    $fightsQuery = "
        SELECT 
            f1.name AS fighter1, 
            f1.photo AS fighter1_photo,
            f2.name AS fighter2, 
            f2.photo AS fighter2_photo
        FROM event_fights ef
        JOIN fighters f1 ON ef.fighter1_id = f1.fighter_id
        JOIN fighters f2 ON ef.fighter2_id = f2.fighter_id
        WHERE ef.event_id = ?
    ";
}

$stmt = $conn->prepare($fightsQuery);
$stmt->bind_param("i", $event['event_id']);
$stmt->execute();
$fights = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View event details, main fight, and other scheduled matches.">
    <title><?php echo htmlspecialchars($event['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/event.css">
</head>
<body>

    <?php include '../includes/header.php'; ?>

    <!-- Event Banner -->
    <section class="event-banner">
        <?php if (!empty($event['banner']) && file_exists("../assets/uploads/banners/" . $event['banner'])): ?>
            <img src="../assets/uploads/banners/<?php echo htmlspecialchars($event['banner']); ?>" alt="Event Banner" class="event-banner-img">

        <?php else: ?>
            <div class="banner-placeholder">No Banner Available</div>
        <?php endif; ?>
    </section>

    <header class="event-header">
        <h1 class="event-title"><?php echo htmlspecialchars($event['e_name']); ?></h1>
        <p class="event-date"><?php echo htmlspecialchars(date('F j, Y, g:i A', strtotime($event['event_date']))); ?></p>

    </header>


    <!-- Fights Section -->
    <section class="event-fights">
        <h2><?php echo $isPast ? "Fight Results" : "Upcoming Fights"; ?></h2>
        <?php if ($fights->num_rows > 0): ?>
            <div class="fight-list">
                <?php while ($fight = $fights->fetch_assoc()): ?>
                    <?php
                    $fighter1_class = $fighter2_class = '';
                    $fighter1_result = $fighter2_result = '';

                    if ($isPast) {
                        switch ($fight['result']) {
                            case 'Win': // Fighter 1 wins
                                $fighter1_class = 'winner';
                                $fighter2_class = 'loser grayscale';
                                $fighter1_result = 'Win';
                                $fighter2_result = 'Lost';
                                break;
                            case 'Loss': // Fighter 2 wins
                                $fighter2_class = 'winner';
                                $fighter1_class = 'loser grayscale';
                                $fighter2_result = 'Win';
                                $fighter1_result = 'Lost';
                                break;
                            case 'Draw': // Match draw
                                $fighter1_class = $fighter2_class = 'draw';
                                $fighter1_result = $fighter2_result = 'Draw';
                                break;
                            default: // If result is unknown
                                $fighter1_result = $fighter2_result = 'Pending';
                                break;
                        }
                    } else {
                        $fighter1_result = $fighter2_result = 'Pending';
                    }
                    ?>
                    <div class="fight-card">
                        <!-- Fighter 1 -->
                        <div class="fighter <?php echo $fighter1_class; ?>">
                            <img src="<?php 
                                $photoPath = "../assets/" . htmlspecialchars($fight['fighter1_photo']);
                                echo (!empty($fight['fighter1_photo']) && file_exists($photoPath)) ? $photoPath : "../assets/uploads/default.jpg"; 
                            ?>" alt="<?php echo htmlspecialchars($fight['fighter1']); ?>">
                            <p><?php echo htmlspecialchars($fight['fighter1']); ?></p>
                            <?php if ($isPast): ?>
                                <!-- Display Fighter 1 Result -->
                                <p class="result"><?php echo htmlspecialchars($fighter1_result); ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Fighter 2 -->
                        <div class="fighter <?php echo $fighter2_class; ?>">
                            <img src="<?php 
                                $photoPath = "../assets/" . htmlspecialchars($fight['fighter2_photo']);
                                echo (!empty($fight['fighter2_photo']) && file_exists($photoPath)) ? $photoPath : "../assets/uploads/default.jpg"; 
                            ?>" alt="<?php echo htmlspecialchars($fight['fighter2']); ?>">
                            <p><?php echo htmlspecialchars($fight['fighter2']); ?></p>
                            <?php if ($isPast): ?>
                                <!-- Display Fighter 2 Result -->
                                <p class="result"><?php echo htmlspecialchars($fighter2_result); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-data"><?php echo $isPast ? "No results available for this event." : "No upcoming fights scheduled."; ?></p>
        <?php endif; ?>
    </section>

    <?php include '../includes/footer.php'; ?>

</body>
</html>
