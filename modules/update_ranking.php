<?php
include __DIR__ . '/../config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../routes/login.php");
    exit;
} elseif ($_SESSION['is_admin'] !== 1) {
    header("Location: ../index.php");
    exit;
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] !== 1) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$fighters = [];
$message = "";

// Search for fighters by name
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
}

// Update multiple fighters' weight class and ranking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_fighters'])) {
    foreach ($_POST['fighters'] as $fighterId => $fighterData) {
        $newWeightClassId = (int) $fighterData['weight_class_id'];
        $newRanking = (int) $fighterData['ranking'];

        $stmt = $conn->prepare("UPDATE fighters SET weight_class_id = ?, ranking = ? WHERE fighter_id = ?");
        $stmt->bind_param("iii", $newWeightClassId, $newRanking, $fighterId);
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
    <title>Update Fighter Ranking</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

 

    <h1>Update Fighter Ranking and Weight Class</h1>

    <!-- Search Form -->
    <form method="POST" action="update_ranking.php">
        <input type="text" name="fighter_name" placeholder="Enter fighter's name" required>
        <button type="submit" name="search_fighter">Search</button>
    </form>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Display Search Results with Update Options -->
    <?php if (!empty($fighters)): ?>
        <h2>Edit Fighters Matching Search</h2>
        <form method="POST" action="update_ranking.php">
            <?php foreach ($fighters as $fighter): ?>
                <div style="border: 1px solid #ddd; padding: 10px; margin: 10px;">
                    <h3><?= htmlspecialchars($fighter['name']) ?></h3>
                    <input type="hidden" name="fighters[<?= $fighter['fighter_id'] ?>][fighter_id]" value="<?= $fighter['fighter_id'] ?>">

                    <!-- Weight Class Select -->
                    <label for="weight_class_id_<?= $fighter['fighter_id'] ?>">Weight Class:</label>
                    <select name="fighters[<?= $fighter['fighter_id'] ?>][weight_class_id]" required>
                        <?php
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
                </div>
            <?php endforeach; ?>

            <button type="submit" name="update_fighters">Update Selected Fighters</button>
        </form>
    <?php endif; ?>
</body>
</html>
