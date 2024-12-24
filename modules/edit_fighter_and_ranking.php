<?php
include '../config/db_connect.php';

// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit;
// } elseif ($_SESSION['is_admin'] !== 1) {
//     header("Location: index.php");
//     exit;
// }

// Initialize variables
$fighters = [];
$message = "";

// Handle search and display fighters
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_fighter'])) {
    $fighterName = '%' . $_POST['fighter_name'] . '%'; // Using wildcard for partial matches
    $stmt = $conn->prepare("SELECT * FROM fighters WHERE name LIKE ?");
    $stmt->bind_param("s", $fighterName);
    $stmt->execute();
    $fighters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($fighters) === 0) {
        $message = "No fighters found with that name.";
    }
} else {
    // Fetch all fighters if no search query
    $stmt = $conn->prepare("SELECT * FROM fighters");
    $stmt->execute();
    $fighters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Update fighters' details and ranking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_fighters'])) {
    foreach ($_POST['fighters'] as $fighterId => $fighterData) {
        // Retrieve and sanitize inputs
        $newName = htmlspecialchars($fighterData['name']);
        $newBirthdate = htmlspecialchars($fighterData['birthdate']);
        $newPhoto = htmlspecialchars($fighterData['photo']);
        $newWeightClassId = (int) $fighterData['weight_class_id'];
        $newRanking = (int) $fighterData['ranking'];

        // Update the fighter's data in the database
        $stmt = $conn->prepare("UPDATE fighters SET name = ?, birthdate = ?, photo = ?, weight_class_id = ?, ranking = ? WHERE fighter_id = ?");
        $stmt->bind_param("sssiis", $newName, $newBirthdate, $newPhoto, $newWeightClassId, $newRanking, $fighterId);
        $stmt->execute();
    }

    $message = "Selected fighters' information updated successfully.";
}
?>

<?php include '../includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Fighter and Ranking</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<h1>Edit Fighter Information and Ranking</h1>

<!-- Search Form -->
<form method="POST" action="edit_fighter_and_ranking.php">
    <input type="text" name="fighter_name" placeholder="Enter fighter's name" required>
    <button type="submit" name="search_fighter">Search</button>
</form>

<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- Display Fighters List with Editable Fields -->
<h2>All Fighters</h2>
<form method="POST" action="edit_fighter_and_ranking.php">
    <?php foreach ($fighters as $fighter): ?>
        <div style="border: 1px solid #ddd; padding: 10px; margin: 10px;">
            <h3>Edit Fighter: <?= htmlspecialchars($fighter['name']) ?></h3>

            <!-- Hidden Fighter ID -->
            <input type="hidden" name="fighters[<?= $fighter['fighter_id'] ?>][fighter_id]" value="<?= $fighter['fighter_id'] ?>">

            <!-- Editable Fighter Details -->
            <label for="name_<?= $fighter['fighter_id'] ?>">Name:</label>
            <input type="text" name="fighters[<?= $fighter['fighter_id'] ?>][name]" value="<?= htmlspecialchars($fighter['name']) ?>" required><br>

            <label for="birthdate_<?= $fighter['fighter_id'] ?>">Birthdate:</label>
            <input type="date" name="fighters[<?= $fighter['fighter_id'] ?>][birthdate]" value="<?= $fighter['birthdate'] ?>" required><br>
            
             <!-- Editable Fighter Details -->
             <label for="name_<?= $fighter['fighter_id'] ?>">Nickname:</label>
            <input type="text" name="fighters[<?= $fighter['fighter_id'] ?>][nickname]" value="<?= htmlspecialchars($fighter['nickname']) ?>" required><br>

            <label for="photo_<?= $fighter['fighter_id'] ?>">Photo URL:</label>
            <input type="text" name="fighters[<?= $fighter['fighter_id'] ?>][photo]" value="<?= $fighter['photo'] ?>"><br>

            <!-- Weight Class Select -->
            <label for="weight_class_id_<?= $fighter['fighter_id'] ?>">Weight Class:</label>
            <select name="fighters[<?= $fighter['fighter_id'] ?>][weight_class_id]" required>
                <?php
                // Fetch weight classes for the dropdown
                $weightClassStmt = $conn->query("SELECT weight_class_id, name FROM weight_classes");
                while ($row = $weightClassStmt->fetch_assoc()) {
                    $selected = ($row['weight_class_id'] == $fighter['weight_class_id']) ? 'selected' : '';
                    echo "<option value='{$row['weight_class_id']}' $selected>{$row['name']}</option>";
                }
                ?>
            </select><br>

            <!-- Ranking Input -->
            <label for="ranking_<?= $fighter['fighter_id'] ?>">Ranking:</label>
            <input type="number" name="fighters[<?= $fighter['fighter_id'] ?>][ranking]" value="<?= $fighter['ranking'] ?>" min="0" required><br>
            <button type="submit" name="update_fighters">Update Selected Fighters</button>


        </div>
    <?php endforeach; ?>

</form>

</body>
</html>
