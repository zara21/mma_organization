<?php
include __DIR__ . '/../config/db_connect.php';

if (!isset($fighter_slug)) {
    die("Fighter profile not specified.");
}

// Retrieve fighter data using slug
$query = "SELECT * FROM fighters WHERE slug = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $fighter_slug);
$stmt->execute();
$fighter = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fighter) {
    die("Fighter not found.");
}

// Calculate age from birthdate
if (!empty($fighter['birthdate'])) {
    $birthdate = new DateTime($fighter['birthdate']);
    $today = new DateTime();
    $age = $today->diff($birthdate)->y;
} else {
    $age = "Unknown";
}

// Fetch upcoming fights
$query_upcoming = "
    SELECT 
        ef.event_id, 
        e.title AS event_title, 
        e.event_date, 
        f1.name AS fighter1, 
        f2.name AS fighter2
    FROM event_fights ef
    JOIN events e ON ef.event_id = e.event_id
    JOIN fighters f1 ON ef.fighter1_id = f1.fighter_id
    JOIN fighters f2 ON ef.fighter2_id = f2.fighter_id
    WHERE (ef.fighter1_id = ? OR ef.fighter2_id = ?) AND e.status = 'Upcoming'
    ORDER BY e.event_date ASC";
$stmt = $conn->prepare($query_upcoming);
$stmt->bind_param("ii", $fighter['fighter_id'], $fighter['fighter_id']);
$stmt->execute();
$upcoming_fights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch fight history
$query_history = "
    SELECT 
        ef.event_id, 
        e.title AS event_title, 
        e.event_date AS fight_date, 
        ef.result, 
        f1.name AS fighter1, 
        f2.name AS fighter2, 
        f1.fighter_id AS fighter1_id, 
        f2.fighter_id AS fighter2_id
    FROM event_fights ef
    JOIN events e ON ef.event_id = e.event_id
    JOIN fighters f1 ON ef.fighter1_id = f1.fighter_id
    JOIN fighters f2 ON ef.fighter2_id = f2.fighter_id
    WHERE (ef.fighter1_id = ? OR ef.fighter2_id = ?) AND e.status = 'Past'
    ORDER BY e.event_date DESC";
$stmt = $conn->prepare($query_history);
$stmt->bind_param("ii", $fighter['fighter_id'], $fighter['fighter_id']);
$stmt->execute();
$fight_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($fighter['name']) ?> - MMA Organization</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>


<section class="fighter-profile-card">
    <div class="fighter-profile">

    
        <div class="fighter-card-container">
            <!-- Fighter Photo -->
            <?php if (!empty($fighter['photo'])): ?>
                <div class="fighter-photo">
                    <img src="../assets/<?= htmlspecialchars($fighter['photo']) ?>" alt="<?= htmlspecialchars($fighter['name']) ?>'s Photo">
                </div>
            <?php else: ?>
                <div class="fighter-photo">
                    <img src="../images/default-profile.jpg" alt="Default Photo">
                </div>
            <?php endif; ?>

            <!-- Fighter Details -->
            <div class="fighter-info">
                <h1><?= htmlspecialchars($fighter['name']) ?></h1>
                <ul class="fighter-stats">
                    <li><strong>Age:</strong> <?= htmlspecialchars($age) ?> years</li>
                    <li><strong>Weight:</strong> <?= htmlspecialchars($fighter['weight']) ?> kg</li>
                    <li><strong>Height:</strong> <?= htmlspecialchars($fighter['height']) ?> cm</li>
                    <li><strong>Records: (W-L-D)</strong> <?= htmlspecialchars($fighter['wins']) ?>:<?= htmlspecialchars($fighter['losses']) ?>:<?= htmlspecialchars($fighter['draws']) ?></li>
                    
                </ul>
            </div>
        </div>
    </div>

</section>

<?php if (count($upcoming_fights) > 0): ?>
    <h3>Upcoming Fights</h3>
    <div class="upcoming-fights-container">
        <table class="fight-history-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Opponent</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcoming_fights as $fight): ?>
                    <tr>
                        <td><?= htmlspecialchars($fight['event_title']) ?></td>
                        <td>
                            <?php 
                            $opponent = ($fight['fighter1'] === $fighter['name']) ? $fight['fighter2'] : $fight['fighter1'];
                            $opponent_slug = strtolower(str_replace(' ', '-', $opponent));
                            ?>
                            <a href="../athletes/<?= $opponent_slug ?>.php" style="color: inherit; text-decoration: none;">
                                <?= htmlspecialchars($opponent) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($fight['event_date']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h3>Fight History</h3>
<div class="fight-history-container">
    <table class="fight-history-table">
        <thead>
            <tr>
                <th>Fighter 1</th>
                <th>Fighter 2</th>
                <th>Result</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($fight_history) > 0): ?>
                <?php foreach ($fight_history as $fight): ?>
                    <tr>
                        <td style="font-weight: <?= $fight['fighter1_id'] == $fighter['fighter_id'] ? 'bold' : 'normal' ?>;">
                            <?php
                            $fighter1_slug = strtolower(str_replace(' ', '-', $fight['fighter1']));
                            ?>
                            <a href="../athletes/<?= $fighter1_slug ?>.php" style="color: inherit; text-decoration: none;">
                                <?= htmlspecialchars($fight['fighter1']) ?>
                            </a>
                        </td>
                        <td style="font-weight: <?= $fight['fighter2_id'] == $fighter['fighter_id'] ? 'bold' : 'normal' ?>;">
                            <?php
                            $fighter2_slug = strtolower(str_replace(' ', '-', $fight['fighter2']));
                            ?>
                            <a href="../athletes/<?= $fighter2_slug ?>.php" style="color: inherit; text-decoration: none;">
                                <?= htmlspecialchars($fight['fighter2']) ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            if ($fight['result'] === 'Draw') {
                                echo 'Draw';
                            } elseif ($fight['fighter1_id'] == $fighter['fighter_id'] && $fight['result'] === 'Fighter 1 Wins') {
                                echo '<span style="color: green; font-weight: bold;">Win</span>';
                            } elseif ($fight['fighter2_id'] == $fighter['fighter_id'] && $fight['result'] === 'Fighter 2 Wins') {
                                echo '<span style="color: green; font-weight: bold;">Win</span>';
                            } else {
                                echo '<span style="color: red; font-weight: bold;">Lost</span>';
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($fight['fight_date']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No fight history available for this fighter.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
