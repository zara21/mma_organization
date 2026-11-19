<?php
require __DIR__ . '/includes/app.php';

$events = query('SELECT * FROM events ORDER BY event_date DESC, event_id DESC')->fetchAll();
$upcoming = array_values(array_reverse(array_filter($events, static fn ($event) => eventState($event) === 'Upcoming')));
$live = array_values(array_filter($events, static fn ($event) => eventState($event) === 'Live'));
$featured = $live[0] ?? $upcoming[0] ?? $events[0] ?? null;
$schedule = array_slice(array_filter($upcoming, static fn ($event) => $event['event_id'] !== $featured['event_id']), 0, 3);
$fighters = query('SELECT f.*, w.name AS class_name FROM fighters f LEFT JOIN weight_classes w USING (weight_class_id)
                   ORDER BY f.ranking IS NULL, f.ranking, f.fighter_id DESC LIMIT 4')->fetchAll();
$stories = query("SELECT * FROM news WHERE status = 'active' AND category <> 'Highlights' ORDER BY is_featured DESC, publish_date DESC, news_id DESC LIMIT 3")->fetchAll();
$highlights = query("SELECT * FROM news WHERE status = 'active' AND category = 'Highlights' ORDER BY publish_date DESC, news_id DESC LIMIT 3")->fetchAll();
$state = $featured ? eventState($featured) : '';
$banner = $featured ? media($featured['banner'], 'banners') : null;
$ticket = $state === 'Upcoming' ? httpLink($featured['ticket_link']) : null;
$title = $featured ? ($featured['e_name'] ?: $featured['title']) : 'MMA Organization';
$featureTitle = explode(' | ', $title, 2);
$headline = preg_split('/\s+vs\.?\s+/i', $featureTitle[1] ?? $title, 2);

pageStart('Home', 'Home');
?>
<main class="home">
    <section class="hero<?= $banner ? ' has-art' : '' ?>">
        <div class="hero-inner">
            <?php if ($featured): ?>
                <div class="hero-kicker">
                    <span class="hero-status<?= $state === 'Live' ? ' is-live' : '' ?>"><?= h(match ($state) { 'Upcoming' => 'Next event', 'Live' => 'Live event', default => 'From the archive' }) ?></span>
                    <?php if (isset($featureTitle[1])): ?><p><?= h($featureTitle[0]) ?></p><?php endif; ?>
                </div>
            <?php endif; ?>
            <h1><?= h($headline[0]) ?><?php if (isset($headline[1])): ?> <span><small>vs</small> <?= h($headline[1]) ?></span><?php endif; ?></h1>
            <p class="hero-meta"><?= h($featured ? dateLabel($featured['event_date'], 'l, F j, Y · H:i') : 'Fight cards, rankings and the latest from the world of MMA.') ?><?php if ($featured && $featured['event_date']): ?> <span><?= h(dateLabel($featured['event_date'], 'T')) ?></span><?php endif; ?></p>
            <?php if ($featured && $state === 'Upcoming' && $featured['event_date']): ?>
                <div class="countdown" data-countdown="<?= strtotime($featured['event_date']) ?>" aria-label="Time until the event">
                    <?php foreach (['Days', 'Hours', 'Min', 'Sec'] as $unit): ?><div><strong>00</strong><small><?= $unit ?></small></div><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="hero-actions">
                <?php if ($featured): ?><a class="button button-primary" href="<?= detailUrl('event', $featured) ?>">View fight card</a><?php endif; ?>
                <?php if ($ticket): ?><a class="button button-outline" href="<?= h($ticket) ?>">Tickets</a><?php endif; ?>
                <a class="button <?= $featured ? 'button-outline' : 'button-primary' ?>" href="<?= url('/views/events') ?>">All events</a>
            </div>
        </div>
        <?php if ($banner): ?><div class="hero-art"><img src="<?= h($banner) ?>" alt="" fetchpriority="high"></div><?php endif; ?>
    </section>
    <?php if ($schedule): ?>
        <section class="home-section schedule">
            <div class="shell">
                <?php sectionTitle('On the calendar', '/views/events?status=Upcoming', 'Full schedule'); ?>
                <div class="schedule-grid">
                    <?php foreach ($schedule as $event): ?>
                        <a class="schedule-item panel" href="<?= detailUrl('event', $event) ?>">
                            <?php if ($event['event_date']): ?><time datetime="<?= h(dateLabel($event['event_date'], 'c')) ?>">
                                <span><?= h(dateLabel($event['event_date'], 'M')) ?></span>
                                <strong><?= h(dateLabel($event['event_date'], 'j')) ?></strong>
                            </time><?php endif; ?>
                            <div>
                                <h3><?= h($event['title']) ?></h3>
                                <p><?= h(dateLabel($event['event_date'], 'l · H:i T')) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <section class="shell home-section">
        <?php sectionTitle('Fighter roster', '/views/fighters', 'All fighters'); ?>
        <div class="cards cards-four"><?php foreach ($fighters as $fighter) fighterCard($fighter); ?></div>
        <?php if (!$fighters): ?><p class="empty">Fighters will appear here when added to the roster.</p><?php endif; ?>
    </section>
    <section class="shell home-section">
        <?php sectionTitle('Latest news', '/views/news', 'All news'); ?>
        <div class="cards"><?php foreach ($stories as $story) storyCard($story); ?></div>
        <?php if (!$stories): ?><p class="empty">No articles have been published yet.</p><?php endif; ?>
    </section>
    <?php if ($highlights): ?>
        <section class="shell home-section">
            <?php sectionTitle('Highlights', '/views/news?category=Highlights', 'All highlights'); ?>
            <div class="cards"><?php foreach ($highlights as $story) storyCard($story); ?></div>
        </section>
    <?php endif; ?>
</main>
<?php pageEnd(); ?>
