<?php
// მონაცემთა ბაზის ჩართვა
include __DIR__ . '/../config/db_connect.php';

// მიიღეთ slug ფაილიდან
$fslug = isset($nslug) ? $nslug : '';

if (empty($fslug)) {
    die("<div class='error'>Invalid news slug provided.</div>");
}

// SQL-კითხვა სიახლის წამოსაღებად
$sql = "SELECT * FROM news WHERE slug = ? AND status = 'active'";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $fslug);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $news = $result->fetch_assoc();
    } else {
        die("<div class='error'>სიახლე ვერ მოიძებნა.</div>");
    }
    $stmt->close();
} else {
    die("<div class='error'>შეცდომა: " . $conn->error . "</div>");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/news.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="news-container">
        <?php if (!empty($news['image_url'])): ?>
            <div class="news-image">
                <img src="../assets/uploads/news_images/<?php echo htmlspecialchars($news['image_url']); ?>" alt="News Image">
            </div>
        <?php endif; ?>

        <div class="news-content">
            <h1><?php echo htmlspecialchars($news['title']); ?></h1>
            <p class="news-meta">
                <span>Published: <?php echo date("d-m-Y H:i", strtotime($news['publish_date'])); ?></span>
            </p>

            <div class="news-body">
                <?php echo nl2br(htmlspecialchars($news['content'])); ?>
            </div>

            <?php if (!empty($news['video_url'])): ?>
                <div class="news-video">
                    <?php
                    $videoUrl = $news['video_url'];
                    if (strpos($videoUrl, 'watch?v=') !== false) {
                        $videoUrl = str_replace('watch?v=', 'embed/', $videoUrl);
                    } elseif (strpos($videoUrl, 'youtu.be/') !== false) {
                        $videoUrl = str_replace('youtu.be/', 'youtube.com/embed/', $videoUrl);
                    }
                    ?>
                    <iframe width="100%" height="315" src="<?php echo htmlspecialchars($videoUrl); ?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
