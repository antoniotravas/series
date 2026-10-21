<?php
$volume = 0;
foreach ($w['sets'] as $s) {
    $volume += $s['distance_m'] * count($s['reps']);
}
$facts = array_filter([
    t('w.location') => $w['location'],
    t('w.time')     => $w['start_time'] ? substr($w['start_time'], 0, 5) : null,
    t('w.venue')    => t('w.venue.' . $w['venue_type']),
    t('w.weather')  => trim(($w['weather'] ?? '') . ($w['temperature_c'] !== null ? ' ' . $w['temperature_c'] . ' °C' : '')) ?: null,
    t('w.rpe')      => $w['rpe'] ? $w['rpe'] . '/10' : null,
]);
?>
<p class="back"><a class="link" href="<?= url('workouts.php') ?>"><?= e(t('w.back')) ?></a></p>

<header class="page-head page-head--workout">
  <div>
    <p class="muted"><time datetime="<?= e($w['workout_date']) ?>"><?= e(fmt_date($w['workout_date'], true)) ?></time><?php if (!$w['active']): ?> <span class="badge"><?= e(t('w.inactive')) ?></span><?php endif; ?></p>
    <h1><?= e($w['title'] ?: sets_summary($w['sets'])) ?></h1>
    <?php if ($w['title']): ?><p class="lead-sets"><?= e(sets_summary($w['sets'])) ?></p><?php endif; ?>
    <p class="muted"><?= e(t('w.summary_volume', ['km' => fmt_km($volume)])) ?></p>
  </div>
  <div class="actions">
    <a class="btn btn--ghost btn--sm" href="<?= url('workout_edit.php?copy=' . (int)$w['id']) ?>" title="<?= e(t('w.dup_title')) ?>"><?= e(t('w.dup')) ?></a>
    <a class="btn btn--ghost btn--sm" href="<?= url('workout_edit.php?id=' . (int)$w['id']) ?>"><?= e(t('w.edit')) ?></a>
    <form method="post" action="<?= url('workouts_bulk.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="ids[]" value="<?= (int)$w['id'] ?>">
      <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
      <button class="btn btn--ghost btn--sm" type="submit" name="action" value="<?= $w['active'] ? 'deactivate' : 'activate' ?>" title="<?= e(t('w.inactive_hint')) ?>"><?= e(t($w['active'] ? 'w.deactivate' : 'w.activate')) ?></button>
    </form>
    <form method="post" action="<?= url('workout_delete.php') ?>" data-confirm="<?= e(t('w.delete_confirm')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
      <button class="btn btn--danger btn--sm" type="submit"><?= e(t('w.delete')) ?></button>
    </form>
  </div>
</header>

<?php if ($facts): ?>
<dl class="facts">
  <?php foreach ($facts as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?>
</dl>
<?php endif; ?>

<?php
$maxSpeed = 0.0;
foreach ($w['sets'] as $s) {
    if ($s['reps']) {
        $maxSpeed = max($maxSpeed, $s['distance_m'] / min($s['reps']));
    }
}
?>
<div class="viewbar" data-lanes-toggle hidden>
  <div class="viewtoggle" role="group" aria-label="<?= e(t('w.chart.label')) ?>">
    <button type="button" class="chip is-on" data-view="dev" aria-pressed="true"><?= e(t('w.chart.dev')) ?></button>
    <button type="button" class="chip" data-view="speed" aria-pressed="false"><?= e(t('w.chart.speed')) ?></button>
  </div>
</div>

<div class="sets" data-view="dev">
  <?php foreach ($w['sets'] as $i => $s): ?>
    <?php view('partials/set_lanes', ['set' => $s, 'index' => $i + 1, 'pbs' => $pbs, 'maxSpeed' => $maxSpeed]); ?>
  <?php endforeach; ?>
</div>

<?php if ($w['notes']): ?>
<section class="notes">
  <h2><?= e(t('w.notes')) ?></h2>
  <p><?= nl2br(e($w['notes'])) ?></p>
</section>
<?php endif; ?>
