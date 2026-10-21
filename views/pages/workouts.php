<?php
$hasFilters = array_filter($filters) !== [];
$qs = fn(array $extra) => http_build_query(array_filter(array_merge($filters, $extra)));
?>
<header class="page-head">
  <div>
    <h1><?= e(t('w.list.title')) ?></h1>
    <p class="muted"><?= e(t('w.list.count', ['n' => $result['total']])) ?></p>
  </div>
  <div class="actions">
    <a class="btn btn--ghost btn--sm" href="<?= url('import.php') ?>"><?= e(t('imp.title')) ?></a>
    <a class="btn btn--ghost btn--sm" href="<?= url('export.php') ?>"><?= e(t('p.export')) ?></a>
  </div>
</header>

<form method="get" class="filters" role="search">
  <div class="field field--sm">
    <label for="f-distance"><?= e(t('w.filter.distance')) ?></label>
    <select id="f-distance" name="distance">
      <option value=""><?= e(t('w.filter.any')) ?></option>
      <?php foreach ($distances as $d): ?>
        <option value="<?= (int)$d ?>" <?= (int)$d === $filters['distance'] ? 'selected' : '' ?>><?= e(fmt_distance((int)$d)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field field--sm">
    <label for="f-year"><?= e(t('w.filter.year')) ?></label>
    <select id="f-year" name="year">
      <option value=""><?= e(t('w.filter.any')) ?></option>
      <?php foreach ($years as $y): ?>
        <option value="<?= (int)$y ?>" <?= (int)$y === $filters['year'] ? 'selected' : '' ?>><?= (int)$y ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field field--sm">
    <label for="f-month"><?= e(t('w.filter.month')) ?></label>
    <select id="f-month" name="month">
      <option value=""><?= e(t('w.filter.any')) ?></option>
      <?php for ($m = 1; $m <= 12; $m++): ?>
        <option value="<?= $m ?>" <?= $m === $filters['month'] ? 'selected' : '' ?>><?= e(month_name($m)) ?></option>
      <?php endfor; ?>
    </select>
  </div>
  <div class="field field--sm">
    <label for="f-location"><?= e(t('w.filter.location')) ?></label>
    <select id="f-location" name="location">
      <option value=""><?= e(t('w.filter.any')) ?></option>
      <?php foreach ($locations as $l): ?>
        <option value="<?= e($l) ?>" <?= $l === $filters['location'] ? 'selected' : '' ?>><?= e($l) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field field--sm field--grow">
    <label for="f-q"><?= e(t('w.filter.q')) ?></label>
    <input id="f-q" name="q" type="search" value="<?= e($filters['q']) ?>">
  </div>
  <div class="filters__actions">
    <button class="btn btn--primary btn--sm" type="submit"><?= e(t('w.filter.apply')) ?></button>
    <?php if ($hasFilters): ?><a class="link small" href="<?= url('workouts.php') ?>"><?= e(t('w.list.clear')) ?></a><?php endif; ?>
  </div>
</form>

<?php if (!$result['rows']): ?>
  <section class="empty empty--sm">
    <?php if ($hasFilters): ?>
      <p><?= e(t('w.list.empty')) ?></p>
      <a class="link" href="<?= url('workouts.php') ?>"><?= e(t('w.list.clear')) ?></a>
    <?php else: ?>
      <h2><?= e(t('dash.empty.title')) ?></h2>
      <p><?= e(t('dash.empty.lead')) ?></p>
      <a class="btn btn--primary" href="<?= url('workout_edit.php') ?>"><?= e(t('nav.new')) ?></a>
    <?php endif; ?>
  </section>
<?php else: ?>
  <?php view('partials/workout_rows', ['rows' => $result['rows']]); ?>

  <?php if ($result['pages'] > 1): ?>
  <nav class="pager" aria-label="<?= e(t('pager.page', ['p' => $page, 'n' => $result['pages']])) ?>">
    <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="?<?= e($qs(['page' => $page - 1])) ?>"><?= e(t('pager.prev')) ?></a><?php endif; ?>
    <span class="muted small"><?= e(t('pager.page', ['p' => $page, 'n' => $result['pages']])) ?></span>
    <?php if ($page < $result['pages']): ?><a class="btn btn--ghost btn--sm" href="?<?= e($qs(['page' => $page + 1])) ?>"><?= e(t('pager.next')) ?></a><?php endif; ?>
  </nav>
  <?php endif; ?>
<?php endif; ?>
