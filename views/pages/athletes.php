<header class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= e(t('athletes.lead')) ?></p>
  </div>
  <div class="actions">
    <a class="btn btn--primary btn--sm" href="<?= url('network.php#search') ?>"><?= e(t('athletes.invite')) ?></a>
  </div>
</header>

<?php if ($requests): ?>
<section class="panel people-panel">
  <h2><?= e(t('net.requests')) ?></h2>
  <?php view('partials/people', ['people' => $requests, 'mode' => 'coaching']); ?>
</section>
<?php endif; ?>

<?php if ($athletes): ?>
  <?php view('partials/athletes_table', ['athletes' => $athletes]); ?>
<?php else: ?>
  <section class="empty empty--sm">
    <h2><?= e(t('athletes.empty.title')) ?></h2>
    <p><?= e(t('athletes.empty.lead')) ?></p>
    <a class="btn btn--primary" href="<?= url('network.php#search') ?>"><?= e(t('athletes.invite')) ?></a>
  </section>
<?php endif; ?>
