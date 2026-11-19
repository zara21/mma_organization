<?php
require_once __DIR__ . '/../includes/app.php';

$slug = $eslug ?? input($_GET, 'slug');
$event = query('SELECT * FROM events WHERE slug IN (?, ?) ORDER BY (slug = ?) DESC LIMIT 1',
    [$slug, 'demo-' . $slug, $slug])->fetch();
if (!$event) {
    showError(404, 'Event not found.');
}
canonicalizeDetailUrl('event', $event);
$state = eventState($event);
$title = $event['e_name'] ?: $event['title'];
$banner = media($event['banner'], 'banners');
$ticket = $state === 'Upcoming' ? httpLink($event['ticket_link']) : null;
$extra = httpLink($event['additional_link']);
$fights = eventFights((int) $event['event_id']);
$mainFirst = (int) $event['main_fighter1_id'];
$mainSecond = (int) $event['main_fighter2_id'];
$isMain = static fn (array $fight): bool =>
    [(int) $fight['fighter1_id'], (int) $fight['fighter2_id']] === [$mainFirst, $mainSecond] ||
    [(int) $fight['fighter2_id'], (int) $fight['fighter1_id']] === [$mainFirst, $mainSecond];
if ($mainFirst !== $mainSecond && !array_filter($fights, $isMain)) {
    $main = query('SELECT a.name AS first_name, a.slug AS first_slug, a.photo AS first_photo, a.fighter_id AS fighter1_id,
                          b.name AS second_name, b.slug AS second_slug, b.photo AS second_photo, b.fighter_id AS fighter2_id
                   FROM fighters a CROSS JOIN fighters b WHERE a.fighter_id = ? AND b.fighter_id = ?', [$mainFirst, $mainSecond])->fetch();
    if ($main) {
        $main['result'] = 'Pending';
        array_unshift($fights, $main);
    }
}
usort($fights, static fn ($a, $b) => $isMain($b) <=> $isMain($a));

pageStart($title, 'Events');
?>
<main class="shell section">
    <a class="back-link" href="<?= url('/views/events') ?>">All events</a>
    <header class="detail-hero panel">
        <?php if ($banner): ?><img class="hero-image" src="<?= h($banner) ?>" alt=""><?php endif; ?>
        <div class="detail-copy">
            <?php badge($state, $state !== 'Past'); ?>
            <h1><?= h($title) ?></h1><p><?= h(dateLabel($event['event_date'], 'l, F j, Y · H:i T')) ?></p>
            <div class="hero-actions">
                <?php if ($ticket): ?><a class="button button-primary" href="<?= h($ticket) ?>">Tickets</a><?php endif; ?>
                <?php if ($extra): ?><a class="button button-outline" href="<?= h($extra) ?>">Event information</a><?php endif; ?>
            </div>
        </div>
    </header>
    <section class="list-section">
        <h2>Fight card</h2>
        <?php if (!$fights): ?><p class="empty">No fights have been added to this event.</p><?php endif; ?>
        <div class="bout-list">
            <?php foreach ($fights as $fight): ?>
                <article class="panel bout">
                    <?php if ($isMain($fight)): ?><p class="eyebrow bout-label">Main event</p><?php endif; ?>
                    <?php foreach (['first', 'second'] as $side): ?>
                        <?php if ($side === 'second'): ?><span class="versus">VS</span><?php endif; ?>
                        <a class="bout-fighter" href="<?= detailUrl('fighter', ['slug' => $fight[$side . '_slug']]) ?>">
                            <?php if ($photo = media($fight[$side . '_photo'], '')): ?><img src="<?= h($photo) ?>" alt="" loading="lazy"><?php endif; ?>
                            <span><strong><?= h($fight[$side . '_name']) ?></strong>
                                <?php outcomeBadge(boutOutcome($fight['result'], $side === 'first', $state)); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php pageEnd(); ?>
