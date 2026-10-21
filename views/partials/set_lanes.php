<?php
/**
 * Uma série desenhada como pistas: cada repetição é uma linha com uma barra
 * que cresce para a esquerda (mais rápida que a média) ou para a direita (mais lenta).
 * @var array $set   linha de workout_sets com 'reps' e 'stats'
 * @var int   $index posição da série (1..n)
 * @var array $pbs   distance_m => melhor tempo de sempre
 */
$st = $set['stats'];
$dist = (int)$set['distance_m'];
$maxDev = 0.0;
foreach ($set['reps'] as $r) {
    $maxDev = max($maxDev, abs($r - $st['avg']));
}
// escala mínima de 1,5% do tempo médio: numa série muito regular as barras ficam pequenas
$maxDev = max($maxDev, $st['avg'] * 0.015);
$pb = $pbs[$dist] ?? null;
$rest = $set['rest_seconds'] !== null
    ? t('w.rest_desc', ['t' => fmt_time((float)$set['rest_seconds'], 0), 'type' => t('w.rest.' . $set['rest_type'])])
    : null;
?>
<section class="set" aria-labelledby="set-<?= $index ?>-h">
  <header class="set__head">
    <h3 id="set-<?= $index ?>-h" class="set__title">
      <span class="set__reps"><?= count($set['reps']) ?> × <?= e(fmt_distance($dist)) ?></span>
      <?php if ($rest): ?><span class="set__meta"><?= e($rest) ?></span><?php endif; ?>
      <?php if ($set['target_seconds'] !== null): ?><span class="set__meta"><?= e(t('w.target_desc', ['t' => fmt_time((float)$set['target_seconds'])])) ?></span><?php endif; ?>
    </h3>
    <dl class="set__stats">
      <div><dt><?= e(t('w.avg')) ?></dt><dd><?= fmt_time($st['avg'], 1) ?></dd></div>
      <div><dt><?= e(t('w.best')) ?></dt><dd><?= fmt_time($st['best'], 1) ?></dd></div>
      <div><dt><?= e(t('w.sd')) ?></dt><dd>±<?= number_format($st['sd'], 1) ?>s</dd></div>
      <div><dt><?= e(t('w.pace')) ?></dt><dd><?= fmt_pace($dist, $st['avg']) ?><small><?= e(t('w.per_km')) ?></small></dd></div>
    </dl>
  </header>
  <div class="lane lane--head" aria-hidden="true">
    <span></span><span></span><span></span>
    <span class="lane__dev"><?= e(t('w.vs_avg')) ?></span>
    <?php if ($set['target_seconds'] !== null): ?><span class="lane__target"><?= e(t('w.vs_target')) ?></span><?php endif; ?>
  </div>
  <ol class="lanes" aria-label="<?= e(t('w.split_from_avg')) ?>">
    <?php foreach ($set['reps'] as $i => $r):
        $dev = round($r - $st['avg'], 1);
        $pct = min(50, abs($r - $st['avg']) / $maxDev * 50);
        $isPb = $pb !== null && abs($r - $pb) < 0.001;
        $target = $set['target_seconds'] !== null ? (float)$set['target_seconds'] : null;
    ?>
    <li class="lane<?= $isPb ? ' lane--pb' : '' ?>">
      <span class="lane__n"><?= $i + 1 ?></span>
      <span class="lane__time"><?= fmt_time($r, 1) ?><?php if ($isPb): ?> <abbr class="pb" title="<?= e(t('w.pb')) ?>">PB</abbr><?php endif; ?></span>
      <span class="lane__track" aria-hidden="true">
        <span class="lane__bar <?= $dev < 0 ? 'lane__bar--fast' : 'lane__bar--slow' ?>" style="--w: <?= number_format($pct, 2, '.', '') ?>%"></span>
      </span>
      <span class="lane__dev <?= $dev < 0 ? 'is-fast' : ($dev > 0 ? 'is-slow' : '') ?>">
        <?= $dev == 0 ? '0.0' : ($dev < 0 ? '−' : '+') . number_format(abs($dev), 1) ?>
        <?php if ($dev != 0): ?><span class="sr-only"><?= e($dev < 0 ? t('w.faster') : t('w.slower')) ?></span><?php endif; ?>
      </span>
      <?php if ($target !== null): ?>
        <?php $dt = round($r - $target, 1); ?>
        <span class="lane__target <?= $dt < 0 ? 'is-fast' : ($dt > 0 ? 'is-slow' : '') ?>"><?= $dt == 0 ? '0.0' : ($dt < 0 ? '−' : '+') . number_format(abs($dt), 1) ?></span>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>
</section>
