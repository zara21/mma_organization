<?php
require_once('../config/db_connect.php');

// შეამოწმეთ, გადმოიცა თუ არა `event_id`
if (isset($_GET['event_id'])) {
    $event_id = (int)$_GET['event_id'];

    // კონკრეტული ივენტის მიღება
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$event) {
        die("Event not found.");
    }

    // რედაქტირების ფორმა
    echo '<h1>Edit Event: ' . htmlspecialchars($event['name']) . '</h1>';
    ?>
    <form method="POST" action="event_edit.php?event_id=<?php echo $event['event_id']; ?>">
        <label for="name">Event Name:</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($event['name']); ?>" required><br>

        <label for="date">Event Date:</label>
        <input type="datetime-local" name="date" id="date" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['event_date']))); ?>" required><br>

        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <option value="Upcoming" <?php if ($event['status'] === 'Upcoming') echo 'selected'; ?>>Upcoming</option>
            <option value="Live" <?php if ($event['status'] === 'Live') echo 'selected'; ?>>Live</option>
            <option value="Past" <?php if ($event['status'] === 'Past') echo 'selected'; ?>>Past</option>
        </select><br>

        <button type="submit">Save Changes</button>
    </form>
    <?php
    exit;
}

// თუ `event_id` არ გადმოიცა, გამოვიტანოთ ივენტების სია
$stmt = $conn->prepare("SELECT event_id, title, status FROM events");
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management</title>
</head>
<body>
    <h1>Event Management</h1>
    <table border="1">
        <thead>
            <tr>
                <th>Event Name</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($event = $events->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                    <td><?php echo htmlspecialchars($event['status']); ?></td>
                    <td>
                        <a href="event_edit.php?event_id=<?php echo $event['event_id']; ?>">Edit</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
