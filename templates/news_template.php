<?php
require_once __DIR__ . '/../includes/app.php';

$slug = $nslug ?? input($_GET, 'slug');
$story = query("SELECT * FROM news WHERE slug IN (?, ?) AND status = 'active'
                ORDER BY (slug = ?) DESC LIMIT 1", [$slug, 'demo-' . $slug, $slug])->fetch();
if (!$story) {
    showError(404, 'Article not found.');
}
canonicalizeDetailUrl('article', $story);
$photo = media($story['image_url'], 'news_images');
$video = httpLink($story['video_url']);
$embed = null;
if ($video) {
    $host = strtolower((string) parse_url($video, PHP_URL_HOST));
    $path = (string) parse_url($video, PHP_URL_PATH);
    parse_str((string) parse_url($video, PHP_URL_QUERY), $parameters);
    $id = match ($host) {
        'youtu.be', 'www.youtu.be' => trim($path, '/'),
        'youtube.com', 'www.youtube.com', 'm.youtube.com' => $path === '/watch' ? input($parameters, 'v') : basename($path),
        default => '',
    };
    if (preg_match('/^[A-Za-z0-9_-]{11}$/D', $id)) {
        $embed = 'https://www.youtube-nocookie.com/embed/' . $id;
    }
}

pageStart($story['title'], 'News');
?>
<main class="shell section article-shell">
    <a class="back-link" href="<?= url('/views/news') ?>">All news</a>
    <article class="panel article">
        <header class="article-head">
            <p class="eyebrow"><?= h($story['category']) ?></p>
            <h1><?= h($story['title']) ?></h1>
            <p class="muted"><?= h(dateLabel($story['publish_date'])) ?><?= $story['author'] ? ' · ' . h($story['author']) : '' ?></p>
        </header>
        <?php if ($photo): ?><img class="article-image" src="<?= h($photo) ?>" alt=""><?php endif; ?>
        <div class="article-body">
            <div class="article-text"><?= h($story['content']) ?></div>
            <?php if ($embed): ?><iframe class="video-frame" title="<?= h($story['title']) ?> video" src="<?= h($embed) ?>" loading="lazy" allowfullscreen></iframe>
            <?php elseif ($video): ?><p><a class="button button-primary" href="<?= h($video) ?>">Watch video</a></p><?php endif; ?>
        </div>
    </article>
</main>
<?php pageEnd(); ?>
