<?php
$coachAll = $coachAll ?? false;   // treinador: os treinos de todos os atletas
$pick = $pick ?? 0;
$hasFilters = array_filter($filters) !== [] || $pick;
$u = $coachAll ? ($pick ? ['a' => $pick] : []) : ($isOwner ? [] : ['u' => (int)$athlete['id']]);
$qs = fn(array $extra) => http_build_query(array_filter(array_merge($filters, $u, $extra)));
$listUrl = url('workouts.php' . ($u && !$coachAll ? '?u=' . $u['u'] : ''));
?>
<?php if (!$isOwner && !$coachAll): ?>
  <?php view('partials/athlete_bar', ['athlete' => $athlete, 'current' => 'workouts']); ?>
<?php endif; ?>
<header class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= e(t('w.list.count', ['n' => $result['total']])) ?></p>
  </div>
  <?php if ($isOwner): ?>
  <div class="actions">
    <a class="btn btn--ghost btn--sm" href="<?= url('import.php') ?>"><?= e(t('imp.title')) ?></a>
    <a class="btn btn--ghost btn--sm" href="<?= url('export.php') ?>"><?= e(t('p.export')) ?></a>
  </div>
  <?php endif; ?>
</header>

<form method="get" class="filters" role="search">
  <?php if ($u && !$coachAll): ?><input type="hidden" name="u" value="<?= $u['u'] ?>"><?php endif; ?>
  <?php if ($coachAll): ?>
  <div class="field field--sm">
    <label for="f-athlete"><?= e(t('w.filter.athlete')) ?></label>
    <select id="f-athlete" name="a">
      <option value=""><?= e(t('w.filter.all_athletes')) ?></option>
      <?php foreach ($athletes as $a): ?>
        <option value="<?= (int)$a['id'] ?>" <?= (int)$a['id'] === $pick ? 'selected' : '' ?>><?= e($a['display_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
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
  <div class="field field--sm">
    <label for="f-status"><?= e(t('w.filter.status')) ?></label>
    <select id="f-status" name="status">
      <option value=""><?= e(t('w.filter.any')) ?></option>
      <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>><?= e(t('w.filter.active')) ?></option>
      <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>><?= e(t('w.filter.inactive')) ?></option>
    </select>
  </div>
  <div class="field field--sm field--grow">
    <label for="f-q"><?= e(t('w.filter.q')) ?></label>
    <input id="f-q" name="q" type="search" value="<?= e($filters['q']) ?>">
  </div>
  <div class="filters__actions">
    <button class="btn btn--primary btn--sm" type="submit"><?= e(t('w.filter.apply')) ?></button>
    <?php if ($hasFilters): ?><a class="link small" href="<?= e($listUrl) ?>"><?= e(t('w.list.clear')) ?></a><?php endif; ?>
  </div>
</form>

<?php if (!$result['rows']): ?>
  <section class="empty empty--sm">
    <?php if ($hasFilters): ?>
      <p><?= e(t('w.list.empty')) ?></p>
      <a class="link" href="<?= e($listUrl) ?>"><?= e(t('w.list.clear')) ?></a>
    <?php elseif ($coachAll): ?>
      <p><?= e(t($athletes ? 'w.list.empty_athletes' : 'athletes.empty.title')) ?></p>
      <a class="link" href="<?= url('athletes.php') ?>"><?= e(t('athletes.title')) ?></a>
    <?php elseif (!$isOwner): ?>
      <p><?= e(t('athletes.no_workouts')) ?></p>
    <?php else: ?>
      <h2><?= e(t('dash.empty.title')) ?></h2>
      <p><?= e(t('dash.empty.lead')) ?></p>
      <a class="btn btn--primary" href="<?= url('workout_edit.php') ?>"><?= e(t('nav.new')) ?></a>
    <?php endif; ?>
  </section>
<?php elseif (!$isOwner): ?>
  <form method="get" action="<?= url('compare.php') ?>" class="bulk-form" data-compare>
    <p class="muted small bulk__all"><?= e(t('cmp.list_hint')) ?></p>
    <?php view('partials/workout_rows', ['rows' => $result['rows'], 'showAuthor' => $coachAll, 'selectable' => true, 'showVis' => false]); ?>
    <div class="bulk" role="region" aria-label="<?= e(t('cmp.title')) ?>">
      <span class="bulk__count muted small" data-compare-count data-label="<?= e(t('w.bulk.count', ['n' => '%n'])) ?>"></span>
      <button class="btn btn--primary btn--sm" type="submit" data-compare-go data-label="<?= e(t('cmp.list_btn')) ?>" data-two="<?= e(t('cmp.list_two')) ?>"><?= e(t('cmp.list_btn')) ?></button>
    </div>
  </form>
<?php else: ?>
  <form method="post" action="<?= url('workouts_bulk.php') ?>" class="bulk-form">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
    <label class="check bulk__all"><input type="checkbox" data-check-all> <?= e(t('w.bulk.all')) ?></label>
    <?php view('partials/workout_rows', ['rows' => $result['rows'], 'selectable' => true]); ?>
    <div class="bulk" role="region" aria-label="<?= e(t('w.bulk.label')) ?>">
      <span class="bulk__count muted small" data-check-count data-label="<?= e(t('w.bulk.count', ['n' => '%n'])) ?>"></span>
      <button class="btn btn--ghost btn--sm" type="submit" name="action" value="deactivate" title="<?= e(t('w.inactive_hint')) ?>"><?= e(t('w.deactivate')) ?></button>
      <button class="btn btn--ghost btn--sm" type="submit" name="action" value="activate"><?= e(t('w.activate')) ?></button>
      <span class="bulk__vis">
        <label class="sr-only" for="bulk-vis"><?= e(t('vis.label')) ?></label>
        <select id="bulk-vis" name="visibility">
          <?php foreach (VISIBILITIES as $vis): ?><option value="<?= $vis ?>"><?= e(t('vis.' . $vis)) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn--ghost btn--sm" type="submit" name="action" value="visibility"><?= e(t('vis.apply')) ?></button>
      </span>
      <button class="btn btn--danger btn--sm" type="submit" name="action" value="delete" data-confirm="<?= e(t('w.bulk.delete_confirm')) ?>"><?= e(t('w.delete')) ?></button>
    </div>
  </form>
<?php endif; ?>

  <?php if ($result['rows'] && $result['pages'] > 1): ?>
  <nav class="pager" aria-label="<?= e(t('pager.page', ['p' => $page, 'n' => $result['pages']])) ?>">
    <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="?<?= e($qs(['page' => $page - 1])) ?>"><?= e(t('pager.prev')) ?></a><?php endif; ?>
    <span class="muted small"><?= e(t('pager.page', ['p' => $page, 'n' => $result['pages']])) ?></span>
    <?php if ($page < $result['pages']): ?><a class="btn btn--ghost btn--sm" href="?<?= e($qs(['page' => $page + 1])) ?>"><?= e(t('pager.next')) ?></a><?php endif; ?>
  </nav>
  <?php endif; ?>
