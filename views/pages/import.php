<?php
/** @var array|null $items resultado de import_prepare() guardado na sessão */
$summary = fn(array $sets) => implode(' + ', array_map(
    fn($s) => count($s['reps']) . ' × ' . fmt_distance((int)$s['distance_m']) . ($s['rest'] !== '' ? ' (' . fmt_time((float)$s['rest'], 0) . ')' : ''),
    $sets
));
?>
<header class="page-head">
  <div>
    <h1><?= e(t('imp.title')) ?></h1>
    <p class="muted"><?= e($items ? t('imp.found', ['n' => count($items), 'new' => count(array_filter($items, fn($i) => !$i['exists'] && !$i['errors']))]) : t('imp.lead')) ?></p>
  </div>
  <a class="btn btn--ghost btn--sm" href="<?= url('workouts.php') ?>"><?= e(t('w.list.title')) ?></a>
</header>

<?php if ($error): ?>
  <div class="flash flash--error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!$items): ?>
<form method="post" enctype="multipart/form-data" class="form import">
  <?= csrf_field() ?>
  <div class="field">
    <label for="file"><?= e(t('imp.file')) ?></label>
    <input id="file" name="file" type="file" required accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
    <p class="field__hint"><?= e(t('imp.file_hint')) ?></p>
  </div>
  <div><button class="btn btn--primary" type="submit"><?= e(t('imp.read')) ?></button></div>

  <details class="import__help">
    <summary><?= e(t('imp.help.title')) ?></summary>
    <ul>
      <li><?= e(t('imp.help.sheets')) ?></li>
      <li><?= e(t('imp.help.header')) ?></li>
      <li><?= e(t('imp.help.times')) ?></li>
      <li><?= e(t('imp.help.rest')) ?></li>
      <li><?= e(t('imp.help.extra')) ?></li>
    </ul>
<pre class="import__sample">200m
Data       | 1    | 2    | 3    | Média | Intervalo
14/10/2025 | 39   | 38   | 38   |       | 60"       | *antes | 300m | 64 | 63
21/10/2025 | 1:32 | 1:31 | 1:30 |       | 1:30'     | *pista cimpor</pre>
  </details>
</form>

<?php else: ?>
<form method="post" class="import">
  <?= csrf_field() ?>
  <ul class="wlist ilist">
    <?php $month = null; foreach ($items as $i => $it):
        $w = $it['input'];
        $ts = strtotime($w['workout_date']);
        $ok = !$it['errors'];
        if (date('Y-m', $ts) !== $month):
            $month = date('Y-m', $ts);
    ?>
    <li class="wlist__month"><span class="wlist__mname"><?= e(month_name((int)date('n', $ts))) ?></span> <span class="wlist__year"><?= date('Y', $ts) ?></span></li>
    <?php endif; ?>
    <li class="wlist__item">
      <label class="ilist__row<?= $ok ? '' : ' ilist__row--off' ?>">
        <input type="checkbox" name="pick[]" value="<?= $i ?>" <?= $ok && !$it['exists'] ? 'checked' : '' ?> <?= $ok ? '' : 'disabled' ?>>
        <time class="cal" datetime="<?= e($w['workout_date']) ?>">
          <span class="cal__wd"><?= e(mb_strtolower(mb_substr(t('weekday.' . date('N', $ts)), 0, 3))) ?></span>
          <span class="cal__day"><?= date('j', $ts) ?></span>
        </time>
        <span class="wlist__body">
          <span class="wlist__sets"><?= e($summary($w['sets'])) ?></span>
          <span class="wlist__meta">
            <?php if ($it['exists']): ?><span class="badge"><?= e(t('imp.exists')) ?></span><?php endif; ?>
            <?php if ($w['title']): ?><span><?= e($w['title']) ?></span><?php endif; ?>
            <?php if ($w['notes']): ?><span><?= e(str_replace("\n", ' · ', $w['notes'])) ?></span><?php endif; ?>
          </span>
          <?php foreach ([...array_values($it['errors']), ...$it['warnings']] as $msg): ?>
            <span class="ilist__warn"><?= e($msg) ?></span>
          <?php endforeach; ?>
        </span>
      </label>
    </li>
    <?php endforeach; ?>
  </ul>
  <div class="import__actions">
    <button class="btn btn--primary btn--lg" type="submit" name="confirm" value="1"><?= e(t('imp.confirm')) ?></button>
    <a class="link" href="<?= url('import.php?reset=1') ?>"><?= e(t('imp.cancel')) ?></a>
  </div>
</form>
<?php endif; ?>
