<?php
require __DIR__ . '/../includes/app.php';

$filter = input($_GET, 'status');
if (!in_array($filter, EVENT_STATES, true)) {
    $filter = '';
}
$events = query('SELECT * FROM events ORDER BY event_date DESC, event_id DESC')->fetchAll();
$groups = array_fill_keys(['Live', 'Upcoming', 'Past'], []);
foreach ($events as $event) {
    $groups[eventState($event)][] = $event;
}
$groups['Upcoming'] = array_reverse($groups['Upcoming']);
pageStart('Events', 'Events');
?>
<main class="shell section">
    <?php pageHeading('Events', 'Explore the schedule, fight cards and results.'); ?>
    <nav class="filters" aria-label="Event status">
        <?php foreach (['' => 'All events', 'Live' => 'Live', 'Upcoming' => 'Upcoming', 'Past' => 'Past events'] as $key => $label): ?>
            <a class="chip<?= $filter === $key ? ' selected' : '' ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?> href="<?= url('/views/events' . ($key ? '?status=' . $key : '')) ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php foreach ($groups as $state => $rows): ?>
        <?php if (($filter && $filter !== $state) || (!$filter && $state === 'Live' && !$rows)) continue; ?>
        <section class="list-section"><h2><?= $state === 'Past' ? 'Past events' : h($state) ?></h2>
            <?php if (!$rows): ?><p class="empty">No <?= strtolower($state) ?> events.</p><?php endif; ?>
            <div class="cards"><?php foreach ($rows as $event) eventCard($event); ?></div>
        </section>
    <?php endforeach; ?>
</main>
<?php pageEnd(); ?>
