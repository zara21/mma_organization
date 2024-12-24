<?php
include '../config/db_connect.php';

// Check if the delete request is set
if (isset($_GET['delete'])) {
    $fighter_id = intval($_GET['delete']);
    
    // Delete the fighter from the database
    $stmt = $conn->prepare("DELETE FROM fighters WHERE fighter_id = ?");
    $stmt->bind_param("i", $fighter_id);
    $stmt->execute();
    $stmt->close();

    header("Location: delete_fighter.php"); // Redirect back to the same page
    exit;
}

// Fetch fighters from the database
$query = "SELECT * FROM fighters";
$result = $conn->query($query);
$fighters = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Fighters</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<h2>Edit Fighters</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Age</th>
                <th>Weight</th>
                <th>Wins</th>
                <th>Losses</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fighters as $fighter): ?>
                <tr>
                    <td><?= htmlspecialchars($fighter['name']) ?></td>
                    <td><?= htmlspecialchars($fighter['age']) ?></td>
                    <td><?= htmlspecialchars($fighter['weight']) ?> kg</td>
                    <td><?= htmlspecialchars($fighter['wins']) ?></td>
                    <td><?= htmlspecialchars($fighter['losses']) ?></td>
                    
                    <td>
                        <a href="edit_fighter.php?id=<?= $fighter['fighter_id'] ?>">Edit</a>
                        <a href="delete_fighter.php?delete=<?= $fighter['fighter_id'] ?>" onclick="return confirm('Are you sure you want to delete this fighter?');">Delete</a>
                    </td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
