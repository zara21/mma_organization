<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../includes/app.php';
require APP_ROOT . '/includes/admin.php';

$divisions = [
    ['Flyweight', 56.7, ['Luka Vardeli', 'Mateo Rivas', 'Kenji Mori', 'Arman Soren', 'Adrian Vega', 'Noah Kade']],
    ['Bantamweight', 61.2, ['Giorgi Lomidari', 'Rafael Costa', 'Sami Nassar', 'Dario Vale', 'Mika Tanaka', 'Levan Kordza']],
    ['Featherweight', 65.8, ['Nika Bereli', 'Diego Salas', 'Emil Novak', 'Omar Hadi', 'Riku Sato', 'Hugo Rivera']],
    ['Lightweight', 70.3, ['Dato Meshari', 'Lucas Rocha', 'Ari Levin', 'Ilya Morin', 'Marco Silva', 'Cameron Hayes']],
    ['Welterweight', 77.1, ['Sandro Meluri', 'Tiago Luz', 'Leon Adler', 'Yusuf Karim', 'Felix Laurent', 'Dylan Ward']],
    ['Middleweight', 83.9, ['Irakli Dariani', 'Nico Paredes', 'Tomas Voss', 'Amir Zayed', 'Andrei Sorin', 'Jasper Cole']],
    ['Light Heavyweight', 93.0, ['Beka Tserani', 'Mauro Cruz', 'Viktor Brandt', 'Rami Faris', 'Theo Marin', 'Caleb Ford']],
];

$eventPlan = [
    ['Opening Bell', -112, 'Past'],
    ['Riverfront', -91, 'Past'],
    ['Highland Card', -70, 'Past'],
    ['Summer Series', -49, 'Past'],
    ['Capital Clash', -28, 'Past'],
    ['Autumn Finals', -7, 'Past'],
    ['Arena Live', 0, 'Live'],
    ['Winter Kickoff', 9, 'Upcoming'],
    ['Coastal Series', 23, 'Upcoming'],
    ['New Year Card', 45, 'Upcoming'],
    ['Champions Night', 66, 'Upcoming'],
    ['Season Finale', 87, 'Upcoming'],
];

$storyPlan = [
    ['The upcoming fight card is set', 'Announcement', 'Explore the test event schedule and open the next event to see its main card.'],
    ['A guide to the rankings', 'News', 'Seven test divisions each have a champion and five ranked contenders. Open the rankings to compare their records.'],
    ['Capital Clash results roundup', 'Review', 'The completed test card includes wins, losses and a draw. Individual results are also visible on the athlete profiles.'],
    ['Three fights to revisit', 'Highlights', 'Open the archive and compare how each fight is shown on the event card and on both athlete profiles.'],
    ['The winter schedule is out', 'Schedule', 'The next group of simulated events includes both opening bouts and featured matchups.'],
    ['A closer look at the roster', 'Interviews', 'This test article accompanies the roster filters. Try a name, a nickname, or a weight class.'],
    ['Autumn Finals recap', 'Highlights', 'Browse the finished card to see the results, including matches that ended in a draw.'],
    ['Preview of the next season', 'Event', 'This draft demonstrates how an unpublished story appears in the admin panel.'],
];

$portraits = [
    'DVALISHVILI_MERAB_L_BELTMOCK.png', 'PANTOJA_ALEXANDRE_L_BELT_12-07.png',
    'NURMAGOMEDOV_UMAR_L_03-02.png', 'YAN_PETR_L_11-23.png',
    'MORENO_BRANDON_L_06-12.png', 'OMALLEY_SEAN_L_08-19.png',
    'ALBAZI_AMIR_L_06-03.png', 'ROYVAL_BRANDON_L_10-12.png',
];
$banners = [
    '6762845e38e05_47638-17333718351268-1920.avif',
    '6762851574e7f_Petr-Yan-Merab-Dvalishvili-UFC-758x505.jpg',
    '67695df7eb3f9_images.jpg',
];
$covers = [
    '6762761064dce_Alexandre-Pantoja-and-Kai-Asakura.webp',
    '676277e49ab91_i.jpg',
    '676274e8af5da_GettyImages-2190011019 (1).jpg',
    '676270e58cc65_102624-Khamzat-Chimaev-Submission-GettyImages-2181229425.jpg',
    '676276e86e099_i.jpeg',
    '6762779989035_i (1).jpeg',
    '676278d2f0270_ufc-tampa-joaquin-buckley-colby-covington-19.webp',
    '6762785e08bf3_mgj8nt609xakmpiojoxe.jpg',
];

function fillDemoImage(string $table, string $column, string $slug, string $file, string $directory): int
{
    if (!media($file, $directory)) {
        return 0;
    }
    return query("UPDATE $table SET $column = ? WHERE slug = ? AND ($column IS NULL OR $column = '')", [$file, $slug])->rowCount();
}

$created = ['divisions' => 0, 'fighters' => 0, 'events' => 0, 'bouts' => 0, 'articles' => 0];
$images = ['fighter photos' => 0, 'event banners' => 0, 'article covers' => 0];
$lock = 'mma_organization_demo_seed';
if ((int) query('SELECT GET_LOCK(?, 5)', [$lock])->fetchColumn() !== 1) {
    fwrite(STDERR, "Another test data import is running.\n");
    exit(1);
}

