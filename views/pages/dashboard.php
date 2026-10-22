<?php
$hasData = $totals['workouts'] > 0;
$uq = $isOwner ? '' : 'u=' . (int)$athlete['id'];   // manter o atleta nos links (treinador)
$link = function (string $page, string $qs = '') use ($uq): string {
    $all = implode('&', array_filter([$qs, $uq]));
    return url($page . ($all !== '' ? '?' . $all : ''));
};
?>
<?php if (!$isOwner): ?>
  <?php view('partials/athlete_bar', ['athlete' => $athlete, 'current' => 'dashboard']); ?>
<?php endif; ?>
<header class="page-head">
  <div>
    <h1><?= e($isOwner ? t('dash.hello', ['name' => $athlete['display_name']]) : $title) ?></h1>
    <?php if ($hasData): ?>
      <p class="muted">
        <?= e(t('dash.totals.30d', ['n' => (int)$totals['workouts_30d'], 'km' => fmt_km($totals['meters_30d'])])) ?>
        <?php if ($streak > 1): ?><br><?= e(t('dash.streak', ['n' => $streak])) ?><?php endif; ?>
      </p>
    <?php endif; ?>
  </div>
</header>

<?php if ($athletes): ?>
  <?php view('partials/athletes_table', ['athletes' => $athletes, 'compact' => true]); ?>
<?php endif; ?>

<?php if (!$hasData && !$isOwner): ?>
  <section class="empty empty--sm"><p><?= e(t('athletes.no_workouts')) ?></p></section>
<?php elseif (!$hasData): ?>
  <section class="empty">
    <span class="brand__lanes brand__lanes--lg" aria-hidden="true"><i></i><i></i><i></i></span>
    <h2><?= e(t('dash.empty.title')) ?></h2>
    <p><?= e(t('dash.empty.lead')) ?></p>
    <a class="btn btn--primary btn--lg" href="<?= url('workout_edit.php') ?>"><?= e(t('nav.new')) ?></a>
  </section>
<?php else: ?>

<div class="dash">
<div class="dash__side">
  <section class="board" aria-labelledby="pbs-h">
    <h2 id="pbs-h" class="board__title"><?= e(t('dash.pbs')) ?></h2>
    <ol class="board__rows">
      <?php foreach ($pbsByDistance as $pb): ?>
      <li>
        <a href="<?= $link('workout.php', 'id=' . (int)$pb['workout_id']) ?>">
          <span class="board__dist"><?= e(fmt_distance((int)$pb['distance_m'])) ?></span>
          <span class="board__time"><?= fmt_time((float)$pb['best'], 1) ?></span>
          <span class="board__date"><?= e(fmt_date($pb['workout_date'])) ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ol>
  </section>

  <section class="totals" aria-label="Totais">
    <div><strong><?= (int)$totals['workouts'] ?></strong><span><?= e(t('dash.totals.workouts')) ?></span></div>
    <div><strong><?= fmt_km($totals['meters']) ?></strong><span><?= e(t('dash.totals.km')) ?></span></div>
    <div><strong><?= (int)$totals['reps'] ?></strong><span><?= e(t('dash.totals.reps')) ?></span></div>
  </section>

  <section class="panel panel--dist" aria-labelledby="ds-h">
    <h2 id="ds-h"><?= e(t('dash.distances')) ?></h2>
    <?php $maxTimes = max(array_map(fn($p) => (int)$p['times'], $pbs)); ?>
    <ul class="hbars">
      <?php foreach ($pbs as $pb): ?>
      <li>
        <a href="<?= $link('workouts.php', 'distance=' . (int)$pb['distance_m']) ?>"><?= e(fmt_distance((int)$pb['distance_m'])) ?></a>
        <span class="hbars__bar" style="--w: <?= round((int)$pb['times'] / $maxTimes * 100, 1) ?>%"></span>
        <span class="hbars__n"><?= e(t('dash.pbs.times', ['n' => (int)$pb['times']])) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php if ($trained): ?>
  <section class="panel panel--trained" aria-labelledby="tr-h">
    <h2 id="tr-h"><?= e(t('dash.trained')) ?></h2>
    <?php $maxW = max(array_map(fn($d) => (int)$d['workouts'], $trained)); ?>
    <ul class="hbars">
      <?php foreach ($trained as $d): ?>
      <li>
        <a href="<?= $link('workouts.php', 'distance=' . (int)$d['distance_m']) ?>"><?= e(fmt_distance((int)$d['distance_m'])) ?></a>
        <span class="hbars__bar" style="--w: <?= round((int)$d['workouts'] / $maxW * 100, 1) ?>%"></span>
        <span class="hbars__n"><?= e(t((int)$d['workouts'] === 1 ? 'dash.trained.one' : 'dash.trained.n', ['n' => (int)$d['workouts']])) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
