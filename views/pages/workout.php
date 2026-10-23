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
<p class="back"><a class="link" href="<?= e(url($back)) ?>"><?= e(t($isOwner ? 'w.back' : 'w.back_other')) ?></a></p>

<?php if (!$isOwner): ?>
<a class="author" href="<?= url('user.php?id=' . (int)$owner['id']) ?>">
  <?php view('partials/avatar', ['u' => $owner, 'size' => 'sm']); ?>
  <span><?= e($owner['display_name']) ?></span>
</a>
<?php endif; ?>

<header class="page-head page-head--workout">
  <div>
    <p class="muted"><time datetime="<?= e($w['workout_date']) ?>"><?= e(fmt_date($w['workout_date'], true)) ?></time><?php if (!$w['active']): ?> <span class="badge"><?= e(t('w.inactive')) ?></span><?php endif; ?>
      <?php if ($isOwner): ?> <span class="badge badge--vis-<?= e($w['visibility']) ?>" title="<?= e(t('vis.label')) ?>"><?= e(t('vis.' . $w['visibility'])) ?></span><?php endif; ?></p>
    <h1><?= e($w['title'] ?: sets_summary($w['sets'])) ?></h1>
    <?php if ($w['title']): ?><p class="lead-sets"><?= e(sets_summary($w['sets'])) ?></p><?php endif; ?>
    <p class="muted"><?= e(t('w.summary_volume', ['km' => fmt_km($volume)])) ?></p>
    <?php if ($loggedBy): ?><p class="logged-by"><?= e(t('w.logged_by', ['name' => $loggedBy['display_name']])) ?></p><?php endif; ?>
  </div>
  <?php if ($isOwner): ?>
  <div class="actions">
    <?php if ($w['sets']): ?><button type="button" class="btn btn--primary btn--sm" data-share-open><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 8l5-5 5 5M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6"/></svg><?= e(t('share.btn')) ?></button>
    <a class="btn btn--ghost btn--sm" href="<?= url('compare.php?a=' . (int)$w['id']) ?>"><?= e(t('cmp.btn')) ?></a><?php endif; ?>
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
  <?php elseif ($full): ?>
  <div class="actions">
    <?php if ($w['sets']): ?><button type="button" class="btn btn--primary btn--sm" data-share-open><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 8l5-5 5 5M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6"/></svg><?= e(t('share.btn')) ?></button>
    <a class="btn btn--ghost btn--sm" href="<?= url('compare.php?a=' . (int)$w['id']) ?>"><?= e(t('cmp.btn')) ?></a><?php endif; ?>
    <?php if ($canEdit): ?>
    <a class="btn btn--ghost btn--sm" href="<?= url('workout_edit.php?id=' . (int)$w['id']) ?>"><?= e(t('w.edit')) ?></a>
    <form method="post" action="<?= url('workout_delete.php') ?>" data-confirm="<?= e(t('w.delete_confirm')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
      <button class="btn btn--danger btn--sm" type="submit"><?= e(t('w.delete')) ?></button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
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
<?php
// blocos com a mesma distância (ex.: 2 × 5 × 400 m): média de todas as repetições juntas
$byDist = [];
foreach ($w['sets'] as $s) {
    $byDist[(int)$s['distance_m']][] = $s;
}
$combined = array_filter($byDist, fn($g) => count($g) > 1);
?>
<?php foreach ($combined as $dist => $group):
    $all = array_merge(...array_map(fn($s) => $s['reps'], $group));
    $st = set_stats($all);
?>
<section class="combined" aria-label="<?= e(t('w.combined', ['d' => fmt_distance($dist), 'n' => count($group)])) ?>">
  <h2 class="combined__title"><?= e(t('w.combined', ['d' => fmt_distance($dist), 'n' => count($group)])) ?>
    <span class="set__meta"><?= e(t('w.combined_reps', ['n' => count($all)])) ?></span></h2>
  <dl class="set__stats">
    <div><dt><?= e(t('w.avg')) ?></dt><dd><?= fmt_time($st['avg'], 1) ?></dd></div>
    <div><dt><?= e(t('w.best')) ?></dt><dd><?= fmt_time($st['best'], 1) ?></dd></div>
    <div><dt><?= e(t('w.sd')) ?></dt><dd>±<?= number_format($st['sd'], 1) ?>s</dd></div>
    <div><dt><?= e(t('w.pace')) ?></dt><dd><?= fmt_pace($dist, $st['avg']) ?><small><?= e(t('w.per_km')) ?></small></dd></div>
  </dl>
