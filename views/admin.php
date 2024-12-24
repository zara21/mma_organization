<?php 
include __DIR__ . '/../config/db_connect.php';
include __DIR__ . '/../includes/navbar.php'; 

// Only allow access to administrators
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit;
} elseif ($_SESSION['is_admin'] !== 1) {
    // Redirect if the user is not an administrator
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - MMA Organization</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css"> <!-- დამატებითი სტილები -->
</head>
<body>
    <div class="admin-container">
        <h1>Admin Panel</h1>
        <p>Welcome to the admin panel. Select an action below:</p>
        
        <div class="admin-actions">
            <a href="../modules/add_event.php" class="admin-btn">Add Event</a>
            <a href="../modules/edit_event.php" class="admin-btn">Edit Event</a>
            <a href="../modules/delete_event.php" class="admin-btn">Delete Event</a>
            
            <a href="../modules/add_fighter.php" class="admin-btn">Add Fighter</a>
            <a href="../modules/edit_fighter.php" class="admin-btn">Edit Fighter</a>
            <a href="../modules/delete_fighter.php" class="admin-btn">Delete Fighter</a>

            <a href="../modules/add_news.php" class="admin-btn">Add News</a>
            <a href="../views/news.php" class="admin-btn">Manage News</a>
            
            <a href="../modules/add_weight_class.php" class="admin-btn">Add Weight Class</a>
            <a href="../modules/update_ranking.php" class="admin-btn">Update Ranking</a>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>