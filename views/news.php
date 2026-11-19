<?php
require __DIR__ . '/../includes/app.php';

$category = input($_GET, 'category');
$search = input($_GET, 'search');
if (!in_array($category, NEWS_CATEGORIES, true)) {
    $category = '';
}
$pattern = '%' . $search . '%';
$args = [$category, $category, $pattern, $pattern];
$filter = " WHERE status = 'active' AND (? = '' OR category = ?) AND (title LIKE ? OR COALESCE(tags, '') LIKE ?)";
$count = (int) query('SELECT COUNT(*) FROM news' . $filter, $args)->fetchColumn();
$pageCount = max(1, (int) ceil($count / 9));
$page = max(1, min($pageCount, (int) input($_GET, 'page', '1')));
$offset = ($page - 1) * 9;
$stories = query('SELECT * FROM news' . $filter . " ORDER BY publish_date DESC, news_id DESC LIMIT 9 OFFSET $offset", $args)->fetchAll();

pageStart('News', 'News');
?>
<main class="shell section">
    <?php pageHeading($category === 'Highlights' ? 'Highlights' : 'MMA news', 'Fight coverage, announcements and highlights.'); ?>
    <form class="filters filter-form" method="get">
        <label>Search news<input type="search" name="search" value="<?= h($search) ?>" placeholder="Title or tag"></label>
        <label>Category<select name="category"><option value="">All categories</option>
            <?php foreach (NEWS_CATEGORIES as $name): ?><option <?= $category === $name ? 'selected' : '' ?>><?= h($name) ?></option><?php endforeach; ?>
        </select></label>
        <button class="button button-primary">Search</button>
        <?php if ($search !== '' || $category): ?><a class="button button-outline" href="<?= url('/views/news') ?>">Clear</a><?php endif; ?>
    </form>
    <?php if ($count): ?><p class="results-count"><?= $count ?> <?= $count === 1 ? 'article' : 'articles' ?></p><?php endif; ?>
    <div class="cards"><?php foreach ($stories as $story) storyCard($story); ?></div>
    <?php if (!$stories): ?><p class="empty">No stories match these filters.</p><?php endif; ?>
    <?php pagination($page, $pageCount, ['search' => $search, 'category' => $category], 'News pages'); ?>
</main>
<?php pageEnd(); ?>
