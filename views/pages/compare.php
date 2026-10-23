<?php
/** @var array $a treino A (com 'owner') */
/** @var ?array $b treino B, ou null enquanto se escolhe */
$sameAthlete = $b && (int)$a['user_id'] === (int)$b['user_id'];
// nome curto de cada lado: o atleta, ou a data quando é o mesmo atleta
$label = fn(array $w) => $sameAthlete ? fmt_date($w['workout_date']) : $w['owner']['display_name'];

/** Diferença B − A já formatada; $lowerBetter decide a cor (tempo: menos é melhor). */
$delta = function (?float $va, ?float $vb, string $unit, int $dec = 1, bool $lowerBetter = true): string {
    if ($va === null || $vb === null) {
        return '<span class="muted">–</span>';
    }
    $d = round($vb - $va, $dec);
    if ($d == 0) {
        return '<span>0' . ($dec ? '.' . str_repeat('0', $dec) : '') . $unit . '</span>';
    }
    $better = $lowerBetter ? $d < 0 : $d > 0;
    return '<span class="' . ($better ? 'is-fast' : 'is-slow') . '">' . ($d < 0 ? '−' : '+') . number_format(abs($d), $dec) . $unit . '</span>';
};
?>
<p class="back"><a class="link" href="<?= url('workout.php?id=' . (int)$a['id']) ?>"><?= e(t('cmp.back')) ?></a></p>

<header class="page-head">
  <div>
    <h1><?= e(t('cmp.title')) ?></h1>
    <p class="muted"><?= e(t($b ? 'cmp.lead' : 'cmp.pick_lead')) ?></p>
  </div>
  <?php if ($b): ?>
  <div class="actions">
    <a class="btn btn--ghost btn--sm" href="<?= url('compare.php?a=' . (int)$b['id'] . '&b=' . (int)$a['id']) ?>"><?= e(t('cmp.swap')) ?></a>
    <a class="btn btn--ghost btn--sm" href="<?= url('compare.php?a=' . (int)$a['id']) ?>"><?= e(t('cmp.change')) ?></a>
  </div>
  <?php endif; ?>
</header>

<div class="cmp-cards">
  <?php foreach (array_filter(['a' => $a, 'b' => $b]) as $side => $w):
      $tot = compare_totals($w); ?>
  <article class="cmp-card cmp-card--<?= $side ?>">
    <p class="cmp-card__side"><span class="cmp-key cmp-key--<?= $side ?>"><?= strtoupper($side) ?></span>
      <a class="author" href="<?= url('user.php?id=' . (int)$w['owner']['id']) ?>"><?php view('partials/avatar', ['u' => $w['owner'], 'size' => 'sm']); ?><span><?= e($w['owner']['display_name']) ?></span></a></p>
    <p class="muted"><time datetime="<?= e($w['workout_date']) ?>"><?= e(fmt_date($w['workout_date'], true)) ?></time><?= $w['start_time'] ? ' · ' . e(substr($w['start_time'], 0, 5)) : '' ?></p>
    <h2><a href="<?= url('workout.php?id=' . (int)$w['id']) ?>"><?= e($w['title'] ?: sets_summary($w['sets'])) ?></a></h2>
    <?php if ($w['title']): ?><p class="lead-sets"><?= e(sets_summary($w['sets'])) ?></p><?php endif; ?>
    <p class="muted small"><?= e(implode(' · ', array_filter([
        $w['location'],
        t('w.venue.' . $w['venue_type']),
        trim(($w['weather'] ?? '') . ($w['temperature_c'] !== null ? ' ' . $w['temperature_c'] . ' °C' : '')),
        t('w.summary_volume', ['km' => fmt_km($tot['m'])]),
    ]))) ?></p>
  </article>
  <?php endforeach; ?>
</div>

