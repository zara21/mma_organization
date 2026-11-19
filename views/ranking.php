<?php
require __DIR__ . '/../includes/app.php';

$classes = query('SELECT * FROM weight_classes ORDER BY weight_class_id')->fetchAll();
$ranked = query('SELECT * FROM fighters WHERE ranking IS NOT NULL AND weight_class_id IS NOT NULL ORDER BY ranking, name')->fetchAll();
$groups = [];
foreach ($ranked as $fighter) {
    $groups[$fighter['weight_class_id']][] = $fighter;
}
pageStart('Rankings', 'Rankings');
?>
<main class="shell section">
    <?php pageHeading('Rankings', 'Champions and ranked fighters by weight class.'); ?>
    <div class="ranking-grid">
        <?php foreach ($classes as $class): ?>
            <?php $fighters = $groups[$class['weight_class_id']] ?? []; if (!$fighters) continue; ?>
            <section class="panel rank-panel">
                <h2><?= h($class['name']) ?><?= is_numeric($class['name']) ? ' kg division' : '' ?></h2>
                <ul class="rank-list">
                    <?php foreach ($fighters as $fighter): ?>
                        <li>
                            <?php if ((int) $fighter['ranking'] === 0): ?>
                                <span class="rank-position champion" aria-label="Champion" title="Champion">C</span>
                            <?php else: ?>
                                <span class="rank-position">#<?= (int) $fighter['ranking'] ?></span>
                            <?php endif; ?>
                            <a href="<?= detailUrl('fighter', $fighter) ?>"><?= h($fighter['name']) ?></a>
                            <span class="record"><?= h(record($fighter)) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
    <?php if (!$ranked): ?><p class="empty">Rankings have yet to be published.</p><?php endif; ?>
</main>
<?php pageEnd(); ?>
