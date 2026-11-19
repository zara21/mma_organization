<?php
require __DIR__ . '/../includes/app.php';
pageStart('About');
?>
<main class="shell section">
    <?php pageHeading('About MMA Organization', 'A home for mixed martial arts fans.'); ?>
    <section class="panel prose"><h2>Follow the sport</h2><p>Explore event fight cards and results, browse athlete profiles and weight-class rankings, and catch up on MMA news and highlights.</p><a class="button button-primary" href="<?= url('/views/events') ?>">Explore events</a></section>
</main>
<?php pageEnd(); ?>
