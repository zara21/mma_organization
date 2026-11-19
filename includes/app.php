<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const APP_URL = '/mma_organization';
const EVENT_STATES = ['Upcoming', 'Live', 'Past'];
const RESULTS = ['Pending', 'Win', 'Loss', 'Draw'];
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

function eventState(array $event): string
{
    return $event['status'] === 'Upcoming' && $event['event_date'] && strtotime($event['event_date']) <= time()
        ? 'Past' : $event['status'];
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
        default => 'Pending',
    };
}

function boutOutcome(?string $result, bool $first, string $state): string
{
    $outcome = resultLabel($result, $first);
    return $outcome === 'Pending' && $state === 'Upcoming' ? 'Scheduled' : $outcome;
}

function eventFights(int $event): array
{
    return query('SELECT ef.*, a.name AS first_name, a.slug AS first_slug, a.photo AS first_photo,
                         b.name AS second_name, b.slug AS second_slug, b.photo AS second_photo
                  FROM event_fights ef JOIN fighters a ON a.fighter_id = ef.fighter1_id
                  JOIN fighters b ON b.fighter_id = ef.fighter2_id WHERE ef.event_id = ? ORDER BY ef.fight_id', [$event])->fetchAll();
}

require_once APP_ROOT . '/includes/layout.php';
