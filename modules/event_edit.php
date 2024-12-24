<?php
require_once('../config/db_connect.php');

// Check event ID
if (!isset($_GET['event_id'])) {
    die("Error: Event ID is missing.");
}
$event_id = (int)$_GET['event_id'];

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    die("Error: Event not found.");
}

// Fetch fights for this event
$fights_stmt = $conn->prepare("
    SELECT ef.fight_id, f1.fighter_id AS fighter1_id, f1.name AS fighter1, 
           f2.fighter_id AS fighter2_id, f2.name AS fighter2, ef.result
    FROM event_fights ef
    JOIN fighters f1 ON ef.fighter1_id = f1.fighter_id
    JOIN fighters f2 ON ef.fighter2_id = f2.fighter_id
    WHERE ef.event_id = ?
");
$fights_stmt->bind_param("i", $event_id);
$fights_stmt->execute();
$fights = $fights_stmt->get_result();
$fights_stmt->close();

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update event details
    $e_name = $_POST['e_name'];
    $date = $_POST['date'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE events SET e_name = ?, event_date = ?, additional_link = ?, status = ? WHERE event_id = ?");
    $stmt->bind_param("ssssi", $e_name, $date, $description, $status, $event_id);
    $stmt->execute();
    $stmt->close();

    // Update fight results and fighter records
    foreach ($_POST['fights'] as $fight_id => $result) {
        $stmt_fight = $conn->prepare("UPDATE event_fights SET result = ? WHERE fight_id = ?");
        $stmt_fight->bind_param("si", $result, $fight_id);
        $stmt_fight->execute();
        $stmt_fight->close();

        // Update fighters' records based on the result
        $fighter1_id = $_POST['fighter1'][$fight_id];
        $fighter2_id = $_POST['fighter2'][$fight_id];

        if ($result === 'fighter1') {
            // Fighter 1 wins
            $stmt_winner = $conn->prepare("UPDATE fighters SET wins = wins + 1 WHERE fighter_id = ?");
            $stmt_winner->bind_param("i", $fighter1_id);
            $stmt_winner->execute();
            $stmt_winner->close();

            $stmt_loser = $conn->prepare("UPDATE fighters SET losses = losses + 1 WHERE fighter_id = ?");
            $stmt_loser->bind_param("i", $fighter2_id);
            $stmt_loser->execute();
            $stmt_loser->close();
        } elseif ($result === 'fighter2') {
            // Fighter 2 wins
            $stmt_winner = $conn->prepare("UPDATE fighters SET wins = wins + 1 WHERE fighter_id = ?");
            $stmt_winner->bind_param("i", $fighter2_id);
            $stmt_winner->execute();
            $stmt_winner->close();

            $stmt_loser = $conn->prepare("UPDATE fighters SET losses = losses + 1 WHERE fighter_id = ?");
            $stmt_loser->bind_param("i", $fighter1_id);
            $stmt_loser->execute();
            $stmt_loser->close();
        } elseif ($result === 'Draw') {
            // Both fighters draw
            $stmt_draw = $conn->prepare("UPDATE fighters SET draws = draws + 1 WHERE fighter_id IN (?, ?)");
            $stmt_draw->bind_param("ii", $fighter1_id, $fighter2_id);
            $stmt_draw->execute();
            $stmt_draw->close();
        }
    }

    // Redirect after successful update
    header("Location: event_edit.php?event_id=$event_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/event.css">
    <title>Edit Event</title>
</head>
<body>
    <h1>Edit Event: <?php echo htmlspecialchars($event['e_name']); ?></h1>
    <form method="POST">
        <label>Event Name:</label>
        <input type="text" name="e_name" value="<?php echo htmlspecialchars($event['e_name']); ?>" required><br>

        <label>Event Date:</label>
        <input type="datetime-local" name="date" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['event_date']))); ?>" required><br>

        <label>Description:</label>
        <textarea name="description"><?php echo htmlspecialchars($event['additional_link']); ?></textarea><br>

        <label>Status:</label>
        <select name="status" required>
            <option value="Upcoming" <?php if ($event['status'] === 'Upcoming') echo 'selected'; ?>>Upcoming</option>
            <option value="Live" <?php if ($event['status'] === 'Live') echo 'selected'; ?>>Live</option>
            <option value="Past" <?php if ($event['status'] === 'Past') echo 'selected'; ?>>Past</option>
        </select><br>

        <h2>Fight Results</h2>
        <?php while ($fight = $fights->fetch_assoc()): ?>
            <div>
                <p><?php echo htmlspecialchars($fight['fighter1']) . " vs " . htmlspecialchars($fight['fighter2']); ?></p>
                <select name="fights[<?php echo $fight['fight_id']; ?>]" required>
                    <option value="Pending" <?php if ($fight['result'] === 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="fighter1" <?php if ($fight['result'] === 'fighter1') echo 'selected'; ?>><?php echo htmlspecialchars($fight['fighter1']); ?> Wins</option>
                    <option value="fighter2" <?php if ($fight['result'] === 'fighter2') echo 'selected'; ?>><?php echo htmlspecialchars($fight['fighter2']); ?> Wins</option>
                    <option value="Draw" <?php if ($fight['result'] === 'Draw') echo 'selected'; ?>>Draw</option>
                </select>
                <input type="hidden" name="fighter1[<?php echo $fight['fight_id']; ?>]" value="<?php echo $fight['fighter1_id']; ?>">
                <input type="hidden" name="fighter2[<?php echo $fight['fight_id']; ?>]" value="<?php echo $fight['fighter2_id']; ?>">
            </div>
        <?php endwhile; ?>

        <button type="submit">Update Event and Fight Results</button>
    </form>
</body>
</html>
