<?php
require_once('../config/db_connect.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $title = trim($_POST['title']);
    $e_name = trim($_POST['e_name']);
    $event_date = trim($_POST['event_date']);
    $main_fighter1 = (int)$_POST['main_fighter1'];
    $main_fighter2 = (int)$_POST['main_fighter2'];
    $ticket_link = trim($_POST['ticket_link']);
    $status = trim($_POST['status']);
    $additional_link = trim($_POST['additional_link']);
    $additional_fights = $_POST['additional_fights'] ?? [];

    // Handle banner upload
    $banner = "";
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $uploadsDir = "../assets/uploads/banners/";
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        $banner = uniqid() . "_" . basename($_FILES['banner']['name']);
        $bannerPath = $uploadsDir . $banner;
        if (!move_uploaded_file($_FILES['banner']['tmp_name'], $bannerPath)) {
            die("Error uploading banner.");
        }
    } else {
        die("Banner is required.");
    }

    // Validate required fields
    if (empty($title) || empty($e_name) || empty($event_date) || empty($main_fighter1) || empty($main_fighter2) || empty($status)) {
        die("Please fill in all required fields.");
    }

    // Generate a unique slug
    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $title));

    // Insert the event into the database
    $stmt = $conn->prepare(
        "INSERT INTO events (e_name, title, slug, event_date, main_fighter1_id, main_fighter2_id, banner, ticket_link, additional_link, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssssiissss", 
        $e_name, 
        $title, 
        $slug, 
        $event_date, 
        $main_fighter1, 
        $main_fighter2, 
        $banner, 
        $ticket_link, 
        $additional_link, 
        $status
    );

    if ($stmt->execute()) {
        $event_id = $stmt->insert_id; // Get the inserted event ID

        // Insert additional fights
        if (!empty($additional_fights)) {
            $stmt_fight = $conn->prepare("INSERT INTO event_fights (event_id, fighter1_id, fighter2_id) VALUES (?, ?, ?)");
            foreach ($additional_fights as $fight) {
                $fighter1 = (int)$fight['fighter1'];
                $fighter2 = (int)$fight['fighter2'];
                if ($fighter1 && $fighter2) {
                    $stmt_fight->bind_param("iii", $event_id, $fighter1, $fighter2);
                    $stmt_fight->execute();
                }
            }
            $stmt_fight->close();
        }

        // Generate the PHP file for the event
        $eventFileName = "../events/" . $slug . ".php";
        $eventFileContent = "<?php\n";
        $eventFileContent .= "\$eslug = '" . $slug . "';\n";
        $eventFileContent .= "include '../templates/event_template.php';\n";

        // Write the content to a new PHP file
        if (file_put_contents($eventFileName, $eventFileContent) === false) {
            die("Error creating event file: $eventFileName");
        }

        echo "Event successfully added.";
        header("Location: events.php");
        exit();
    } else {
        die("Error adding event: " . $stmt->error);
    }

    $stmt->close();
}

// Fetch all fighters for the dropdown menus
$fighters = [];
$result = $conn->query("SELECT fighter_id, name FROM fighters ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $fighters[] = $row;
}
$result->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Event</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <h1>Add New Event</h1>
    <form action="add_event.php" method="POST" enctype="multipart/form-data">
        <label for="e_name">Event Name:</label>
        <input type="text" name="e_name" id="e_name" required><br>

        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required><br>

        <label for="event_date">Event Date:</label>
        <input type="datetime-local" name="event_date" id="event_date" required><br>

        <label for="main_fighter1">Main Fighter 1:</label>
        <select name="main_fighter1" id="main_fighter1" required>
            <option value="">Select Fighter 1</option>
            <?php foreach ($fighters as $fighter): ?>
                <option value="<?= htmlspecialchars($fighter['fighter_id']) ?>">
                    <?= htmlspecialchars($fighter['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label for="main_fighter2">Main Fighter 2:</label>
        <select name="main_fighter2" id="main_fighter2" required>
            <option value="">Select Fighter 2</option>
            <?php foreach ($fighters as $fighter): ?>
                <option value="<?= htmlspecialchars($fighter['fighter_id']) ?>">
                    <?= htmlspecialchars($fighter['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label for="banner">Banner:</label>
        <input type="file" name="banner" id="banner" accept="image/*" required><br>

        <label for="ticket_link">Ticket Link:</label>
        <input type="url" name="ticket_link" id="ticket_link" placeholder="https://example.com"><br>

        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <option value="Upcoming">Upcoming</option>
            <option value="Live">Live</option>
            <option value="Past">Past</option>
        </select><br>

        <label for="additional_link">Additional Link:</label>
        <input type="text" name="additional_link" id="additional_link" placeholder="Additional information link"><br>

        <h3>Additional Fights</h3>
        <div id="additional-fights-container">
            <!-- Additional fights will be dynamically added here -->
        </div>
        <button type="button" onclick="addFight()">Add More Fights</button><br><br>

        <button type="submit">Add Event</button>
    </form>

    <script>
        let fightCount = 0;

        function addFight() {
            fightCount++;
            const container = document.getElementById('additional-fights-container');

            const fightBlock = document.createElement('div');
            fightBlock.id = `fight-${fightCount}`;
            fightBlock.innerHTML = `
                <h4>Fight ${fightCount}</h4>
                <label for="fighter1_${fightCount}">Fighter 1:</label>
                <select name="additional_fights[${fightCount}][fighter1]" id="fighter1_${fightCount}" required>
                    <?php foreach ($fighters as $fighter): ?>
                        <option value="<?= $fighter['fighter_id'] ?>">
                            <?= htmlspecialchars($fighter['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>

                <label for="fighter2_${fightCount}">Fighter 2:</label>
                <select name="additional_fights[${fightCount}][fighter2]" id="fighter2_${fightCount}" required>
                    <?php foreach ($fighters as $fighter): ?>
                        <option value="<?= $fighter['fighter_id'] ?>">
                            <?= htmlspecialchars($fighter['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>
            `;
            container.appendChild(fightBlock);
        }
    </script>
</body>
</html>
