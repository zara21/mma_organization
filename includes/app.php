<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
define('APP_URL', getenv('MMA_APP_URL') !== false ? rtrim((string) getenv('MMA_APP_URL'), '/') : '/mma_organization');
const EVENT_STATES = ['Upcoming', 'Live', 'Past'];
const RESULTS = ['Pending', 'Win', 'Loss', 'Draw', 'No contest'];
const EVENT_LIVE_WINDOW_SECONDS = 8 * 60 * 60;
const NEWS_CATEGORIES = ['News', 'Live', 'Review', 'Schedule', 'Event', 'Announcement', 'Highlights', 'Interviews', 'Opinion'];

date_default_timezone_set(getenv('MMA_TIMEZONE') ?: 'Asia/Tbilisi');
session_start([
    'use_strict_mode' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_path' => APP_URL . '/',
]);

require_once APP_ROOT . '/config/db_connect.php';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    return APP_URL . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function query(string $sql, array $args = []): PDOStatement
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    return $stmt;
}

function input(array $source, string $key, string $default = ''): string
{
    return isset($source[$key]) && is_scalar($source[$key]) ? trim((string) $source[$key]) : $default;
}

/** Builds a LIKE pattern that treats %, _ and \ in user input literally. */
function likePattern(string $search): string
{
    return '%' . addcslashes($search, '%_' . chr(92)) . '%';
}

/** Simple per-IP throttle for sign-in attempts (5 failures per 15 minutes). */
function loginThrottle(string $action): bool
{
    $file = sys_get_temp_dir() . '/mma_login_' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'cli');
    $window = 900;
    $attempts = array_filter(is_file($file) ? (array) json_decode((string) file_get_contents($file), true) : [],
        static fn ($time) => is_int($time) && $time > time() - $window);
    if ($action === 'fail') {
        $attempts[] = time();
    }
    if ($action === 'reset') {
        $attempts = [];
    }
    if ($action !== 'check') {
        file_put_contents($file, json_encode(array_values($attempts)), LOCK_EX);
    }
    return count($attempts) >= 5;
}

function csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrfField(): void
{
    echo '<input type="hidden" name="csrf" value="' . h(csrf()) . '">';
}

function verifyPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        showError(405, 'This action requires a form submission.');
    }
    if (!hash_equals(csrf(), input($_POST, 'csrf'))) {
        showError(403, 'Your session has expired. Refresh the page and try again.');
    }
}

function currentUser(): ?array
{
    static $loaded = false, $user = null;
    if (!$loaded) {
        $loaded = true;
        if (isset($_SESSION['user_id'])) {
            $user = query('SELECT user_id, username, email, is_admin, role FROM users WHERE user_id = ?', [$_SESSION['user_id']])->fetch() ?: null;
        }
    }
    return $user;
}

function isAdmin(?array $user): bool
{
    return $user && ((int) $user['is_admin'] === 1 || $user['role'] === 'admin');
}

function authenticateAccount(string $email, string $password): ?array
{
    $matches = [];
    foreach (query('SELECT user_id, password, is_admin, role FROM users WHERE email = ? ORDER BY user_id', [$email]) as $account) {
        if (password_verify($password, $account['password'])) {
            unset($account['password']);
            $matches[] = $account;
        }
    }
    return count($matches) === 1 ? $matches[0] : null;
}

function requireAdmin(): array
{
    $user = currentUser();
    if (!$user) {
        redirect('/routes/login?next=admin');
    }
    if (!isAdmin($user)) {
        showError(403, 'This page requires an administrator account.');
    }
    return $user;
}

function showError(int $status, string $message): never
{
    http_response_code($status);
    pageStart((string) $status);
    echo '<main class="shell section"><h1>' . $status . '</h1><p class="empty">' . h($message) . '</p><a class="button" href="' . url() . '">Back to home</a></main>';
    pageEnd();
    exit;
}

function media(?string $value, string $directory): ?string
{
    if (!$value) {
        return null;
    }
    $file = basename(str_replace(chr(92), '/', $value));
    $relative = '/assets/uploads/' . ($directory === '' ? '' : $directory . '/') . $file;
    return is_file(APP_ROOT . $relative) ? url(substr($relative, 0, -strlen($file)) . rawurlencode($file)) : null;
}

function httpLink(?string $value): ?string
{
    if ($value && filter_var($value, FILTER_VALIDATE_URL) && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)) {
        return $value;
    }
    return null;
}

function dateLabel(?string $value, string $format = 'M j, Y'): string
{
    $time = $value ? strtotime($value) : false;
    return $time === false ? 'Date to be announced' : date($format, $time);
}

function eventState(array $event, ?int $now = null): string
{
    if ($event['status'] === 'Past') {
        return 'Past';
    }
    $now ??= time();
    $time = $event['event_date'] ? strtotime($event['event_date']) : false;
    if ($time === false || $time > $now) {
        return 'Upcoming';
    }
    return $now - $time < EVENT_LIVE_WINDOW_SECONDS ? 'Live' : 'Past';
}

