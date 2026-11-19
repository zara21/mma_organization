<?php
require_once __DIR__ . '/../includes/app.php';
require_once APP_ROOT . '/includes/admin.php';

$account = requireAdmin();
$adminId = (int) $account['user_id'];
$resources = adminResources();
$section = input($_GET, 'section');
$manageRankings = $section === 'rankings';
$viewActivity = $section === 'activity';
if ($section !== '' && !$manageRankings && !$viewActivity && !isset($resources[$section])) {
    showError(404, 'Admin section not found.');
}
$id = max(0, (int) input($_GET, 'id'));
$mode = input($_GET, 'mode');
$editing = isset($resources[$section]) && $mode === 'edit';
$error = '';
$notice = $_SESSION['admin_notice'] ?? '';
unset($_SESSION['admin_notice']);

if ($viewActivity) {
    $activityCount = (int) query('SELECT COUNT(*) FROM admin_actions')->fetchColumn();
    $activityPages = max(1, (int) ceil($activityCount / 30));
    $activityPage = max(1, min($activityPages, (int) input($_GET, 'page', '1')));
    $offset = ($activityPage - 1) * 30;
    $activity = query('SELECT aa.action_type, aa.description, UNIX_TIMESTAMP(aa.created_at) AS occurred, u.username
                       FROM admin_actions aa LEFT JOIN users u ON u.user_id = aa.admin_id
                       ORDER BY aa.action_id DESC LIMIT 30 OFFSET ' . $offset)->fetchAll();
} elseif ($manageRankings) {
    $divisions = query('SELECT w.weight_class_id, w.name FROM weight_classes w
        ORDER BY EXISTS (SELECT 1 FROM fighters f WHERE f.weight_class_id = w.weight_class_id) DESC, w.weight_class_id')->fetchAll(PDO::FETCH_KEY_PAIR);
    $division = (int) input($_GET, 'division', (string) array_key_first($divisions));
    if ($division && !isset($divisions[$division])) {
        showError(404, 'Weight class not found.');
    }
    $submitted = $_POST['rankings'] ?? [];
    $submittedVersion = input($_POST, 'version');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyPost();
        if (!isset($divisions[$division]) || !is_array($submitted)) {
            showError(400, 'Choose a valid weight class and submit its rankings.');
        }
        try {
            saveDivisionRankings($division, $submitted, $submittedVersion, $adminId);
            $_SESSION['admin_notice'] = 'Rankings saved.';
            redirect(adminPath('rankings', ['division' => $division]));
        } catch (DomainException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $error = 'The rankings could not be saved. Try again.';
        }
    }
    $rankedFighters = $division ? query('SELECT fighter_id, name, ranking FROM fighters WHERE weight_class_id = ?
        ORDER BY ranking IS NULL, ranking, name, fighter_id', [$division])->fetchAll() : [];
    $version = rankingVersion($rankedFighters);
} elseif ($section !== '') {
    [$label, $pk, $nameColumn, $fields] = $resources[$section];
    $recordName = match ($section) {
        'events' => 'event',
        'fighters' => 'fighter',
        'news' => 'article',
        'weight_classes' => 'weight class',
        'fights' => 'fight',
    };
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyPost();
        $operation = input($_POST, 'operation');
        try {
            if ($operation === 'delete') {
                deleteAdminRecord($section, $pk, $id, $adminId);
                $_SESSION['admin_notice'] = 'Record deleted.';
                redirect(adminPath($section));
            }
            if ($operation === 'save' && $editing) {
                $id = saveAdminRecord($section, $pk, $fields, $id, $adminId);
                $_SESSION['admin_notice'] = 'Changes saved.';
                redirect(adminPath($section, ['mode' => 'edit', 'id' => $id]));
            }
            if ($section === 'events' && $editing && $id && in_array($operation, ['add_bout', 'remove_bout', 'save_result'], true)) {
                saveFightCard($id, $operation, $adminId);
                $_SESSION['admin_notice'] = 'Fight card updated.';
                redirect(adminPath($section, ['mode' => 'edit', 'id' => $id]) . '#fight-card');
            }
            showError(400, 'Unknown admin action.');
        } catch (DomainException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $error = in_array((int) ($exception->errorInfo[1] ?? 0), [1451, 1452], true)
                ? 'This record is linked to another entry. Review the related records and try again.'
                : 'The change could not be saved. Check the values and try again.';
        }
    }
}

if ($editing) {
    $row = $id ? query("SELECT * FROM $section WHERE $pk = ?", [$id])->fetch() : [];
    if ($id && !$row) {
        showError(404, 'Record not found.');
    }
}
pageStart('Admin panel', '', true);
?>
<main class="shell admin-shell">
    <aside class="panel admin-nav">
        <h2>Admin panel</h2>
        <nav aria-label="Admin navigation">
            <a href="<?= url(adminPath()) ?>" <?= $section === '' ? 'aria-current="page"' : '' ?>>Overview</a>
            <a href="<?= url(adminPath('rankings')) ?>" <?= $manageRankings ? 'aria-current="page"' : '' ?>>Rankings</a>
            <?php foreach ($resources as $key => [$resourceLabel]): ?>
                <a href="<?= url(adminPath($key)) ?>" <?= $section === $key ? 'aria-current="page"' : '' ?>><?= h($resourceLabel) ?></a>
            <?php endforeach; ?>
            <a href="<?= url(adminPath('activity')) ?>" <?= $viewActivity ? 'aria-current="page"' : '' ?>>Activity</a>
        </nav>
        <a class="text-link" href="<?= url() ?>">View website</a>
    </aside>
    <div class="admin-main">
        <?php if ($error): ?><p class="alert error" role="alert"><?= h($error) ?></p><?php endif; ?>
        <?php if ($notice): ?><p class="alert" role="status"><?= h($notice) ?></p><?php endif; ?>
        <?php if (!$section): ?>
            <?php pageHeading('Overview', 'Manage your events, roster, rankings and editorial content.'); ?>
            <div class="admin-totals">
                <?php foreach ($resources as $table => [$resourceLabel]): ?>
                    <a class="panel metric" href="<?= url(adminPath($table)) ?>"><span><?= h($resourceLabel) ?></span><strong><?= (int) query("SELECT COUNT(*) FROM $table")->fetchColumn() ?></strong></a>
                <?php endforeach; ?>
            </div>
            <section class="panel admin-panel">
                <?php sectionTitle('Recent events', adminPath('events'), 'Manage events'); ?>
                <div class="table-wrap"><table>
                    <thead><tr><th>Event</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach (query('SELECT * FROM events ORDER BY event_date DESC LIMIT 5') as $event): ?>
                        <tr><td><a href="<?= url(adminPath('events', ['mode' => 'edit', 'id' => $event['event_id']])) ?>"><?= h($event['title']) ?></a></td><td><?= h(dateLabel($event['event_date'])) ?></td><td><?= h(eventState($event)) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            </section>
        <?php elseif ($viewActivity): ?>
            <?php pageHeading('Activity', 'Recent result, ranking and article status changes.'); ?>
            <?php if (!$activity): ?>
                <p class="empty">No changes have been recorded yet.</p>
            <?php else: ?>
                <div class="panel table-wrap"><table>
                    <thead><tr><th scope="col">When</th><th scope="col">Changed by</th><th scope="col">Change</th></tr></thead>
                    <tbody>
                        <?php foreach ($activity as $entry): ?>
                            <?php $occurred = (int) $entry['occurred']; ?>
                            <tr>
                                <td><time datetime="<?= h(date('c', $occurred)) ?>"><?= h(date('M j, Y · H:i T', $occurred)) ?></time></td>
                                <td><?= h($entry['username'] ?? 'System') ?></td>
                                <td><strong><?= h($entry['action_type']) ?></strong><p class="muted"><?= h($entry['description']) ?></p></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
                <?php pagination($activityPage, $activityPages, ['section' => 'activity'], 'Activity pages'); ?>
            <?php endif; ?>
        <?php elseif ($manageRankings): ?>
            <?php pageHeading('Rankings', 'Update fighter positions by weight class.'); ?>
            <?php if (!$divisions): ?>
                <p class="empty">Add a weight class before editing rankings.</p>
            <?php else: ?>
                <form class="filters filter-form" method="get">
                    <input type="hidden" name="section" value="rankings">
                    <label>Weight class<select name="division">
                        <?php foreach ($divisions as $key => $name): ?><option value="<?= (int) $key ?>" <?= $division === (int) $key ? 'selected' : '' ?>><?= h($name) ?></option><?php endforeach; ?>
                    </select></label>
                    <button class="button button-outline">Show rankings</button>
                </form>
                <?php if (!$rankedFighters): ?>
                    <p class="empty">There are no fighters in this weight class.</p>
                <?php else: ?>
                    <form method="post" action="<?= h(url(adminPath('rankings', ['division' => $division]))) ?>" class="panel admin-panel ranking-editor" data-ranking-editor>
                        <?php csrfField(); ?>
                        <input type="hidden" name="version" value="<?= h($version) ?>">
                        <h2><?= h($divisions[$division]) ?></h2>
                        <p class="muted" id="rank-help">Use 0 for champion and leave a position blank to unrank a fighter. Enter the same number for a shared position.</p>
                        <?php if (count($rankedFighters) > 1): ?>
                            <p class="muted" data-rank-swap-guide hidden>Drag a fighter’s handle onto another fighter to swap positions, or select two handles.</p>
                        <?php endif; ?>
                        <div class="table-wrap"><table>
                            <thead><tr><th scope="col">Fighter</th><th scope="col">Position</th></tr></thead>
                            <tbody>
                                <?php foreach ($rankedFighters as $fighter): ?>
                                    <?php
                                    $fighterId = (int) $fighter['fighter_id'];
                                    $value = $version === $submittedVersion && is_array($submitted)
                                        ? input($submitted, (string) $fighterId, (string) $fighter['ranking']) : $fighter['ranking'];
                                    ?>
                                    <tr data-ranking-row>
                                        <td><div class="ranking-athlete">
                                            <?php if (count($rankedFighters) > 1): ?>
                                                <button class="rank-swap" type="button" data-rank-swap hidden aria-pressed="false" aria-label="<?= h('Swap position for ' . $fighter['name']) ?>"></button>
                                            <?php endif; ?>
                                            <span data-ranked-name><?= h($fighter['name']) ?></span>
                                        </div></td>
                                        <td><input type="number" min="0" max="<?= MAX_FIGHTER_RANK ?>" step="1" name="rankings[<?= $fighterId ?>]"
                                            value="<?= h($value) ?>" aria-label="<?= h('Ranking for ' . $fighter['name']) ?>" aria-describedby="rank-help"></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table></div>
                        <p class="muted rank-swap-status" data-rank-swap-status role="status"></p>
                        <button class="button button-primary">Save rankings</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif ($editing): ?>
            <div class="section-head"><h1><?= $id ? 'Edit' : 'Add' ?> <?= h($recordName) ?></h1><a class="text-link" href="<?= url(adminPath($section)) ?>"><?= h($label) ?></a></div>
            <section class="panel admin-panel">
                <h2><?= h($row[$nameColumn] ?? $label) ?></h2>
                <form class="admin-form" method="post" enctype="multipart/form-data">
                    <?php csrfField(); ?>
                    <input type="hidden" name="operation" value="save">
                    <?php foreach ($fields as $key => ['label' => $fieldLabel, 'type' => $type, 'required' => $required, 'options' => $options]): ?>
                        <?php
                        $value = $row[$key] ?? (in_array($key, ['wins', 'losses', 'draws', 'is_featured'], true) ? '0' : '');
                        if ($key === 'finish_time') {
                            $value = finishTime(isset($row['finish_seconds']) ? (int) $row['finish_seconds'] : null);
                        }
                        if ($type === 'datetime-local' && $value) {
                            $value = str_replace(' ', 'T', $value);
                        }
                        if (input($_POST, 'operation') === 'save' && $type !== 'file') {
                            $value = input($_POST, $key, (string) $value);
                        }
                        if ($section === 'fights' && $key === 'method') {
                            fightMethodPicker((string) $value, input($_POST, 'operation') === 'save' ? input($_POST, 'method_other') : null);
                            continue;
                        }
                        ?>
                        <label class="<?= $type === 'textarea' ? 'wide' : '' ?>"><?= h($fieldLabel) ?>
                            <?php if ($type === 'select'): ?>
                                <select name="<?= h($key) ?>" <?= $required ? 'required' : '' ?>><option value=""><?= $required ? 'Select an option' : 'None' ?></option>
                                    <?php foreach ($options as $option => $caption): ?><option value="<?= h($option) ?>" <?= (string) $value === (string) $option ? 'selected' : '' ?>><?= h($caption) ?></option><?php endforeach; ?>
                                </select>
                                <?php if ($section === 'events' && $key === 'status'): ?>
                                    <small class="muted">Live starts at the scheduled time and lasts up to <?= intdiv(EVENT_LIVE_WINDOW_SECONDS, 3600) ?> hours. Set Past to end it early.</small>
                                <?php endif; ?>
                            <?php elseif ($type === 'textarea'): ?>
                                <textarea name="<?= h($key) ?>" rows="10" maxlength="<?= (int) $options ?>" required><?= h($value) ?></textarea>
                            <?php elseif ($type === 'file'): ?>
                                <input type="file" name="<?= h($key) ?>" accept="image/jpeg,image/png,image/webp,image/avif">
                                <?php if ($value): ?><small class="muted">Upload a replacement to change the current image.</small><?php endif; ?>
                            <?php else: ?>
                                <input type="<?= $type === 'decimal' ? 'number' : h($type) ?>" name="<?= h($key) ?>" value="<?= h($value) ?>" <?= $required ? 'required' : '' ?>
                                    <?= in_array($type, ['text', 'url'], true) && $options ? 'maxlength="' . (int) $options . '"' : '' ?>
                                    <?= in_array($type, ['number', 'decimal'], true) ? 'min="' . ($key === 'finish_round' ? '1' : '0') . '" max="' . h($options) . '" step="' . ($type === 'decimal' ? '0.01' : '1') . '"' : '' ?>
                                    <?= $type === 'datetime-local' ? 'step="1"' : '' ?>>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                    <div class="wide"><button class="button button-primary">Save changes</button></div>
                </form>
            </section>
            <?php if ($section === 'events' && $id): ?>
                <section id="fight-card" class="panel admin-panel">
                    <h2>Fight card</h2>
                    <p class="muted">Results update both fighters’ records. A no contest adds no win, loss or draw.</p>
                    <div class="admin-bouts">
                        <?php foreach (eventFights($id) as $fight): ?>
                            <?php
                            $pairLabel = $fight['first_name'] . ' vs ' . $fight['second_name'];
                            $editingResult = input($_POST, 'operation') === 'save_result' && (int) input($_POST, 'bout') === (int) $fight['fight_id'];
                            $resultValue = $editingResult ? input($_POST, 'result') : ($fight['result'] ?: 'Pending');
                            $methodValue = $editingResult ? input($_POST, 'method') : $fight['method'];
                            $roundValue = $editingResult ? input($_POST, 'finish_round') : $fight['finish_round'];
                            $timeValue = $editingResult ? input($_POST, 'finish_time') : finishTime($fight['finish_seconds']);
                            ?>
                            <div class="admin-bout">
                                <form method="post" class="admin-result-form">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="operation" value="save_result"><input type="hidden" name="bout" value="<?= (int) $fight['fight_id'] ?>">
                                    <h3><?= h($pairLabel) ?></h3>
                                    <div class="admin-result-fields">
                                        <label>Result<select name="result">
                                            <?php foreach (['Pending' => 'Pending', 'Win' => $fight['first_name'] . ' wins', 'Loss' => $fight['second_name'] . ' wins', 'Draw' => 'Draw', 'No contest' => 'No contest'] as $result => $caption): ?>
                                                <option value="<?= h($result) ?>" <?= $resultValue === $result ? 'selected' : '' ?>><?= h($caption) ?></option>
                                            <?php endforeach; ?>
                                        </select></label>
                                        <?php fightMethodPicker((string) $methodValue, $editingResult ? input($_POST, 'method_other') : null); ?>
                                        <label>Finish round<input type="number" name="finish_round" min="1" max="99" value="<?= h($roundValue) ?>"></label>
                                        <label>Time into round<input name="finish_time" maxlength="5" value="<?= h($timeValue) ?>" placeholder="M:SS"></label>
                                    </div>
                                    <button class="button button-outline">Save result</button>
                                </form>
                                <?php $mainPair = [(int) $row['main_fighter1_id'], (int) $row['main_fighter2_id']]; ?>
                                <?php if (!in_array($mainPair, [[(int) $fight['fighter1_id'], (int) $fight['fighter2_id']], [(int) $fight['fighter2_id'], (int) $fight['fighter1_id']]], true)): ?>
                                    <form method="post" data-confirm="Remove this matchup from the fight card?">
                                        <?php csrfField(); ?><input type="hidden" name="operation" value="remove_bout"><input type="hidden" name="bout" value="<?= (int) $fight['fight_id'] ?>">
                                        <button class="button button-danger" aria-label="<?= h('Remove ' . $pairLabel) ?>">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach (recordedEventFights($id) as $fight): ?>
                            <div class="admin-bout">
                                <div class="admin-result-form">
                                    <h3><?= h($fight['first_name'] . ' vs ' . $fight['second_name']) ?></h3>
                                    <p class="muted"><?= h(adminBoutLabel($fight)) ?></p>
                                </div>
                                <a class="button button-outline" href="<?= h(url(adminPath('fights', ['mode' => 'edit', 'id' => $fight['fight_id']]))) ?>">Edit recorded fight</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form class="inline-form add-bout" method="post">
                        <?php csrfField(); ?><input type="hidden" name="operation" value="add_bout">
                        <?php foreach (['first' => 'Fighter 1', 'second' => 'Fighter 2'] as $key => $caption): ?>
                            <label><?= $caption ?><select name="<?= $key ?>" required><option value="">Select fighter</option>
                                <?php foreach ($fields['main_fighter1_id']['options'] as $fighterId => $name): ?><option value="<?= (int) $fighterId ?>"><?= h($name) ?></option><?php endforeach; ?>
                            </select></label>
                        <?php endforeach; ?>
                        <button class="button button-primary">Add matchup</button>
                    </form>
                </section>
            <?php endif; ?>
            <?php if ($id): ?>
                <form class="delete-form" method="post" data-confirm="Permanently delete this record?">
                    <?php csrfField(); ?><input type="hidden" name="operation" value="delete"><button class="button button-danger">Delete <?= h($recordName) ?></button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <?php
            $search = input($_GET, 'search');
            $matches = ' WHERE ' . $nameColumn . ' LIKE ?';
            $pattern = likePattern($search);
            $total = (int) query("SELECT COUNT(*) FROM $section" . $matches, [$pattern])->fetchColumn();
            $pages = max(1, (int) ceil($total / 25));
            $page = max(1, min($pages, (int) input($_GET, 'page', '1')));
            $offset = ($page - 1) * 25;
            $rows = query("SELECT * FROM $section" . $matches . " ORDER BY $pk DESC LIMIT 25 OFFSET $offset", [$pattern])->fetchAll();
            ?>
            <div class="section-head"><h1><?= h($label) ?></h1><a class="button button-primary" href="<?= url(adminPath($section, ['mode' => 'edit'])) ?>">Add <?= h($recordName) ?></a></div>
            <form class="filters filter-form" method="get"><input type="hidden" name="section" value="<?= h($section) ?>">
                <label>Search <?= h(strtolower($label)) ?><input type="search" name="search" value="<?= h($search) ?>"></label><button class="button button-outline">Search</button>
            </form>
            <?php if (!$rows): ?><p class="empty">No records found.</p><?php else: ?>
                <div class="panel table-wrap"><table>
                    <thead><tr><th><?= h($fields[$nameColumn]['label']) ?></th>
                        <?php if (in_array($section, ['events', 'news'], true)): ?><th>Status</th><?php endif; ?>
                        <?php if ($section === 'fighters'): ?><th>Ranking</th><?php endif; ?>
                        <th>Actions</th></tr></thead>
                    <tbody><?php foreach ($rows as $entry): ?>
                        <tr><td><?= h($entry[$nameColumn] ?: 'Recorded fight') ?></td>
                            <?php if ($section === 'events'): ?><td><?= h(eventState($entry)) ?></td><?php endif; ?>
                            <?php if ($section === 'news'): ?><td><?= $entry['status'] === 'active' ? 'Published' : 'Draft' ?></td><?php endif; ?>
                            <?php if ($section === 'fighters'): ?><td><?= $entry['ranking'] === null ? 'Unranked' : ((int) $entry['ranking'] === 0 ? 'Champion' : '#' . (int) $entry['ranking']) ?></td><?php endif; ?>
                            <td><a class="text-link" href="<?= url(adminPath($section, ['mode' => 'edit', 'id' => $entry[$pk]])) ?>">Edit</a></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
            <?php if ($pages > 1): ?>
                <nav class="filters pagination" aria-label="Admin pages">
                    <?php if ($page > 1): ?><a class="chip" href="<?= h(url(adminPath($section, ['search' => $search, 'page' => $page - 1]))) ?>">Previous</a><?php endif; ?>
                    <span class="chip selected">Page <?= $page ?> of <?= $pages ?></span>
                    <?php if ($page < $pages): ?><a class="chip" href="<?= h(url(adminPath($section, ['search' => $search, 'page' => $page + 1]))) ?>">Next</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php pageEnd(); ?>
