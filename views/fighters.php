<?php
require __DIR__ . '/../includes/app.php';

$search = input($_GET, 'search');
$division = (int) input($_GET, 'division');
$classes = query('SELECT * FROM weight_classes ORDER BY weight_class_id')->fetchAll();
$pattern = likePattern($search);
$args = [$division, $division, $pattern, $pattern];
$filter = " WHERE (? = 0 OR f.weight_class_id = ?) AND (f.name LIKE ? OR COALESCE(f.nickname, '') LIKE ?)";
$count = (int) query('SELECT COUNT(*) FROM fighters f' . $filter, $args)->fetchColumn();
$pageSize = 16;
$pageCount = max(1, (int) ceil($count / $pageSize));
$page = max(1, min($pageCount, (int) input($_GET, 'page', '1')));
$offset = ($page - 1) * $pageSize;
$fighters = query('SELECT f.*, w.name AS class_name FROM fighters f LEFT JOIN weight_classes w USING (weight_class_id)' . $filter .
                  " ORDER BY f.ranking IS NULL, f.ranking, f.name, f.fighter_id LIMIT $pageSize OFFSET $offset", $args)->fetchAll();
pageStart('Fighters', 'Fighters');
?>
<main class="shell section">
    <?php pageHeading('Fighter roster', 'Find athletes by name, nickname or weight class.'); ?>
    <form class="filters filter-form" method="get">
        <label>Search fighters<input type="search" name="search" value="<?= h($search) ?>" placeholder="Name or nickname"></label>
        <label>Weight class<select name="division"><option value="0">All weight classes</option>
            <?php foreach ($classes as $class): ?><option value="<?= (int) $class['weight_class_id'] ?>" <?= $division === (int) $class['weight_class_id'] ? 'selected' : '' ?>><?= h($class['name']) ?></option><?php endforeach; ?>
        </select></label>
        <button class="button button-primary">Search</button>
        <?php if ($search !== '' || $division): ?><a class="button button-outline" href="<?= url('/views/fighters') ?>">Clear</a><?php endif; ?>
    </form>
    <?php if ($count): ?><p class="results-count"><?= $count ?> <?= $count === 1 ? 'fighter' : 'fighters' ?></p><?php endif; ?>
    <div class="cards cards-four"><?php foreach ($fighters as $fighter) fighterCard($fighter); ?></div>
    <?php if (!$fighters): ?><p class="empty">No fighters match these filters.</p><?php endif; ?>
    <?php pagination($page, $pageCount, ['search' => $search, 'division' => $division], 'Fighter pages'); ?>
</main>
<?php pageEnd(); ?>
