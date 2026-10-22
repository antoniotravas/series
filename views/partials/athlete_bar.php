<?php
/** Barra do treinador quando está a ver os dados de um atleta. */
/** @var array $athlete */
/** @var string $current dashboard | workouts */
$aid = (int)$athlete['id'];
?>
<div class="athlete-bar">
  <a class="link small" href="<?= url('athletes.php') ?>"><?= e(t('athletes.back')) ?></a>
  <div class="athlete-bar__who">
    <?php view('partials/avatar', ['u' => $athlete, 'size' => 'sm']); ?>
    <strong><?= e($athlete['display_name']) ?></strong>
    <span class="badge"><?= e(t('athletes.coach_view')) ?></span>
  </div>
  <nav class="viewtoggle" aria-label="<?= e($athlete['display_name']) ?>">
    <a class="chip<?= $current === 'dashboard' ? ' is-on' : '' ?>" href="<?= url('dashboard.php?u=' . $aid) ?>"<?= $current === 'dashboard' ? ' aria-current="page"' : '' ?>><?= e(t('nav.dashboard')) ?></a>
    <a class="chip<?= $current === 'workouts' ? ' is-on' : '' ?>" href="<?= url('workouts.php?u=' . $aid) ?>"<?= $current === 'workouts' ? ' aria-current="page"' : '' ?>><?= e(t('nav.workouts')) ?></a>
  </nav>
  <a class="btn btn--primary btn--sm athlete-bar__new" href="<?= url('workout_edit.php?for=' . $aid) ?>">+ <?= e(t('athletes.log_for')) ?></a>
</div>
