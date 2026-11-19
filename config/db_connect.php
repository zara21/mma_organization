<?php
declare(strict_types=1);

$host = getenv('MMA_DB_HOST') ?: '127.0.0.1';
$port = getenv('MMA_DB_PORT') ?: '3306';
$db = getenv('MMA_DB_NAME') ?: 'mma_organization';
$user = getenv('MMA_DB_USER') ?: 'root';
$pass = getenv('MMA_DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $error) {
    error_log($error->getMessage());
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    exit('<!doctype html><html lang="en"><meta charset="utf-8"><title>Service unavailable</title><h1>Database unavailable</h1><p>Start MySQL in XAMPP and check the database settings, then reload this page.</p></html>');
}