</div>
<div class="dash__main">
  <section class="panel panel--weekly" aria-labelledby="wk-h">
    <h2 id="wk-h"><?= e(t('dash.weekly')) ?></h2>
    <p class="muted small"><?= e(t('dash.weekly.unit')) ?></p>
    <div class="chart" data-chart="bars" data-points='<?= e(json_encode(array_map(fn($w) => [
        'label' => (int)date('j', strtotime($w['week'])) . '/' . (int)date('n', strtotime($w['week'])),
        'title' => t('dash.week_of', ['d' => fmt_date($w['week'])]),
        'value' => round($w['meters'] / 1000, 1),
        'extra' => $w['workouts'] . ' ' . t('dash.totals.workouts'),
    ], $weekly))) ?>'></div>
    <table class="sr-only">
      <caption><?= e(t('dash.weekly')) ?></caption>
      <?php foreach ($weekly as $w): ?><tr><th><?= e(fmt_date($w['week'])) ?></th><td><?= fmt_km($w['meters']) ?> km</td></tr><?php endforeach; ?>
    </table>
  </section>

  <section class="panel panel--progress" id="evolucao" aria-labelledby="pr-h">
    <div class="panel__head">
      <div>
        <h2 id="pr-h"><?= e(t('dash.progress')) ?></h2>
        <p class="muted small"><?= e(t('dash.progress.lead')) ?></p>
      </div>
      <form method="get" action="#evolucao" class="inline-select">
        <?php if (!$isOwner): ?><input type="hidden" name="u" value="<?= (int)$athlete['id'] ?>"><?php endif; ?>
        <label class="sr-only" for="d"><?= e(t('w.filter.distance')) ?></label>
        <select id="d" name="d" data-autosubmit>
          <?php foreach ($pbsByDistance as $pb): ?>
            <option value="<?= (int)$pb['distance_m'] ?>" <?= (int)$pb['distance_m'] === $progDist ? 'selected' : '' ?>><?= e(fmt_distance((int)$pb['distance_m'])) ?></option>
          <?php endforeach; ?>
        </select>
        <noscript><button class="btn btn--sm" type="submit"><?= e(t('w.filter.apply')) ?></button></noscript>
      </form>
    </div>
    <?php if (count($progression) < 2): ?>
      <p class="muted"><?= e(t('dash.progress.few')) ?></p>
    <?php else: ?>
      <div class="chart" data-chart="lines" data-series='<?= e(json_encode([
          ['name' => t('dash.progress.avg'), 'cls' => 'avg', 'values' => array_map(fn($p) => round((float)$p['avg_t'], 2), $progression)],
          ['name' => t('dash.progress.best'), 'cls' => 'best', 'values' => array_map(fn($p) => round((float)$p['best_t'], 2), $progression)],
      ])) ?>' data-labels='<?= e(json_encode(array_map(fn($p) => fmt_date($p['workout_date']), $progression))) ?>'></div>
      <ul class="legend">
        <li><i class="legend__key legend__key--avg"></i><?= e(t('dash.progress.avg')) ?></li>
        <li><i class="legend__key legend__key--best"></i><?= e(t('dash.progress.best')) ?></li>
      </ul>
      <table class="sr-only">
        <caption><?= e(t('dash.progress')) ?> <?= e(fmt_distance($progDist)) ?></caption>
        <?php foreach ($progression as $p): ?><tr><th><?= e(fmt_date($p['workout_date'])) ?></th><td><?= fmt_time((float)$p['avg_t']) ?></td><td><?= fmt_time((float)$p['best_t']) ?></td></tr><?php endforeach; ?>
      </table>
    <?php endif; ?>
  </section>

  <section class="panel panel--recent" aria-labelledby="rc-h">
    <h2 id="rc-h"><?= e(t('dash.recent')) ?></h2>
    <?php view('partials/workout_rows', ['rows' => $recent]); ?>
    <p><a class="link" href="<?= $link('workouts.php') ?>"><?= e(t('dash.see_all')) ?></a></p>
  </section>
</div>
</div>
<?php endif; ?>
