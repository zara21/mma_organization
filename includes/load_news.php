<?php
// Include database connection
include __DIR__ . '/../config/db_connect.php';

// Get offset from request
$offset = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;

// Query to fetch 10 news items from the database starting at the offset
$query = "SELECT * FROM news WHERE status = 'active' ORDER BY publish_date DESC LIMIT 10 OFFSET $offset";
$result = mysqli_query($conn, $query);

// Check for errors
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch results and display them
while ($row = mysqli_fetch_assoc($result)) {
    echo '<div class="news-item">';
    if (!empty($row['image_url'])) {
        echo '<img src="/mma_organization/assets/uploads/news_images/' . htmlspecialchars($row['image_url']) . '" alt="News Image" class="news-image">';
    }
    echo '<div class="news-details">';
    echo '<h2 class="news-title">';
    echo '<a href="/mma_organization/news/' . htmlspecialchars($row['slug']) . '.php">' . htmlspecialchars($row['title']) . '</a>';
    echo '</h2>';
    echo '<p class="news-content">' . htmlspecialchars(substr($row['content'], 0, 150)) . '...</p>';
    echo '<p class="news-time"><strong>Published:</strong> ' . date('d M Y', strtotime($row['publish_date'])) . '</p>';
    echo '</div>';
    echo '</div>';
}

// Close database connection
mysqli_close($conn);
?>
