<?php
require_once __DIR__ . '/../includes/app.php';

$classes = query('SELECT * FROM weight_classes ORDER BY weight_class_id')->fetchAll();
$ranked = query('SELECT * FROM fighters WHERE ranking BETWEEN 0 AND 10 AND weight_class_id IS NOT NULL
                 ORDER BY ranking, name, fighter_id')->fetchAll();
$groups = [];
foreach ($ranked as $fighter) {
    $groups[$fighter['weight_class_id']][] = $fighter;
}
pageStart('Rankings', 'Rankings');
?>
<main class="shell section">
    <?php pageHeading('Rankings', 'Champions and positions #1–#10 in each weight class.'); ?>
    <div class="ranking-grid">
        <?php foreach ($classes as $class): ?>
            <?php $fighters = $groups[$class['weight_class_id']] ?? []; if (!$fighters) continue; ?>
            <section class="panel rank-panel">
                <h2><?= h($class['name']) ?><?= is_numeric($class['name']) ? ' kg division' : '' ?></h2>
                <ul class="rank-list">
                    <?php foreach ($fighters as $fighter): ?>
                        <?php $photo = media($fighter['photo'], ''); ?>
                        <li>
                            <?php if ((int) $fighter['ranking'] === 0): ?>
                                <span class="rank-position champion" aria-label="Champion" title="Champion">C</span>
                            <?php else: ?>
                                <span class="rank-position">#<?= (int) $fighter['ranking'] ?></span>
                            <?php endif; ?>
                            <a class="rank-fighter" href="<?= detailUrl('fighter', $fighter) ?>">
                                <span class="rank-portrait" aria-hidden="true">
                                    <?php if ($photo): ?>
                                        <img src="<?= h($photo) ?>" alt="" loading="lazy" width="44" height="44">
                                    <?php else: ?>
                                        <?= h(fighterInitials((string) $fighter['name'])) ?>
                                    <?php endif; ?>
                                </span>
                                <span><?= h($fighter['name']) ?></span>
                            </a>
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