try {
    $pdo->beginTransaction();
    $rosters = [];
    $lastNames = [];
    $nationalities = ['Georgia', 'Spain', 'Japan', 'Poland', 'Brazil', 'Canada'];
    $nicknames = ['North Star', 'Switch', 'Tempo', 'Night Shift', 'Atlas', 'Signal'];
    foreach ($divisions as $divisionIndex => [$division, $weight, $names]) {
        $className = "$division (" . number_format($weight, 1, '.', '') . ' kg)';
        $classId = query('SELECT weight_class_id FROM weight_classes WHERE name = ? ORDER BY weight_class_id LIMIT 1', [$className])->fetchColumn();
        if (!$classId) {
            query('INSERT INTO weight_classes (name) VALUES (?)', [$className]);
            $classId = (int) $pdo->lastInsertId();
            $created['divisions']++;
        }
        $roster = [];
        foreach ($names as $rank => $name) {
            $slug = 'demo-' . strtolower(str_replace(' ', '-', $name));
            $id = query('SELECT fighter_id FROM fighters WHERE slug = ? ORDER BY fighter_id LIMIT 1', [$slug])->fetchColumn();
            if (!$id) {
                $birthdate = sprintf('%04d-%02d-%02d', 1991 + ($divisionIndex + $rank) % 10, $rank + 1, $divisionIndex + 7);
                $height = number_format(1.59 + $divisionIndex * .047 + $rank * .019, 2, '.', '');
                query('INSERT INTO fighters (name, nickname, nationality, birthdate, height, weight, slug, weight_class_id, ranking)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$name, $nicknames[($rank + $divisionIndex) % 6], $nationalities[$rank], $birthdate, $height, $weight, $slug, $classId, $rank]);
                $id = (int) $pdo->lastInsertId();
                $created['fighters']++;
            }
            $portrait = $portraits[($divisionIndex + $rank) % count($portraits)];
            $images['fighter photos'] += fillDemoImage('fighters', 'photo', $slug, $portrait, '');
            $roster[] = (int) $id;
            $lastNames[$id] = explode(' ', $name, 2)[1];
        }
        $rosters[] = $roster;
    }

    // Rotate opponents within a division, then interleave divisions on each card.
    $matchups = [];
    for ($round = 0; $round < 4; $round++) {
        for ($pair = 0; $pair < 3; $pair++) {
            foreach ($rosters as $roster) {
                $first = $pair === 0 ? 0 : 1 + ($round + $pair - 1) % 5;
                $second = 1 + ($round + 4 - $pair) % 5;
                $matchups[] = [$roster[$first], $roster[$second]];
            }
        }
    }

    $today = new DateTimeImmutable('today');
    $scoredOutcomes = ['Win', 'Loss', 'Win', 'Draw', 'Loss', 'Win', 'Loss'];
    foreach ($eventPlan as $index => [$name, $offset, $status]) {
        $slug = sprintf('demo-fight-night-%02d', $index + 1);
        $banner = $banners[$index % count($banners)];
        if (query('SELECT event_id FROM events WHERE slug = ?', [$slug])->fetchColumn()) {
            $images['event banners'] += fillDemoImage('events', 'banner', $slug, $banner, 'banners');
            continue;
        }
        $card = array_slice($matchups, $index * 6, 6);
        [$mainFirst, $mainSecond] = $card[0];
        $title = "Fight Night: $name";
        $headline = "$title | $lastNames[$mainFirst] vs $lastNames[$mainSecond]";
        $start = $status === 'Live' ? new DateTimeImmutable('-1 hour') : $today->modify("$offset days")->setTime(19, 30);
        query("INSERT INTO events (title, e_name, slug, event_date, main_fighter1_id, main_fighter2_id, banner, status)
               VALUES (?, ?, ?, ?, ?, ?, '', ?)", [$title, $headline, $slug, $start->format('Y-m-d H:i:s'), $mainFirst, $mainSecond, $status]);
        $eventId = (int) $pdo->lastInsertId();
        $created['events']++;
        $images['event banners'] += fillDemoImage('events', 'banner', $slug, $banner, 'banners');
        foreach ($card as $slot => [$first, $second]) {
            $scored = $status === 'Past' || ($status === 'Live' && $slot >= 4);
            $result = $scored ? $scoredOutcomes[($index * 3 + $slot) % count($scoredOutcomes)] : 'Pending';
            query('INSERT INTO event_fights (event_id, fighter1_id, fighter2_id, result) VALUES (?, ?, ?, ?)', [$eventId, $first, $second, $result]);
            applyRecord($first, $second, $result, 1);
            $created['bouts']++;
        }
    }

    foreach ($storyPlan as $index => [$title, $category, $copy]) {
        $slug = sprintf('demo-story-%02d', $index + 1);
        if (query('SELECT news_id FROM news WHERE slug = ?', [$slug])->fetchColumn()) {
            $images['article covers'] += fillDemoImage('news', 'image_url', $slug, $covers[$index], 'news_images');
            continue;
        }
        $date = $today->modify("-$index days")->setTime(12, 0);
        query("INSERT INTO news (title, slug, content, author, category, status, publish_date, tags, is_featured, comments_enabled)
               VALUES (?, ?, ?, 'MMA Editorial', ?, ?, ?, 'test', ?, 0)",
            [$title, $slug, "This article uses fictional test data.\n\n$copy", $category, $index === 7 ? 'inactive' : 'active',
                $date->format('Y-m-d H:i:s'), $index === 0 ? 1 : 0]);
        $created['articles']++;
        $images['article covers'] += fillDemoImage('news', 'image_url', $slug, $covers[$index], 'news_images');
    }

    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
} finally {
    query('SELECT RELEASE_LOCK(?)', [$lock]);
}

foreach ($created + $images as $type => $count) {
    printf("%s: %d added\n", ucfirst($type), $count);
}
