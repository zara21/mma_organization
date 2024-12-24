<?php
include '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get fighter IDs and other data from the form submission
    $fighter1_id = $_POST['fighter1_id'];
    $fighter2_id = $_POST['fighter2_id'];
    $date = $_POST['date'];
    $location = $_POST['location'];
    $result = $_POST['result'];
    $method = $_POST['method']; // Add method of victory if needed

    // Add fight to the fights table
    $stmt = $conn->prepare("INSERT INTO fights (fighter1_id, fighter2_id, date, location, result, method) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $fighter1_id, $fighter2_id, $date, $location, $result, $method);
    $stmt->execute();

    // Check for execution errors
    if ($stmt->error) {
        die("Error inserting fight: " . $stmt->error);
    }
    $stmt->close();

    // Update fighters' wins/losses/draws based on the result
    if ($result == "Fighter 1 Wins") {
        // Fighter 1 wins
        $stmt1 = $conn->prepare("UPDATE fighters SET wins = wins + 1 WHERE fighter_id = ?");
        $stmt1->bind_param("i", $fighter1_id);
        $stmt1->execute();
        $stmt1->close();

        // Fighter 2 loses
        $stmt2 = $conn->prepare("UPDATE fighters SET losses = losses + 1 WHERE fighter_id = ?");
        $stmt2->bind_param("i", $fighter2_id);
        $stmt2->execute();
        $stmt2->close();
    } elseif ($result == "Fighter 2 Wins") {
        // Fighter 2 wins
        $stmt1 = $conn->prepare("UPDATE fighters SET wins = wins + 1 WHERE fighter_id = ?");
        $stmt1->bind_param("i", $fighter2_id);
        $stmt1->execute();
        $stmt1->close();

        // Fighter 1 loses
        $stmt2 = $conn->prepare("UPDATE fighters SET losses = losses + 1 WHERE fighter_id = ?");
        $stmt2->bind_param("i", $fighter1_id);
        $stmt2->execute();
        $stmt2->close();
    } else {
        // Draw
        $stmt1 = $conn->prepare("UPDATE fighters SET draws = draws + 1 WHERE fighter_id = ?");
        $stmt1->bind_param("i", $fighter1_id);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE fighters SET draws = draws + 1 WHERE fighter_id = ?");
        $stmt2->bind_param("i", $fighter2_id);
        $stmt2->execute();
        $stmt2->close();
    }

    header("Location: admin.php");
    exit;
}

// Fetch fighters for the dropdowns
$fighters = $conn->query("SELECT fighter_id, name FROM fighters")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" href="../assets/css/styles.css">

<head><title>Add Fight</title></head>
<body>
    <form method="POST" action="add_fight.php">
        <label for="fighter1_id">Fighter 1:</label>
        <select name="fighter1_id" required>
            <option value="">Select Fighter 1</option>
            <?php foreach ($fighters as $fighter): ?>
                <option value="<?= $fighter['fighter_id']; ?>"><?= htmlspecialchars($fighter['name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="fighter2_id">Fighter 2:</label>
        <select name="fighter2_id" required>
            <option value="">Select Fighter 2</option>
            <?php foreach ($fighters as $fighter): ?>
                <option value="<?= $fighter['fighter_id']; ?>"><?= htmlspecialchars($fighter['name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="date">Date:</label>
        <input type="date" name="date" required>

        <label for="location">Location:</label>
        <input type="text" name="location" required>

        <label for="result">Result:</label>
        <select name="result" required>
            <option value="">Select Result</option>
            <option value="Fighter 1 Wins">Fighter 1 Wins</option>
            <option value="Fighter 2 Wins">Fighter 2 Wins</option>
            <option value="Draw">Draw</option>
        </select>

        <label for="method">Method:</label>
        <input type="text" name="method" required>

        <button type="submit">Add Fight</button>
    </form>
</body>
</html>
