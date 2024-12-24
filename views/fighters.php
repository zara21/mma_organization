<?php
include __DIR__ . '/../config/db_connect.php';

$searchTerm = '';

// Check if a search query was submitted
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $query = "SELECT *, TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age FROM fighters WHERE name LIKE ? OR nickname LIKE ?";
    $stmt = $conn->prepare($query);
    $searchWildcard = '%' . $searchTerm . '%';
    $stmt->bind_param("ss", $searchWildcard, $searchWildcard);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT *, TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age FROM fighters";
    $result = $conn->query($query);
}
?>

<?php include '../includes/header.php'; ?>

<link rel="stylesheet" href="../css/fighters.css">

<h1 class="page-title">MMA Fighters</h1>

<!-- Search Form -->
<form method="GET" action="fighters.php" class="search-form">
    <div class="search"> <input type="text" name="search" placeholder="Search fighters by name or nickname" value="<?= htmlspecialchars($searchTerm) ?>"></div>
    <div class="button"> <button type="submit">Search</button></div>
    
</form>

<!-- Fighters Container -->
<div class="fighters-container">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($fighter = $result->fetch_assoc()): ?>
            <div class="fighter-card">
                <div class="fighter-photo">
                    <img src="../assets/<?= !empty($fighter['photo']) ? htmlspecialchars($fighter['photo']) : '../images/default.jpg' ?>" alt="<?= htmlspecialchars($fighter['name']) ?>">
                </div>
                <div class="fighter-info">
                    <h2>
                        <a href="../athletes/<?= htmlspecialchars($fighter['slug']) ?>.php"><?= htmlspecialchars($fighter['name']) ?></a>
                    </h2>
                    <p><strong>Nickname:</strong> <?= htmlspecialchars($fighter['nickname']) ?></p>
                    <p><strong>Age:</strong> <?= htmlspecialchars($fighter['age']) ?> years</p>
                    <p><strong>Weight:</strong> <?= htmlspecialchars($fighter['weight']) ?> kg</p>
                    <p><strong>Wins:</strong> <?= htmlspecialchars($fighter['wins']) ?></p>
                    <p><strong>Losses:</strong> <?= htmlspecialchars($fighter['losses']) ?></p>
                    <a class="view-profile" href="../athletes/<?= htmlspecialchars($fighter['slug']) ?>.php">View Profile</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-results">No fighters found for "<?= htmlspecialchars($searchTerm) ?>".</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
