<?php
require_once __DIR__ . '/../includes/app.php';

$slug = $fighter_slug ?? input($_GET, 'slug');
$fighter = query('SELECT f.*, w.name AS class_name FROM fighters f LEFT JOIN weight_classes w USING (weight_class_id)
                  WHERE f.slug IN (?, ?) ORDER BY (f.slug = ?) DESC, f.fighter_id LIMIT 1',
    [$slug, 'demo-' . $slug, $slug])->fetch();
if (!$fighter) {
    showError(404, 'Fighter not found.');
}
canonicalizeDetailUrl('fighter', $fighter);
$id = (int) $fighter['fighter_id'];
$eventBouts = query('SELECT e.slug, e.title, e.status, e.event_date, ef.result, ef.fighter1_id,
                            a.slug AS first_slug, a.name AS first_name, b.slug AS second_slug, b.name AS second_name
                     FROM event_fights ef JOIN events e USING (event_id)
                     JOIN fighters a ON a.fighter_id = ef.fighter1_id JOIN fighters b ON b.fighter_id = ef.fighter2_id
                     WHERE ef.fighter1_id = ? OR ef.fighter2_id = ?', [$id, $id])->fetchAll();
$recordedBouts = query("SELECT e.slug, COALESCE(e.title, f.location, 'Recorded fight') AS title, 'Past' AS status,
                              f.date AS event_date, f.result, f.fighter1_id, a.slug AS first_slug, a.name AS first_name,
                              b.slug AS second_slug, b.name AS second_name
                       FROM fights f LEFT JOIN events e USING (event_id)
                       JOIN fighters a ON a.fighter_id = f.fighter1_id JOIN fighters b ON b.fighter_id = f.fighter2_id
                       WHERE f.fighter1_id = ? OR f.fighter2_id = ?", [$id, $id])->fetchAll();
$bouts = array_merge($eventBouts, $recordedBouts);
usort($bouts, static fn ($a, $b) => strcmp((string) $b['event_date'], (string) $a['event_date']));
$photo = media($fighter['photo'], '');
$birth = !empty($fighter['birthdate']) && $fighter['birthdate'] !== '0000-00-00' ? new DateTimeImmutable($fighter['birthdate']) : null;
$stats = [
    'Age' => $birth ? $birth->diff(new DateTimeImmutable('today'))->y : null,
    'Weight' => $fighter['weight'] ? $fighter['weight'] . ' kg' : null,
    'Height' => $fighter['height'] && (float) $fighter['height'] < 3 ? $fighter['height'] . ' m' : null,
    'Division' => isset($fighter['class_name']) ? $fighter['class_name'] . (is_numeric($fighter['class_name']) ? ' kg' : '') : null,
];

pageStart($fighter['name'], 'Fighters');
?>
<main class="shell section">
    <a class="back-link" href="<?= url('/views/fighters') ?>">All fighters</a>
    <header class="panel profile-hero">
        <div class="fighter-art">
            <?php if ($photo): ?><img src="<?= h($photo) ?>" alt="">
            <?php else: ?><span class="fighter-initials" aria-hidden="true"><?= h(fighterInitials($fighter['name'])) ?></span><?php endif; ?>
        </div>
        <div class="detail-copy">
            <?php if ($photo && str_starts_with($fighter['slug'], 'demo-')): ?><p class="eyebrow">Illustrative photo</p>
            <?php elseif ($fighter['nationality']): ?><p class="eyebrow"><?= h($fighter['nationality']) ?></p><?php endif; ?>
            <h1><?= h($fighter['name']) ?></h1>
            <?php if ($fighter['nickname']): ?><p>“<?= h($fighter['nickname']) ?>”</p><?php endif; ?>
            <div class="record profile-record"><?= h(record($fighter)) ?> <small>W–L–D</small></div>
            <dl class="profile-stats"><?php foreach ($stats as $name => $value): ?>
                <?php if ($value !== null): ?><div><dt><?= h($name) ?></dt><dd><?= h($value) ?></dd></div><?php endif; ?>
            <?php endforeach; ?></dl>
        </div>
    </header>
    <section class="list-section">
        <h2>Fight history &amp; schedule</h2>
        <?php if (!$bouts): ?><p class="empty">No fights are listed for this athlete yet.</p><?php else: ?>
        <div class="panel table-wrap"><table>
            <thead><tr><th>Event</th><th>Opponent</th><th>Date</th><th>Result</th></tr></thead>
            <tbody>
            <?php foreach ($bouts as $bout): ?>
                <?php $first = (int) $bout['fighter1_id'] === $id; $opponent = $first ? 'second' : 'first'; ?>
                <tr>
                    <td><?php if ($bout['slug']): ?><a href="<?= detailUrl('event', $bout) ?>"><?= h($bout['title']) ?></a><?php else: ?><?= h($bout['title']) ?><?php endif; ?></td>
                    <td><a href="<?= detailUrl('fighter', ['slug' => $bout[$opponent . '_slug']]) ?>"><?= h($bout[$opponent . '_name']) ?></a></td>
                    <td><?= h(dateLabel($bout['event_date'])) ?></td>
                    <td><?php outcomeBadge(boutOutcome($bout['result'], $first, eventState($bout))); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>
</main>
<?php pageEnd(); ?>
