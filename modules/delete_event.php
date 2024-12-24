<?php
include '../config/db_connect.php';
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$event_id]);

    header("Location: events.php");
    exit;
}
?>
