<?php
// ჩართეთ ბაზის კავშირის ფაილი და დაიწყეთ სესია
include __DIR__ . '/../config/db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // მომხმარებლის მონაცემების მიღება ბაზიდან
    $stmt = $pdo->prepare("SELECT user_id, username, password, is_admin FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // მომხმარებლის არსებობისა და პაროლის გადამოწმება
    if ($user && password_verify($password, $user['password'])) {
        // სესიის ცვლადებში მომხმარებლის მონაცემების შენახვა
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        // მომხმარებლის გადამისამართება მთავარ გვერდზე
        header("Location: index.php");
        exit;
    } else {
        // შეცდომის შეტყობინება არასწორი მონაცემების შემთხვევაში
        $error = "Incorrect email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php __DIR__ . '/../includes/navbar.php'; ?>
    <h1>Login</h1>
    
    <?php
    // შეცდომის გამოტანა, თუ მონაცემები არასწორია
    if (isset($error)) {
        echo "<p style='color: red;'>$error</p>";
    }
    ?>

    <form method="POST" action="login.php">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</body>
<?php include '../includes/footer.php'; ?>
</html>
