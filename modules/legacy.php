<?php
require_once __DIR__ . '/../includes/app.php';
require_once APP_ROOT . '/includes/admin.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    showError(405, 'Use the admin panel to submit changes.');
}
$legacyId = (int) input($_GET, $legacySection === 'events' ? 'event_id' : 'id');
$legacyParams = $legacyCreate || ($legacyEdit && $legacyId) ? ['mode' => 'edit'] : [];
if ($legacyEdit && $legacyId) {
    $legacyParams['id'] = $legacyId;
}
redirect(adminPath($legacySection, $legacyParams));