</section>
<?php endforeach; ?>

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

<?php if ($full && $w['sets']): ?>
<dialog class="share" data-share aria-labelledby="share-h">
  <div class="share__head">
    <h2 id="share-h"><?= e(t('share.title')) ?></h2>
    <button type="button" class="btn btn--icon" data-share-close aria-label="<?= e(t('share.close')) ?>">✕</button>
  </div>
  <div class="share__body">
    <figure class="share__preview" data-format="post"><canvas data-share-canvas role="img" aria-label="<?= e(t('share.preview')) ?>"></canvas></figure>
    <div class="share__opts">
      <fieldset>
        <legend><?= e(t('share.format')) ?></legend>
        <div class="viewtoggle" role="group">
          <button type="button" class="chip is-on" data-share-format="post" aria-pressed="true"><?= e(t('share.post')) ?> <small>4:5</small></button>
          <button type="button" class="chip" data-share-format="story" aria-pressed="false"><?= e(t('share.story')) ?> <small>9:16</small></button>
          <button type="button" class="chip" data-share-format="fit" aria-pressed="false"><?= e(t('share.fit')) ?></button>
        </div>
      </fieldset>
      <fieldset>
        <legend><?= e(t('share.content')) ?></legend>
        <div class="viewtoggle" role="group">
          <button type="button" class="chip is-on" data-share-content="chart" aria-pressed="true"><?= e(t('share.content.chart')) ?></button>
          <button type="button" class="chip" data-share-content="lines" aria-pressed="false"><?= e(t('share.content.lines')) ?></button>
          <button type="button" class="chip" data-share-content="times" aria-pressed="false"><?= e(t('share.content.times')) ?></button>
          <button type="button" class="chip" data-share-content="none" aria-pressed="false"><?= e(t('share.content.none')) ?></button>
        </div>
        <div class="share__show">
          <?php foreach (['author', 'date', 'title', 'rest', 'stats', 'footer'] as $k): ?>
          <label class="check"><input type="checkbox" data-share-show="<?= $k ?>" checked> <?= e(t('share.show.' . $k)) ?></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <fieldset>
        <legend><?= e(t('share.theme')) ?></legend>
        <div class="share__themes" role="group">
          <button type="button" class="swatch swatch--night is-on" data-share-theme="night" aria-pressed="true"><span><?= e(t('share.theme.night')) ?></span></button>
          <button type="button" class="swatch swatch--tartan" data-share-theme="tartan" aria-pressed="false"><span><?= e(t('share.theme.tartan')) ?></span></button>
          <button type="button" class="swatch swatch--light" data-share-theme="light" aria-pressed="false"><span><?= e(t('share.theme.light')) ?></span></button>
          <button type="button" class="swatch swatch--grass" data-share-theme="grass" aria-pressed="false"><span><?= e(t('share.theme.grass')) ?></span></button>
          <button type="button" class="swatch swatch--blue" data-share-theme="blue" aria-pressed="false"><span><?= e(t('share.theme.blue')) ?></span></button>
        </div>
        <label class="check share__clear"><input type="checkbox" data-share-clear> <?= e(t('share.clear')) ?></label>
      </fieldset>
      <p class="share__actions">
        <button type="button" class="btn btn--primary" data-share-send hidden><?= e(t('share.send')) ?></button>
        <button type="button" class="btn btn--ghost" data-share-copy hidden data-done="<?= e(t('share.copied')) ?>"><?= e(t('share.copy')) ?></button>
        <button type="button" class="btn btn--ghost" data-share-download><?= e(t('share.download')) ?></button>
      </p>
      <p class="muted share__hint"><?= e(t($isOwner ? 'share.hint' : 'share.hint_coach')) ?></p>
    </div>
  </div>
  <script type="application/json" data-share-data><?= json_encode(share_card_data($w, $owner, $pbs) + ['site' => config('site_host', 'www.series.corridas.eu')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</dialog>
<?php endif; ?>
