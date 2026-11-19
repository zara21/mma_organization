<?php
require_once __DIR__ . '/../includes/app.php';
require_once APP_ROOT . '/includes/admin.php';

$account = requireAdmin();
$resources = adminResources();
$section = input($_GET, 'section');
if ($section !== '' && !isset($resources[$section])) {
    showError(404, 'Admin section not found.');
}
$id = max(0, (int) input($_GET, 'id'));
$mode = input($_GET, 'mode');
$editing = $section !== '' && $mode === 'edit';
$error = '';
$notice = $_SESSION['admin_notice'] ?? '';
unset($_SESSION['admin_notice']);

if ($section !== '') {
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
                deleteAdminRecord($section, $pk, $id);
                $_SESSION['admin_notice'] = 'Record deleted.';
                redirect(adminPath($section));
            }
            if ($operation === 'save' && $editing) {
                $id = saveAdminRecord($section, $pk, $fields, $id);
                $_SESSION['admin_notice'] = 'Changes saved.';
                redirect(adminPath($section, ['mode' => 'edit', 'id' => $id]));
            }
            if ($section === 'events' && $editing && $id && in_array($operation, ['add_bout', 'remove_bout', 'save_result'], true)) {
                saveFightCard($id, $operation);
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
            <?php foreach ($resources as $key => [$resourceLabel]): ?>
                <a href="<?= url(adminPath($key)) ?>" <?= $section === $key ? 'aria-current="page"' : '' ?>><?= h($resourceLabel) ?></a>
            <?php endforeach; ?>
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
                        if ($type === 'datetime-local' && $value) {
                            $value = str_replace(' ', 'T', $value);
                        }
                        if (input($_POST, 'operation') === 'save' && $type !== 'file') {
                            $value = input($_POST, $key, (string) $value);
                        }
                        ?>
                        <label class="<?= $type === 'textarea' ? 'wide' : '' ?>"><?= h($fieldLabel) ?>
                            <?php if ($type === 'select'): ?>
                                <select name="<?= h($key) ?>" <?= $required ? 'required' : '' ?>><option value=""><?= $required ? 'Select an option' : 'None' ?></option>
                                    <?php foreach ($options as $option => $caption): ?><option value="<?= h($option) ?>" <?= (string) $value === (string) $option ? 'selected' : '' ?>><?= h($caption) ?></option><?php endforeach; ?>
                                </select>
                            <?php elseif ($type === 'textarea'): ?>
                                <textarea name="<?= h($key) ?>" rows="10" maxlength="<?= (int) $options ?>" required><?= h($value) ?></textarea>
                            <?php elseif ($type === 'file'): ?>
                                <input type="file" name="<?= h($key) ?>" accept="image/jpeg,image/png,image/webp,image/avif">
                                <?php if ($value): ?><small class="muted">Upload a replacement to change the current image.</small><?php endif; ?>
                            <?php else: ?>
                                <input type="<?= $type === 'decimal' ? 'number' : h($type) ?>" name="<?= h($key) ?>" value="<?= h($value) ?>" <?= $required ? 'required' : '' ?>
                                    <?= in_array($type, ['text', 'url'], true) && $options ? 'maxlength="' . (int) $options . '"' : '' ?>
                                    <?= in_array($type, ['number', 'decimal'], true) ? 'min="0" max="' . h($options) . '" step="' . ($type === 'decimal' ? '0.01' : '1') . '"' : '' ?>
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
                    <p class="muted">Saving or correcting a result updates both fighters’ records.</p>
                    <div class="admin-bouts">
                        <?php foreach (eventFights($id) as $fight): ?>
                            <?php $pairLabel = $fight['first_name'] . ' vs ' . $fight['second_name']; ?>
                            <div class="admin-bout">
                                <form method="post" class="inline-form">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="operation" value="save_result"><input type="hidden" name="bout" value="<?= (int) $fight['fight_id'] ?>">
                                    <label><?= h($pairLabel) ?><select name="result">
                                        <?php foreach (['Pending' => 'Pending', 'Win' => $fight['first_name'] . ' wins', 'Loss' => $fight['second_name'] . ' wins', 'Draw' => 'Draw'] as $result => $caption): ?>
                                            <option value="<?= h($result) ?>" <?= ($fight['result'] ?: 'Pending') === $result ? 'selected' : '' ?>><?= h($caption) ?></option>
                                        <?php endforeach; ?>
                                    </select></label>
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
            $pattern = '%' . $search . '%';
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
                            <?php if ($section === 'fighters'): ?><td><?= h($entry['ranking'] ?? 'Unranked') ?></td><?php endif; ?>
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
