<?php
// სესიის შემოწმება და დაწყება, თუ ჯერ არ დაწყებულა
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>


<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../css/styles.css">

</head>
<body>

<nav>

    <div class="logo-in-nav">
        <a href="/mma_organization/index.php"> <img src="/mma_organization/assets/uploads/icon/UFC_Logo.png" alt="Menu" class="logo-in-nav-img" /> </a>
    </div>

    <div class="nav-links">
        <a href="/mma_organization/index.php">Home</a>
        <a href="/mma_organization/views/ranking.php">Ranking</a>
        <a href="/mma_organization/views/news.php">News</a>
        <a href="/mma_organization/views/fighters.php">Fighters</a>
        <a href="/mma_organization/views/events.php">Events</a>
        <a href="/mma_organization/views/about.php">About us</a>

    </div>

    <div class="shoe-box">
        <img src="/mma_organization/assets/uploads/icon/user.svg" alt="Menu" class="menu-image" onclick="openBox()" />
        <div class="nav-box" id="box">
            <?php if (isset($_SESSION['user_id'])): ?>
                <p class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <div class="user-info">
                    <?php
                        if (isset($_SESSION['user_id'])) {
                            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user = $stmt->fetch();

                            echo "<p>Username: " . htmlspecialchars($user['username']) . "</p>";
                            echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
                        }
                    ?>
                </div>

                <?php if ($_SESSION['is_admin']): ?>
                    <a href="/mma_organization/views/admin.php" class="nav-link">Admin Panel</a>
                <?php endif; ?>

                <a href="/mma_organization/routes/logout.php" class="nav-link logout">Logout</a>
            <?php else: ?>
                <div class="auth-links">
                    <a href="/mma_organization/routes/login.php" class="nav-link">Login</a>
                    <a href="/mma_organization/views/register.php" class="nav-link">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function openBox() {
            const box = document.getElementById('box');
            if (box.style.display === 'block') {
                box.style.display = 'none'; // Hide the box
            } else {
                box.style.display = 'block'; // Show the box
            }
        }

    </script>
</nav>

</body>
</html>

