<?php
require __DIR__ . '/../includes/app.php';

$user = currentUser();
if (!$user) {
    redirect('/routes/login');
}
pageStart('My account');
?>
<main class="shell section">
    <?php pageHeading('My account', 'View your account details.'); ?>
    <div class="panel prose"><h2><?= h($user['username']) ?></h2><p><?= h($user['email']) ?></p>
        <?php if (isAdmin($user)): ?><a class="button button-primary" href="<?= url('/views/admin') ?>">Open admin panel</a><?php endif; ?>
    </div>
</main>
<?php pageEnd(); ?>
