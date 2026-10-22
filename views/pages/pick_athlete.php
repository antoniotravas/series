<?php /** @var array $athletes */ ?>
<header class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= e(t('w.pick_athlete')) ?></p>
  </div>
</header>

<ul class="pick-list">
  <?php foreach ($athletes as $a): ?>
  <li>
    <a href="<?= url('workout_edit.php?for=' . (int)$a['id']) ?>">
      <?php view('partials/avatar', ['u' => $a, 'size' => 'md']); ?>
      <span><strong><?= e($a['display_name']) ?></strong>
        <?php if ($a['last_workout']): ?><small><?= e(t('athletes.col.last')) ?>: <?= e(fmt_date($a['last_workout'])) ?></small><?php endif; ?></span>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
