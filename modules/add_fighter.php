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

// Fetch weight classes for the dropdown
$weight_classes = $conn->query("SELECT weight_class_id, name FROM weight_classes")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve input values
    $name = $_POST['name'];
    $nickname = htmlspecialchars($_POST['nickname']);
    $birthdate = $_POST['birthdate'];
    $age = (int)$_POST['age'];
    $height = (float)$_POST['height'];
    $weight = (float)$_POST['weight'];
    $nationality = $_POST['nationality'];
    $wins = (int)$_POST['wins'];
    $losses = (int)$_POST['losses'];
    $draws = (int)$_POST['draws'];
    $weight_class_id = (int)$_POST['weight_class_id'];
    $ranking = (int)$_POST['ranking'];


    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photoPath = 'uploads/' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
    } else {
        $photoPath = NULL;
    }

    

    $slug = strtolower(str_replace(' ', '-', $name));

    // Create a new file for the fighter profile that includes fighter-profile.php with the slug parameter
    $profilePageContent = "<?php\n";
    $profilePageContent .= "\$fighter_slug = '$slug';\n";
    $profilePageContent .= "include '../templates/fighter-profile.php';\n";

    file_put_contents("../athletes/$slug.php", $profilePageContent);

    

    

   
    $stmt = $conn->prepare("INSERT INTO fighters 
        (name, nickname, birthdate, age, height, weight, nationality, photo, wins, losses, draws, slug, weight_class_id, ranking) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiidssiiisii", 
        $name, $nickname, $birthdate, $age, $height, $weight, $nationality, $photoPath, $wins, $losses, $draws, $slug, $weight_class_id, $ranking);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" href="../assets/css/styles.css">

<head><title>Add Fighter</title></head>
<body>
    <form method="POST" action="add_fighter.php" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Name" required>
        <input type="text" name="nickname" placeholder="Nickname" required>
        <input type="date" name="birthdate" placeholder="Birthdate" required>
        <input type="number" name="age" placeholder="Age" required>
        <input type="number" step="0.01" name="height" placeholder="Height (e.g., 1.80)" required>
        <input type="number" step="0.1" name="weight" placeholder="Weight (e.g., 70.5)" required>
        <input type="text" name="nationality" placeholder="Nationality" required>
        <input type="number" name="wins" placeholder="Wins" required>
        <input type="number" name="losses" placeholder="Losses" required>
        <input type="number" name="draws" placeholder="Draws" required>
        <input type="file" name="photo" accept="image/*" required>

        <label for="weight_class_id">Weight Class:</label>
        <select name="weight_class_id" required>
            <option value="">Select a Weight Class</option>
            <?php foreach ($weight_classes as $class): ?>
                <option value="<?= $class['weight_class_id']; ?>"><?= htmlspecialchars($class['name']); ?></option>
            <?php endforeach; ?>
        </select>

        <input type="number" name="ranking" placeholder="Ranking" required>
        <button type="submit">Add Fighter</button>
    </form>
</body>
</html>
