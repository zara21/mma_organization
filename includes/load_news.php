<?php
require __DIR__ . '/../includes/app.php';
$offset = max(0, (int) input($_POST, 'offset'));
foreach (query("SELECT * FROM news WHERE status = 'active' ORDER BY publish_date DESC, news_id DESC LIMIT 10 OFFSET $offset") as $story) {
    storyCard($story);
}
