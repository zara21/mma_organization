<?php
include '../config/db_connect.php';

// Check if the fighter ID is provided in the URL
if (!isset($_GET['id'])) {
    die("Fighter ID not specified.");
}

$fighter_id = intval($_GET['id']);

// Fetch the fighter's current details
$query = "SELECT * FROM fighters WHERE fighter_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $fighter_id);
$stmt->execute();
$fighter = $stmt->get_result()->fetch_assoc();

// Check if the fighter exists
if (!$fighter) {
    die("Fighter not found.");
}

// Update fighter details if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $age = intval($_POST['age']); // Ensure age is treated as an integer
    $weight = floatval($_POST['weight']); // Ensure weight is treated as a float
    $wins = intval($_POST['wins']); // Ensure wins is treated as an integer
    $losses = intval($_POST['losses']); // Ensure losses is treated as an integer
    
    // Updated type string to include 'd' for the weight
    $update_query = "UPDATE fighters SET name = ?, age = ?, weight = ?, wins = ?, losses = ? WHERE fighter_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sidiii", $name, $age, $weight, $wins, $losses, $fighter_id); // Update types accordingly
    $stmt->execute();
    $stmt->close();

    // Redirect to delete_fighter.php or any other page after the update
    header("Location: delete_fighter.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Fighter</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<h2>Edit Fighter</h2>
<form method="POST" action="edit_fighter.php?id=<?= $fighter_id ?>">
    <label for="name">Name:</label>
    <input type="text" name="name" id="name" value="<?= htmlspecialchars($fighter['name']) ?>" required>

    <label for="age">Age:</label>
    <input type="number" name="age" id="age" value="<?= htmlspecialchars($fighter['age']) ?>" required>

    <label for="weight">Weight:</label>
    <input type="number" name="weight" id="weight" value="<?= htmlspecialchars($fighter['weight']) ?>" required>

    <label for="wins">Wins:</label>
    <input type="number" name="wins" id="wins" value="<?= htmlspecialchars($fighter['wins']) ?>" required>

    <label for="losses">Losses:</label>
    <input type="number" name="losses" id="losses" value="<?= htmlspecialchars($fighter['losses']) ?>" required>

    <button type="submit">Update Fighter</button>
</form>

</body>
</html>
