<?php include '../includes/db_connect.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <h1>Your Profile</h1>
    <?php
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            echo "<p>Username: " . htmlspecialchars($user['username']) . "</p>";
            echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
        } else {
            echo "You are not logged in.";
        }
    ?>
</body>
<?php include '../includes/footer.php'; ?>
</html>


