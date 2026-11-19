<?php
function pageStart(string $title, string $active = '', bool $admin = false): void
{
    $account = currentUser();
    $nav = ['Home' => '/', 'Events' => '/views/events', 'Fighters' => '/views/fighters', 'Rankings' => '/views/ranking', 'News' => '/views/news'];
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b0b0d">
    <title><?= h($title) ?> · MMA Organization</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,600;0,700;0,800;1,800&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= url('/assets/css/site.css') ?>?v=2">
    <?php if ($admin): ?><link rel="stylesheet" href="<?= url('/assets/css/control.css') ?>"><?php endif; ?>
    <script src="<?= url('/assets/js/site.js') ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#content">Skip to content</a>
<header class="topbar">
    <div class="shell topbar-inner">
        <a class="header-brand" href="<?= url() ?>" aria-label="MMA Organization home">
            <span class="brand-mark">MMA<i></i></span><span class="brand-name">Organization</span>
        </a>
        <nav id="site-navigation" class="main-nav" aria-label="Main navigation">
            <?php foreach ($nav as $name => $path): ?>
                <a href="<?= url($path) ?>"<?= $active === $name ? ' aria-current="page"' : '' ?>><span><?= h($name) ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="account">
            <?php if ($account): ?>
                <details class="account-disclosure">
                    <summary class="account-trigger">
                        <span class="account-avatar" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($account['username'], 0, 1))) ?></span>
                        <span class="account-name"><?= h($account['username']) ?></span>
                    </summary>
                    <div class="account-menu">
                        <p class="account-menu-name"><?= h($account['username']) ?></p>
                        <a href="<?= url('/views/profile') ?>">My account</a>
                        <?php if (isAdmin($account)): ?><a href="<?= url('/views/admin') ?>">Admin panel</a><?php endif; ?>
                        <form method="post" action="<?= url('/routes/logout') ?>"><?php csrfField(); ?><button type="submit">Sign out</button></form>
                    </div>
                </details>
            <?php else: ?>
                <div class="account-links">
                    <a class="button header-sign-in" href="<?= url('/routes/login') ?>"<?= $active === 'Account' ? ' aria-current="page"' : '' ?>>Sign in</a>
                    <a class="button button-primary" href="<?= url('/views/register') ?>"<?= $active === 'Register' ? ' aria-current="page"' : '' ?>>Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<div id="content">
    <?php
}

function pageEnd(): void
{
    ?>
</div>
<footer class="site-footer">
    <div class="shell footer-inner">
        <a class="wordmark" href="<?= url() ?>"><strong>MMA<span>.</span></strong><small>Organization</small></a>
        <nav aria-label="Footer"><a href="<?= url('/views/about') ?>">About</a><a href="<?= url('/views/news?category=Highlights') ?>">Highlights</a><a href="<?= url('/views/admin') ?>">Admin</a></nav>
        <small>© <?= date('Y') ?> MMA Organization</small>
    </div>
</footer>
</body>
</html>
    <?php
}

function pageHeading(string $title, string $description): void
{
    ?>
    <header class="page-heading panel"><h1><?= h($title) ?></h1><p><?= h($description) ?></p></header>
    <?php
}

function sectionTitle(string $title, string $path, string $linkLabel): void
{
    ?>
    <div class="section-head"><h2><?= h($title) ?></h2><a href="<?= url($path) ?>" class="text-link"><?= h($linkLabel) ?></a></div>
    <?php
}

function pagination(int $page, int $pages, array $filters, string $label): void
{
    if ($pages <= 1) {
        return;
    }
    $pageLink = static fn (int $number): string => h(http_build_query($filters + ['page' => $number]));
    ?>
    <nav class="filters pagination" aria-label="<?= h($label) ?>">
        <?php if ($page > 1): ?><a class="chip" href="?<?= $pageLink($page - 1) ?>">Previous</a><?php endif; ?>
        <span class="page-indicator" aria-current="page">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?><a class="chip" href="?<?= $pageLink($page + 1) ?>">Next</a><?php endif; ?>
    </nav>
    <?php
}