function detailUrl(string $type, array $row): string
{
    $slug = $row['slug'] ?? '';
    if (str_starts_with($slug, 'demo-')) {
        $slug = substr($slug, strlen('demo-'));
    }
    return url('/views/' . $type . '/' . rawurlencode($slug));
}

function canonicalizeDetailUrl(string $type, array $row): void
{
    $canonical = detailUrl($type, $row);
    if (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) !== $canonical) {
        header('Location: ' . $canonical, true, 301);
        exit;
    }
}

function record(array $fighter): string
{
    return implode('–', array_map(fn ($stat) => (int) ($fighter[$stat] ?? 0), ['wins', 'losses', 'draws']));
}

function resultLabel(?string $result, bool $first): string
{
    return match ($result) {
        'Win', 'Fighter 1 Wins' => $first ? 'Win' : 'Loss',
        'Loss', 'Fighter 2 Wins' => $first ? 'Loss' : 'Win',
        'Draw' => 'Draw',
        'No contest', 'No Contest', 'NC' => 'No contest',
        default => 'Pending',
    };
}

function boutMethodGroup(?string $method): string
{
    $method = trim((string) $method);
    if ($method === '' || in_array(strtolower($method), ['unknown', 'unverified', 'not specified'], true)) {
        return 'Method not recorded';
    }
    return match (true) {
        (bool) preg_match('~\b(?:t\.?\s*k\.?\s*o|k\.?\s*o|knock[\s-]*out|(?:referee|doctor|medical|corner) stoppage|did not answer the bell)\b|ნოკაუტ~iu', $method) => 'KO',
        (bool) preg_match('~\b(?:submissions?|sub|tap[\s-]*out|tapped|chokes?|arm[\s-]*bar|kimura|americana|omoplata|gogoplata|heel[\s-]*hook|knee[\s-]*bar|toe[\s-]*hold|ankle[\s-]*lock|wrist[\s-]*lock|arm[\s-]*lock|leg[\s-]*lock|neck[\s-]*crank|twister|triangle|guillotine|slicers?)\b|საბმიშ|დანებ~iu', $method) => 'Submission',
        (bool) preg_match('~\b(?:decisions?|points?|score[\s-]*cards?|judges?|ud|sd|md)\b|ქულ|გადაწყვეტილებ~iu', $method) => 'Decision',
        default => 'Other',
    };
}

function fighterMethodStats(int $fighterId, array $bouts): array
{
    $stats = array_fill_keys(['KO', 'Submission', 'Decision', 'Other', 'Method not recorded'], ['Win' => 0, 'Loss' => 0]);
    foreach ($bouts as $bout) {
        $outcome = resultLabel($bout['result'], (int) $bout['fighter1_id'] === $fighterId);
        if ($outcome === 'Win' || $outcome === 'Loss') {
            $stats[boutMethodGroup($bout['method'] ?? null)][$outcome]++;
        }
    }
    foreach (['Other', 'Method not recorded'] as $group) {
        if (array_sum($stats[$group]) === 0) {
            unset($stats[$group]);
        }
    }
    return $stats;
}

function boutOutcome(?string $result, bool $first, string $state): string
{
    $outcome = resultLabel($result, $first);
    if ($outcome !== 'Pending') {
        return $outcome;
    }
    return match ($state) {
        'Upcoming' => 'Scheduled',
        'Past' => 'Result not recorded',
        default => 'Pending',
    };
}

function finishTime(?int $seconds): string
{
    return $seconds === null ? '' : sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
}

function boutDetails(array $bout): string
{
    return implode(' · ', array_filter([
        $bout['method'] ?? null,
        isset($bout['finish_round']) ? 'Round ' . $bout['finish_round'] : null,
        finishTime(isset($bout['finish_seconds']) ? (int) $bout['finish_seconds'] : null),
    ]));
}

function eventFights(int $event): array
{
    return query('SELECT ef.*, a.name AS first_name, a.slug AS first_slug, a.photo AS first_photo,
                         b.name AS second_name, b.slug AS second_slug, b.photo AS second_photo
                  FROM event_fights ef JOIN fighters a ON a.fighter_id = ef.fighter1_id
                  JOIN fighters b ON b.fighter_id = ef.fighter2_id WHERE ef.event_id = ? ORDER BY ef.fight_id', [$event])->fetchAll();
}

function recordedEventFights(int $event): array
{
    return query('SELECT f.*, a.name AS first_name, a.slug AS first_slug, a.photo AS first_photo,
                         b.name AS second_name, b.slug AS second_slug, b.photo AS second_photo
                  FROM fights f JOIN fighters a ON a.fighter_id = f.fighter1_id
                  JOIN fighters b ON b.fighter_id = f.fighter2_id WHERE f.event_id = ? ORDER BY f.fight_id', [$event])->fetchAll();
}

require_once APP_ROOT . '/includes/layout.php';
