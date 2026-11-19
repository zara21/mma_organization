<?php
declare(strict_types=1);

const MAX_FIGHTER_RANK = 100000;
const CUSTOM_FIGHT_METHOD = '__other__';
const FIGHT_METHOD_GROUPS = [
    'Knockout and stoppage' => [
        'KO', 'TKO', 'TKO (referee stoppage)', 'TKO (doctor stoppage)',
        'TKO (corner stoppage)', 'TKO (did not answer the bell)', 'TKO (injury)',
    ],
    'Submission' => ['Submission', 'Tap out', 'Verbal submission', 'Technical submission'],
    'Decision' => ['Decision', 'Unanimous decision', 'Split decision', 'Majority decision', 'Technical decision'],
    'Draw' => ['Unanimous draw', 'Split draw', 'Majority draw', 'Technical draw'],
    'Other official results' => ['Disqualification', 'Forfeit', 'Accidental foul', 'Overturned result'],
];

function field(string $label, string $type = 'text', bool $required = false, int|float|array|null $options = null): array
{
    return compact('label', 'type', 'required', 'options');
}

function fightDetailFields(): array
{
    return [
        'method' => field('Method', 'text', false, 50),
        'finish_round' => field('Finish round', 'number', false, 99),
        'finish_time' => field('Time into round (M:SS)', 'text', false, 5),
    ];
}

function validatedFightDetails(array $data, string $customMethod = ''): array
{
    $time = $data['finish_time'];
    $method = $data['method'];
    if ($method === CUSTOM_FIGHT_METHOD) {
        $method = trim($customMethod);
        if ($method === '' || !mb_check_encoding($method, 'UTF-8') || mb_strlen($method) > 50) {
            throw new DomainException('Enter a description of up to 50 characters for the other method.');
        }
    }
    if ($data['finish_round'] !== null && (int) $data['finish_round'] < 1) {
        throw new DomainException('Enter a finish round of at least 1.');
    }
    if ($time !== null && !preg_match('/^(\d{1,2}):([0-5]\d)$/D', $time, $parts)) {
        throw new DomainException('Enter the finish time as minutes and seconds, for example 2:35.');
    }
    return [
        'method' => $method,
        'finish_round' => $data['finish_round'] === null ? null : (int) $data['finish_round'],
        'finish_seconds' => $time === null ? null : (int) $parts[1] * 60 + (int) $parts[2],
    ];
}

function logAdminAction(?int $adminId, string $type, string $description): void
{
    query('INSERT INTO admin_actions (admin_id, action_type, description) VALUES (?, ?, ?)', [$adminId, $type, $description]);
}

function adminRankLabel(?int $rank): string
{
    return match ($rank) {
        null => 'Unranked',
        0 => 'Champion',
        default => '#' . $rank,
    };
}

function adminBoutLabel(array $bout): string
{
    $result = match ($bout['result'] ?? null) {
        'Win', 'Fighter 1 Wins' => 'Fighter 1 wins',
        'Loss', 'Fighter 2 Wins' => 'Fighter 2 wins',
        'Draw' => 'Draw',
        'No contest', 'No Contest', 'NC' => 'No contest',
        default => 'Pending',
    };
    $details = boutDetails($bout);
    return $details === '' ? $result : $result . ' · ' . $details;
}

function fightDetailsChanged(array $old, array $new): bool
{
    return $old['method'] !== $new['method'] ||
        $old['finish_round'] !== $new['finish_round'] ||
        $old['finish_seconds'] !== $new['finish_seconds'];
}