function badge(string $label, bool $accent = false): void
{
    echo '<span class="badge' . ($accent ? ' badge-accent' : '') . '">' . h($label) . '</span>';
}

function fighterInitials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
}

function outcomeBadge(string $outcome): void
{
    echo '<span class="outcome outcome-' . h(strtolower(str_replace(' ', '-', $outcome))) . '">' . h($outcome) . '</span>';
}

function fightMethodPicker(string $value, ?string $customText = null): void
{
    $isPreset = in_array($value, array_merge(...array_values(FIGHT_METHOD_GROUPS)), true);
    $isOther = $value !== '' && !$isPreset;
    $customText ??= $isOther && $value !== CUSTOM_FIGHT_METHOD ? $value : '';
    ?>
    <div class="fight-method-picker" data-fight-method-picker>
        <label>Method
            <select name="method" data-fight-method>
                <option value="" <?= $value === '' ? 'selected' : '' ?>>Not specified</option>
                <?php foreach (FIGHT_METHOD_GROUPS as $group => $methods): ?>
                    <optgroup label="<?= h($group) ?>">
                        <?php foreach ($methods as $method): ?>
                            <option value="<?= h($method) ?>" <?= $value === $method ? 'selected' : '' ?>><?= h($method) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
                <option value="<?= CUSTOM_FIGHT_METHOD ?>" <?= $isOther ? 'selected' : '' ?>>Other (write in)</option>
            </select>
        </label>
        <label data-other-method>Other method or result detail
            <input name="method_other" maxlength="50" value="<?= h($customText) ?>" placeholder="Describe how the bout ended">
        </label>
    </div>
    <?php
}

function fighterCard(array $fighter): void
{
    $photo = media($fighter['photo'], '');
    ?>
    <article class="fighter-card panel">
        <a href="<?= detailUrl('fighter', $fighter) ?>">
            <div class="fighter-art">
                <?php if ($photo): ?><img src="<?= h($photo) ?>" alt="" loading="lazy">
                <?php else: ?><span class="fighter-initials" aria-hidden="true"><?= h(fighterInitials((string) $fighter['name'])) ?></span><?php endif; ?>
                <?php if ($fighter['ranking'] !== null): ?><span class="rank-label<?= (int) $fighter['ranking'] === 0 ? ' is-champion' : '' ?>"><?= (int) $fighter['ranking'] === 0 ? 'Champion' : '#' . (int) $fighter['ranking'] ?></span><?php endif; ?>
            </div>
            <div class="card-copy">
                <p class="eyebrow"><?= h($fighter['nationality'] ?: ($fighter['class_name'] ?? 'MMA Fighter')) ?></p>
                <h3><?= h($fighter['name']) ?></h3>
                <div class="record"><?= h(record($fighter)) ?> <small>W–L–D</small></div>
            </div>
        </a>
    </article>
    <?php
}

function storyCard(array $story): void
{
    $photo = media($story['image_url'], 'news_images');
    ?>
    <article class="story-card panel">
        <a href="<?= detailUrl('article', $story) ?>">
            <div class="story-art"><?php if ($photo): ?><img src="<?= h($photo) ?>" alt="" loading="lazy"><?php endif; ?></div>
            <div class="card-copy">
                <p class="eyebrow"><?= h($story['category']) ?></p>
                <h3><?= h($story['title']) ?></h3>
                <p class="muted meta"><?= h(dateLabel($story['publish_date'])) ?></p>
            </div>
        </a>
    </article>
    <?php
}

function eventCard(array $event): void
{
    $photo = media($event['banner'], 'banners');
    $state = eventState($event);
    ?>
    <article class="event-card panel">
        <a href="<?= detailUrl('event', $event) ?>">
            <div class="story-art"><?php if ($photo): ?><img src="<?= h($photo) ?>" alt="" loading="lazy"><?php endif; ?></div>
            <div class="card-copy">
                <?php badge($state, $state !== 'Past'); ?>
                <h3><?= h($event['e_name'] ?: $event['title']) ?></h3>
                <p class="muted meta"><?= h(dateLabel($event['event_date'], 'D, M j, Y · H:i')) ?></p>
            </div>
        </a>
    </article>
    <?php
}