<?php if (!$b): ?>
<section class="panel cmp-pick" aria-labelledby="cmp-pick-h">
  <h2 id="cmp-pick-h"><?= e(t('cmp.pick')) ?></h2>
  <form method="get" class="filters">
    <input type="hidden" name="a" value="<?= (int)$a['id'] ?>">
    <?php if (count($people) > 1): ?>
    <div class="field field--sm">
      <label for="c-who"><?= e(t('w.filter.athlete')) ?></label>
      <select id="c-who" name="who" data-autosubmit>
        <?php foreach ($people as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $who ? 'selected' : '' ?>><?= e($p['display_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="field field--sm">
      <label for="c-dist"><?= e(t('w.filter.distance')) ?></label>
      <select id="c-dist" name="distance" data-autosubmit>
        <option value="0"><?= e(t('w.filter.any')) ?></option>
        <?php foreach ($distances as $d): ?>
          <option value="<?= (int)$d ?>" <?= (int)$d === $dist ? 'selected' : '' ?>><?= e(fmt_distance((int)$d)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <noscript><button class="btn btn--primary btn--sm" type="submit"><?= e(t('w.filter.apply')) ?></button></noscript>
  </form>
  <?php if ($result['rows']): ?>
    <ul class="wlist cmp-pick__list">
      <?php foreach ($result['rows'] as $r): $ts = strtotime($r['workout_date']); ?>
      <li class="wlist__item">
        <a href="<?= url('compare.php?a=' . (int)$a['id'] . '&b=' . (int)$r['id']) ?>" class="wlist__link">
          <time class="cal" datetime="<?= e($r['workout_date']) ?>">
            <span class="cal__wd"><?= e(mb_strtolower(mb_substr(t('weekday.' . date('N', $ts)), 0, 3))) ?></span>
            <span class="cal__day"><?= date('j', $ts) ?></span>
          </time>
          <span class="wlist__body">
            <span class="wlist__sets"><?= e(sets_summary($r['sets'])) ?></span>
            <span class="wlist__meta">
              <span><?= e(month_name((int)date('n', $ts)) . ' ' . date('Y', $ts)) ?></span>
              <?php if ($r['title']): ?><span><?= e($r['title']) ?></span><?php endif; ?>
              <?php if ($r['location']): ?><span><?= e($r['location']) ?></span><?php endif; ?>
            </span>
          </span>
          <span class="wlist__vol"><?= fmt_km((float)$r['volume_m']) ?><small>km</small></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php if ($result['pages'] > 1): ?>
    <nav class="pager" aria-label="<?= e(t('pager.page', ['p' => $page, 'n' => $result['pages']])) ?>">
      <?php $qs = fn($p) => http_build_query(['a' => (int)$a['id'], 'who' => $who, 'distance' => (int)$dist, 'page' => $p]); ?>
      <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="?<?= e($qs($page - 1)) ?>"><?= e(t('pager.prev')) ?></a><?php endif; ?>
      <span class="muted small"><?= e(t('pager.page', ['p' => $page, 'n' => $result['pages']])) ?></span>
      <?php if ($page < $result['pages']): ?><a class="btn btn--ghost btn--sm" href="?<?= e($qs($page + 1)) ?>"><?= e(t('pager.next')) ?></a><?php endif; ?>
    </nav>
    <?php endif; ?>
  <?php else: ?>
    <p class="muted"><?= e(t('w.list.empty')) ?></p>
  <?php endif; ?>
</section>

<?php else:
    $ta = compare_totals($a);
    $tb = compare_totals($b);
    // ritmo médio em segundos por km (velocidade média de todas as repetições)
    $pace = fn(array $t) => $t['m'] > 0 ? $t['t'] / $t['m'] * 1000 : null;
?>
<section class="panel cmp-sec" aria-labelledby="cmp-tot-h">
  <h2 id="cmp-tot-h"><?= e(t('cmp.totals')) ?></h2>
  <div class="table-scroll">
  <table class="cmp-table">
    <thead><tr><th></th><th class="num"><span class="cmp-key cmp-key--a">A</span> <?= e($label($a)) ?></th><th class="num"><span class="cmp-key cmp-key--b">B</span> <?= e($label($b)) ?></th><th class="num"><?= e(t('cmp.diff')) ?></th></tr></thead>
    <tbody>
      <tr><th scope="row"><?= e(t('share.volume')) ?></th><td class="num"><?= fmt_km($ta['m']) ?> km</td><td class="num"><?= fmt_km($tb['m']) ?> km</td><td class="num"><?= $delta($ta['m'] / 1000, $tb['m'] / 1000, ' km', 2, false) ?></td></tr>
      <tr><th scope="row"><?= e(t('share.reps')) ?></th><td class="num"><?= $ta['n'] ?></td><td class="num"><?= $tb['n'] ?></td><td class="num"><?= $delta($ta['n'], $tb['n'], '', 0, false) ?></td></tr>
      <tr><th scope="row"><?= e(t('cmp.avg_pace')) ?></th><td class="num"><?= fmt_pace($ta['m'], $ta['t']) ?><small><?= e(t('w.per_km')) ?></small></td><td class="num"><?= fmt_pace($tb['m'], $tb['t']) ?><small><?= e(t('w.per_km')) ?></small></td><td class="num"><?= $delta($pace($ta), $pace($tb), ' s/km') ?></td></tr>
      <tr><th scope="row"><?= e(t('cmp.avg_rest')) ?></th><td class="num"><?= $ta['rest'] !== null ? fmt_time($ta['rest'], 0) : '–' ?></td><td class="num"><?= $tb['rest'] !== null ? fmt_time($tb['rest'], 0) : '–' ?></td><td class="num"><?= $delta($ta['rest'], $tb['rest'], ' s', 0, false) ?></td></tr>
      <tr><th scope="row"><?= e(t('w.rpe')) ?></th><td class="num"><?= $a['rpe'] ? (int)$a['rpe'] . '/10' : '–' ?></td><td class="num"><?= $b['rpe'] ? (int)$b['rpe'] . '/10' : '–' ?></td><td class="num"><?= $delta($a['rpe'] ? (float)$a['rpe'] : null, $b['rpe'] ? (float)$b['rpe'] : null, '', 0) ?></td></tr>
    </tbody>
  </table>
  </div>
</section>

<?php foreach ($groups as $gi => $g):
    $both = $g['a'] && $g['b'];
    $n = max(count($g['a']), count($g['b']));
    $paceOf = fn(?array $s) => $s ? $s['avg'] / $g['d'] * 1000 : null;
?>
<section class="panel cmp-sec" aria-labelledby="cmp-d<?= $gi ?>-h">
  <h2 id="cmp-d<?= $gi ?>-h"><?= e(fmt_distance($g['d'])) ?>
    <span class="set__meta"><?= e($both ? t('cmp.reps_vs', ['a' => count($g['a']), 'b' => count($g['b'])]) : t($g['a'] ? 'cmp.only_a' : 'cmp.only_b')) ?></span></h2>

  <div class="table-scroll">
  <table class="cmp-table">
    <thead><tr><th></th><th class="num"><span class="cmp-key cmp-key--a">A</span></th><th class="num"><span class="cmp-key cmp-key--b">B</span></th><th class="num"><?= e(t('cmp.diff')) ?></th></tr></thead>
    <tbody>
      <?php foreach (['avg' => 'w.avg', 'best' => 'w.best', 'worst' => 'cmp.worst'] as $k => $lbl): ?>
      <tr><th scope="row"><?= e(t($lbl)) ?></th>
        <td class="num"><?= $g['sa'] ? fmt_time($g['sa'][$k], 1) : '–' ?></td>
        <td class="num"><?= $g['sb'] ? fmt_time($g['sb'][$k], 1) : '–' ?></td>
        <td class="num"><?= $delta($g['sa'][$k] ?? null, $g['sb'][$k] ?? null, ' s') ?></td></tr>
      <?php endforeach; ?>
      <tr><th scope="row"><?= e(t('w.sd')) ?></th>
        <td class="num"><?= $g['sa'] ? '±' . number_format($g['sa']['sd'], 1) . 's' : '–' ?></td>
        <td class="num"><?= $g['sb'] ? '±' . number_format($g['sb']['sd'], 1) . 's' : '–' ?></td>
        <td class="num"><?= $delta($g['sa']['sd'] ?? null, $g['sb']['sd'] ?? null, ' s') ?></td></tr>
      <tr><th scope="row"><?= e(t('w.pace')) ?></th>
        <td class="num"><?= $g['sa'] ? fmt_pace($g['d'], $g['sa']['avg']) : '–' ?></td>
        <td class="num"><?= $g['sb'] ? fmt_pace($g['d'], $g['sb']['avg']) : '–' ?></td>
        <td class="num"><?= $delta($paceOf($g['sa']), $paceOf($g['sb']), ' s/km') ?></td></tr>
    </tbody>
  </table>
  </div>

  <?php if ($both):
      // gráfico: tempo de cada repetição, mais rápido em cima
      $all = array_merge($g['a'], $g['b']);
      $lo = min($all);
      $hi = max($all);
      $pad = max(($hi - $lo) * 0.12, $lo * 0.01, 0.5);
      $lo -= $pad;
      $hi += $pad;
      $W = 640; $H = 200; $L = 52; $R = 12; $T = 12; $B = 28;
      $x = fn(int $i) => $n > 1 ? $L + $i * ($W - $L - $R) / ($n - 1) : ($L + $W - $R) / 2;
      $y = fn(float $v) => $T + ($v - $lo) / ($hi - $lo) * ($H - $T - $B);
      $line = fn(array $reps) => implode(' ', array_map(fn($i, $v) => number_format($x($i), 1, '.', '') . ',' . number_format($y($v), 1, '.', ''), array_keys($reps), $reps));
  ?>
  <figure class="cmp-chart">
    <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="<?= e(t('cmp.chart', ['d' => fmt_distance($g['d'])])) ?>">
      <?php foreach ([$lo + $pad, ($lo + $hi) / 2, $hi - $pad] as $tick): ?>
        <line class="cmp-chart__grid" x1="<?= $L ?>" x2="<?= $W - $R ?>" y1="<?= number_format($y($tick), 1, '.', '') ?>" y2="<?= number_format($y($tick), 1, '.', '') ?>"/>
        <text class="cmp-chart__tick" x="<?= $L - 8 ?>" y="<?= number_format($y($tick) + 4, 1, '.', '') ?>" text-anchor="end"><?= e(fmt_time($tick, 1)) ?></text>
      <?php endforeach; ?>
      <?php for ($i = 0; $i < $n; $i++): ?>
        <text class="cmp-chart__tick" x="<?= number_format($x($i), 1, '.', '') ?>" y="<?= $H - 8 ?>" text-anchor="middle"><?= $i + 1 ?></text>
      <?php endfor; ?>
      <?php foreach (['a', 'b'] as $side): ?>
        <polyline class="cmp-chart__line cmp-chart__line--<?= $side ?>" points="<?= $line($g[$side]) ?>"/>
        <?php foreach ($g[$side] as $i => $v): ?>
          <circle class="cmp-chart__dot cmp-chart__dot--<?= $side ?>" cx="<?= number_format($x($i), 1, '.', '') ?>" cy="<?= number_format($y($v), 1, '.', '') ?>" r="4"><title><?= strtoupper($side) ?> · <?= e(t('w.rep', ['n' => $i + 1])) ?> · <?= e(fmt_time($v, 1)) ?></title></circle>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </svg>
    <figcaption class="muted small"><span class="cmp-key cmp-key--a">A</span> <?= e($label($a)) ?> &nbsp; <span class="cmp-key cmp-key--b">B</span> <?= e($label($b)) ?> · <?= e(t('cmp.chart_hint')) ?></figcaption>
  </figure>
  <?php endif; ?>

  <details class="cmp-reps"<?= $both ? ' open' : '' ?>>
    <summary><?= e(t('cmp.by_rep')) ?></summary>
    <div class="table-scroll">
    <table class="cmp-table">
      <thead><tr><th>#</th><th class="num"><span class="cmp-key cmp-key--a">A</span></th><th class="num"><span class="cmp-key cmp-key--b">B</span></th><th class="num"><?= e(t('cmp.diff')) ?></th></tr></thead>
      <tbody>
        <?php for ($i = 0; $i < $n; $i++): $ra = $g['a'][$i] ?? null; $rb = $g['b'][$i] ?? null; ?>
        <tr><th scope="row"><?= $i + 1 ?></th>
          <td class="num"><?= fmt_time($ra, 1) ?></td>
          <td class="num"><?= fmt_time($rb, 1) ?></td>
          <td class="num"><?= $delta($ra, $rb, ' s') ?></td></tr>
        <?php endfor; ?>
      </tbody>
    </table>
    </div>
  </details>
</section>
<?php endforeach; ?>

<?php if ($a['notes'] || $b['notes']): ?>
<section class="cmp-cards">
  <?php foreach (['a' => $a, 'b' => $b] as $side => $w): ?>
  <div class="notes cmp-card cmp-card--<?= $side ?>">
    <h2><span class="cmp-key cmp-key--<?= $side ?>"><?= strtoupper($side) ?></span> <?= e(t('w.notes')) ?></h2>
    <p><?= $w['notes'] ? nl2br(e($w['notes'])) : '<span class="muted">–</span>' ?></p>
  </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<?php endif; ?>