function adminResources(): array
{
    $fighters = query('SELECT fighter_id, name FROM fighters ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
    $classes = query('SELECT weight_class_id, name FROM weight_classes ORDER BY weight_class_id')->fetchAll(PDO::FETCH_KEY_PAIR);
    $events = query('SELECT event_id, title FROM events ORDER BY event_date DESC')->fetchAll(PDO::FETCH_KEY_PAIR);
    return [
        'events' => ['Events', 'event_id', 'title', [
            'title' => field('Title', 'text', true, 255),
            'e_name' => field('Display title', 'text', false, 255),
            'event_date' => field('Event date and time (Tbilisi)', 'datetime-local', true),
            'status' => field('Status', 'select', true, array_combine(EVENT_STATES, EVENT_STATES)),
            'main_fighter1_id' => field('Main event · fighter 1', 'select', true, $fighters),
            'main_fighter2_id' => field('Main event · fighter 2', 'select', true, $fighters),
            'ticket_link' => field('Ticket URL', 'url', false, 255),
            'additional_link' => field('Event information URL', 'url', false, 255),
            'banner' => field('Banner image', 'file'),
        ]],
        'fighters' => ['Fighters', 'fighter_id', 'name', [
            'name' => field('Name', 'text', true, 100),
            'nickname' => field('Nickname', 'text', false, 100),
            'nationality' => field('Nationality', 'text', false, 50),
            'birthdate' => field('Birth date', 'date'),
            'height' => field('Height (m)', 'decimal', false, 9.99),
            'weight' => field('Weight (kg)', 'decimal', false, 999.9),
            'wins' => field('Wins', 'number', true, 100000),
            'losses' => field('Losses', 'number', true, 100000),
            'draws' => field('Draws', 'number', true, 100000),
            'weight_class_id' => field('Weight class', 'select', false, $classes),
            'ranking' => field('Ranking (0 for champion)', 'number', false, MAX_FIGHTER_RANK),
            'photo' => field('Fighter photo', 'file'),
        ]],
        'news' => ['News', 'news_id', 'title', [
            'title' => field('Title', 'text', true, 255),
            'author' => field('Author', 'text', false, 100),
            'category' => field('Category', 'select', true, array_combine(NEWS_CATEGORIES, NEWS_CATEGORIES)),
            'status' => field('Publication status', 'select', true, ['active' => 'Published', 'inactive' => 'Draft']),
            'content' => field('Article text', 'textarea', true, 16000),
            'video_url' => field('Video URL', 'url', false, 255),
            'tags' => field('Tags', 'text', false, 255),
            'is_featured' => field('Feature on home page', 'select', true, [0 => 'No', 1 => 'Yes']),
            'image_url' => field('Article image', 'file'),
        ]],
        'weight_classes' => ['Weight classes', 'weight_class_id', 'name', [
            'name' => field('Division name', 'text', true, 100),
        ]],
        'fights' => ['Recorded fights', 'fight_id', 'location', [
            'event_id' => field('Event', 'select', true, $events),
            'fighter1_id' => field('Fighter 1', 'select', true, $fighters),
            'fighter2_id' => field('Fighter 2', 'select', true, $fighters),
            'date' => field('Fight date', 'date', true),
            'location' => field('Location', 'text', true, 100),
            'result' => field('Result', 'select', true, ['Fighter 1 Wins' => 'Fighter 1 wins', 'Fighter 2 Wins' => 'Fighter 2 wins', 'Draw' => 'Draw', 'No contest' => 'No contest']),
        ] + fightDetailFields()],
    ];
}

function adminPath(string $section = '', array $params = []): string
{
    return '/views/admin' . ($section ? '?' . http_build_query(['section' => $section] + $params) : '');
}

function rankingVersion(array $fighters): string
{
    $ranks = array_column($fighters, 'ranking', 'fighter_id');
    ksort($ranks);
    return hash('sha256', json_encode($ranks, JSON_THROW_ON_ERROR));
}

function saveDivisionRankings(int $division, array $submitted, string $version, ?int $adminId = null): void
{
    global $pdo;

    $pdo->beginTransaction();
    try {
        $fighters = query('SELECT fighter_id, name, ranking FROM fighters WHERE weight_class_id = ? ORDER BY fighter_id FOR UPDATE', [$division])->fetchAll();
        if (!$fighters || count($fighters) !== count($submitted) || rankingVersion($fighters) !== $version) {
            throw new DomainException('This division has changed. Review the latest rankings before saving.');
        }

        foreach ($fighters as $fighter) {
            $id = (int) $fighter['fighter_id'];
            $value = $submitted[$id] ?? null;
            if (!is_string($value) || ($value !== '' && (!ctype_digit($value) || (int) $value > MAX_FIGHTER_RANK))) {
                throw new DomainException('Enter a whole number from 0 to ' . MAX_FIGHTER_RANK . ', or leave the position blank.');
            }
            $rank = $value === '' ? null : (int) $value;
            $previous = $fighter['ranking'] === null ? null : (int) $fighter['ranking'];
            if ($rank !== $previous) {
                query('UPDATE fighters SET ranking = ? WHERE fighter_id = ?', [$rank, $id]);
                logAdminAction($adminId, 'Ranking', $fighter['name'] . ' (fighter #' . $id . ', division #' . $division . ') · ' .
                    adminRankLabel($previous) . ' → ' . adminRankLabel($rank));
            }
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function validatedFields(array $fields): array
{
    $data = [];
    foreach ($fields as $key => ['label' => $label, 'type' => $type, 'required' => $required, 'options' => $options]) {
        if ($type === 'file') {
            continue;
        }
        $value = input($_POST, $key);
        if ($value === '') {
            if ($required) {
                throw new DomainException($label . ' is required.');
            }
            $data[$key] = null;
            continue;
        }
        $valid = match ($type) {
            'select' => array_key_exists($value, $options),
            'number' => ctype_digit($value) && (int) $value <= $options,
            'decimal' => preg_match('/^\d+(\.\d{1,2})?$/D', $value) && (float) $value > 0 && (float) $value <= $options,
            'date' => validDate($value, 'Y-m-d'),
            'datetime-local' => validDate($value, 'Y-m-d\TH:i') || validDate($value, 'Y-m-d\TH:i:s'),
            'url' => httpLink($value) && mb_strlen($value) <= $options,
            default => mb_check_encoding($value, 'UTF-8') && mb_strlen($value) <= $options,
        };
        if (!$valid) {
            throw new DomainException('Enter a valid value for ' . $label . '.');
        }
        if ($type === 'datetime-local') {
            $value = date('Y-m-d H:i:s', strtotime($value));
        }
        $data[$key] = $value;
    }
    return $data;
}

function validDate(string $value, string $format): bool
{
    $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
    return $date && $date->format($format) === $value && $date->format('Y') >= '1000';
}

function uploadImage(string $key, string $directory): ?string
{
    $file = $_FILES[$key] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        throw new DomainException('Upload an image smaller than 5 MB.');
    }
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $image = @getimagesize($file['tmp_name']);
    if (!isset($extensions[$mime]) || !$image || $image['mime'] !== $mime) {
        throw new DomainException('Choose a JPEG, PNG, WebP or AVIF image.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $path = APP_ROOT . '/assets/uploads/' . ($directory === '' ? '' : $directory . '/') . $name;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new DomainException('The image could not be saved.');
    }
    return $path;
}

function imageColumn(string $section): ?array
{
    return match ($section) {
        'events' => ['banner', 'banners'],
        'fighters' => ['photo', ''],
        'news' => ['image_url', 'news_images'],
        default => null,
    };
}

/** Deletes an uploaded image once no row of that table references it any more. */
function removeUnusedImage(string $section, ?string $file): void
{
    $image = imageColumn($section);
    $file = $file ? basename(str_replace(chr(92), '/', $file)) : '';
    if (!$image || $file === '' || $file === '.' || $file === '..') {
        return;
    }
    [$column, $folder] = $image;
    if (query("SELECT COUNT(*) FROM $section WHERE $column = ?", [$file])->fetchColumn() > 0) {
        return;
    }
    $path = APP_ROOT . '/assets/uploads/' . ($folder === '' ? '' : $folder . '/') . $file;
    if (is_file($path)) {
        @unlink($path);
    }
}

function applyRecord(int $first, int $second, ?string $result, int $direction): void
{
    $changes = match ($result) {
        'Win', 'Fighter 1 Wins' => [$first => 'wins', $second => 'losses'],
        'Loss', 'Fighter 2 Wins' => [$first => 'losses', $second => 'wins'],
        'Draw' => [$first => 'draws', $second => 'draws'],
        default => [],
    };
    ksort($changes);
    foreach ($changes as $fighter => $stat) {
        query("UPDATE fighters SET $stat = GREATEST(0, COALESCE($stat, 0) + ?) WHERE fighter_id = ?", [$direction, $fighter]);
    }
}

function ensureMainBout(int $id, int $first, int $second): void
{
    $pair = query('SELECT fight_id FROM event_fights WHERE event_id = ? AND ((fighter1_id = ? AND fighter2_id = ?) OR (fighter1_id = ? AND fighter2_id = ?))',
        [$id, $first, $second, $second, $first])->fetch();
    if (!$pair) {
        query('INSERT INTO event_fights (event_id, fighter1_id, fighter2_id) VALUES (?, ?, ?)', [$id, $first, $second]);
    }
}

function saveAdminRecord(string $section, string $pk, array $fields, int $id, ?int $adminId = null): int
{
    global $pdo;
    $data = validatedFields($fields);
    $upload = null;
    $pdo->beginTransaction();
    try {
        $existing = $id ? query("SELECT * FROM $section WHERE $pk = ? FOR UPDATE", [$id])->fetch() : null;
        if ($id && !$existing) {
            throw new DomainException('This record no longer exists.');
        }
        if ($section === 'events') {
            $first = (int) $data['main_fighter1_id'];
            $second = (int) $data['main_fighter2_id'];
            $legacyPair = $existing && $first === (int) $existing['main_fighter1_id'] && $second === (int) $existing['main_fighter2_id'];
            if ($first === $second && !$legacyPair) {
                throw new DomainException('Choose two different main event fighters.');
            }
            if (recordedPair($id, $first, $second)) {
                throw new DomainException('This matchup already exists under Recorded fights.');
            }
        }
        if ($section === 'fighters') {
            if ($data['birthdate'] && $data['birthdate'] > date('Y-m-d')) {
                throw new DomainException('Birth date cannot be in the future.');
            }
            if ($data['ranking'] !== null && $data['weight_class_id'] === null) {
                throw new DomainException('Select a weight class for this ranking.');
            }
            $data['age'] = $data['birthdate'] ? (new DateTimeImmutable($data['birthdate']))->diff(new DateTimeImmutable('today'))->y : null;
        }
        if ($section === 'weight_classes' && query('SELECT weight_class_id FROM weight_classes WHERE name = ? AND weight_class_id <> ?', [$data['name'], $id])->fetch()) {
            throw new DomainException('That weight class already exists.');
        }
        if ($section === 'fights') {
            $data = array_replace($data, validatedFightDetails($data, input($_POST, 'method_other')));
            unset($data['finish_time']);
            if ((int) $data['fighter1_id'] === (int) $data['fighter2_id']) {
                throw new DomainException('Choose two different fighters.');
            }
            if ($data['date'] > date('Y-m-d')) {
                throw new DomainException('A recorded fight cannot have a future date.');
            }
            query('SELECT event_id FROM events WHERE event_id = ? FOR UPDATE', [$data['event_id']]);
            $pair = [$data['event_id'], $data['fighter1_id'], $data['fighter2_id'], $data['fighter2_id'], $data['fighter1_id']];
            $pairFilter = ' WHERE event_id = ? AND ((fighter1_id = ? AND fighter2_id = ?) OR (fighter1_id = ? AND fighter2_id = ?))';
            $main = query('SELECT event_id FROM events WHERE event_id = ? AND ((main_fighter1_id = ? AND main_fighter2_id = ?) OR (main_fighter1_id = ? AND main_fighter2_id = ?))', $pair)->fetch();
            if ($main || query('SELECT fight_id FROM event_fights' . $pairFilter, $pair)->fetch() ||
                query('SELECT fight_id FROM fights' . $pairFilter . ' AND fight_id <> ?', [...$pair, $id])->fetch()) {
                throw new DomainException('This matchup is already listed for that event. Edit it where it was recorded.');
            }
        }
        if (!$id && in_array($section, ['events', 'fighters', 'news'], true)) {
            $title = mb_strtolower($data['title'] ?? $data['name'], 'UTF-8');
            $slug = trim(preg_replace('/[^\pL\pN]+/u', '-', $title), '-');
            $data['slug'] = (mb_substr($slug, 0, 200) ?: rtrim($section, 's')) . '-' . bin2hex(random_bytes(5));
        }

        $image = imageColumn($section);
        if ($image) {
            [$column, $folder] = $image;
            $upload = uploadImage($column, $folder);
            if ($upload) {
                $data[$column] = basename($upload);
            } elseif (!$id && $section === 'events') {
                $data[$column] = '';
            }
        }

        $columns = array_keys($data);
        if ($id) {
            $assignments = implode(', ', array_map(fn ($column) => $column . ' = ?', $columns));
            query("UPDATE $section SET $assignments WHERE $pk = ?", [...array_values($data), $id]);
        } else {
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            query("INSERT INTO $section (" . implode(', ', $columns) . ") VALUES ($placeholders)", array_values($data));
            $id = (int) $pdo->lastInsertId();
        }
        if ($section === 'events' && $first !== $second) {
            ensureMainBout($id, $first, $second);
        }
        if ($section === 'fights') {
            $changed = !$existing || (int) $existing['fighter1_id'] !== (int) $data['fighter1_id'] ||
                (int) $existing['fighter2_id'] !== (int) $data['fighter2_id'] || $existing['result'] !== $data['result'];
            if ($existing && $changed) {
                applyRecord((int) $existing['fighter1_id'], (int) $existing['fighter2_id'], $existing['result'], -1);
            }
            if ($changed) {
                applyRecord((int) $data['fighter1_id'], (int) $data['fighter2_id'], $data['result'], 1);
            }
            $resultChange = adminBoutLabel($data);
            $previousResult = $existing ? adminBoutLabel($existing) : 'New result';
            if ($changed || fightDetailsChanged($existing, $data) || (int) $existing['event_id'] !== (int) $data['event_id']) {
                logAdminAction($adminId, 'Fight result', 'Recorded fight #' . $id . ' (event #' . $data['event_id'] . ') · ' .
                    $previousResult . ' → ' . $resultChange);
            }
        }
        if ($section === 'fighters') {
            $previousRank = isset($existing['ranking']) ? (int) $existing['ranking'] : null;
            $rank = $data['ranking'] === null ? null : (int) $data['ranking'];
            $previousDivision = (int) ($existing['weight_class_id'] ?? 0);
            $division = (int) $data['weight_class_id'];
            if ($previousRank !== $rank || ($rank !== null && $previousDivision !== $division)) {
                logAdminAction($adminId, 'Ranking', $data['name'] . ' (fighter #' . $id . ') · ' .
                    adminRankLabel($previousRank) . " (division #$previousDivision) → " . adminRankLabel($rank) . " (division #$division)");
            }
        }
        if ($section === 'news' && $data['status'] !== ($existing['status'] ?? null)) {
            logAdminAction($adminId, 'Article status', $data['title'] . ' (article #' . $id . ') · ' .
                publicationStatus($existing['status'] ?? null) . ' → ' . publicationStatus($data['status']));
        }
        $pdo->commit();
        if ($upload && $existing) {
            removeUnusedImage($section, $existing[$image[0]] ?? null);
        }
        return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($upload && is_file($upload)) {
            unlink($upload);
        }
        throw $error;
    }
}

function recordedPair(int $event, int $first, int $second): bool
{
    return $event > 0 && (bool) query('SELECT fight_id FROM fights WHERE event_id = ?
        AND ((fighter1_id = ? AND fighter2_id = ?) OR (fighter1_id = ? AND fighter2_id = ?))',
        [$event, $first, $second, $second, $first])->fetch();
}

function publicationStatus(?string $status): string
{
    return match ($status) {
        'active' => 'Published',
        'inactive' => 'Draft',
        default => 'New article',
    };
}

function saveFightCard(int $eventId, string $operation, ?int $adminId = null): void
{
    global $pdo;
    $pdo->beginTransaction();
    try {
        $event = query('SELECT * FROM events WHERE event_id = ? FOR UPDATE', [$eventId])->fetch();
        if (!$event) {
            throw new DomainException('Event not found.');
        }
        if ($operation === 'add_bout') {
            $first = (int) input($_POST, 'first');
            $second = (int) input($_POST, 'second');
            if ($first === $second || (int) query('SELECT COUNT(*) FROM fighters WHERE fighter_id IN (?, ?)', [$first, $second])->fetchColumn() !== 2) {
                throw new DomainException('Choose two different fighters.');
            }
            if (recordedPair($eventId, $first, $second)) {
                throw new DomainException('This matchup already exists under Recorded fights.');
            }
            ensureMainBout($eventId, $first, $second);
        } else {
            $id = (int) input($_POST, 'bout');
            $fight = query('SELECT * FROM event_fights WHERE event_id = ? AND fight_id = ? FOR UPDATE', [$eventId, $id])->fetch();
            if (!$fight) {
                throw new DomainException('Fight not found on this card.');
            }
            $first = (int) $fight['fighter1_id'];
            $second = (int) $fight['fighter2_id'];
            $previous = $fight['result'];
            $label = $event['title'] . ' (fight #' . $id . ') · ';
            if ($operation === 'remove_bout') {
                $main = [(int) $event['main_fighter1_id'], (int) $event['main_fighter2_id']];
                if (in_array($main, [[$first, $second], [$second, $first]], true)) {
                    throw new DomainException('Set another main event before removing this fight.');
                }
                applyRecord($first, $second, $previous, -1);
                query('DELETE FROM event_fights WHERE fight_id = ?', [$id]);
                if ($previous && $previous !== 'Pending') {
                    logAdminAction($adminId, 'Fight result', $label . adminBoutLabel($fight) . ' → Removed');
                }
            } else {
                $result = input($_POST, 'result');
                if (!in_array($result, RESULTS, true)) {
                    throw new DomainException('Choose a valid fight result.');
                }
                $details = $result === 'Pending'
                    ? ['method' => null, 'finish_round' => null, 'finish_seconds' => null]
                    : validatedFightDetails(validatedFields(fightDetailFields()), input($_POST, 'method_other'));
                if ($result !== $previous) {
                    applyRecord($first, $second, $previous, -1);
                    applyRecord($first, $second, $result, 1);
                }
                $oldLabel = adminBoutLabel($fight);
                $newLabel = adminBoutLabel(['result' => $result] + $details);
                if ($result !== $previous || fightDetailsChanged($fight, $details)) {
                    query('UPDATE event_fights SET result = ?, method = ?, finish_round = ?, finish_seconds = ? WHERE fight_id = ?',
                        [$result, $details['method'], $details['finish_round'], $details['finish_seconds'], $id]);
                    logAdminAction($adminId, 'Fight result', $label . $oldLabel . ' → ' . $newLabel);
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function deleteAdminRecord(string $section, string $pk, int $id, ?int $adminId = null): void
{
    global $pdo;
    $pdo->beginTransaction();
    try {
        $row = query("SELECT * FROM $section WHERE $pk = ? FOR UPDATE", [$id])->fetch();
        if (!$row) {
            throw new DomainException('This record no longer exists.');
        }
        if ($section === 'fighters' && query('SELECT fight_id FROM event_fights WHERE fighter1_id = ? OR fighter2_id = ?', [$id, $id])->fetch()) {
            throw new DomainException('This fighter is on a fight card. Remove the matchup before deleting the athlete.');
        }
        if ($section === 'events') {
            if (query('SELECT fight_id FROM fights WHERE event_id = ?', [$id])->fetch()) {
                throw new DomainException('Remove or reassign the recorded fights before deleting this event.');
            }
            foreach (eventFights($id) as $fight) {
                applyRecord((int) $fight['fighter1_id'], (int) $fight['fighter2_id'], $fight['result'], -1);
                if ($fight['result'] && $fight['result'] !== 'Pending') {
                    logAdminAction($adminId, 'Fight result', $row['title'] . ' (fight #' . $fight['fight_id'] . ') · ' .
                        adminBoutLabel($fight) . ' → Removed with event');
                }
            }
        }
        if ($section === 'fights') {
            applyRecord((int) $row['fighter1_id'], (int) $row['fighter2_id'], $row['result'], -1);
            logAdminAction($adminId, 'Fight result', 'Recorded fight #' . $id . ' · ' . adminBoutLabel($row) . ' → Removed');
        }
        if ($section === 'news') {
            logAdminAction($adminId, 'Article status', $row['title'] . ' (article #' . $id . ') · ' .
                publicationStatus($row['status']) . ' → Removed');
        }
        query("DELETE FROM $section WHERE $pk = ?", [$id]);
        $pdo->commit();
        $image = imageColumn($section);
        if ($image) {
            removeUnusedImage($section, $row[$image[0]] ?? null);
        }
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}
