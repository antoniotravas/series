<?php
$me = current_user();
$qs = fn(array $extra) => http_build_query(array_filter(['q' => $term] + $extra));
// ícones do cartão (telemóvel); no computador ficam escondidos
$ico = fn(string $d) => '<svg class="icon admin-card__ico" viewBox="0 0 24 24" aria-hidden="true">' . $d . '</svg>';
$icoCal   = $ico('<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>');
$icoBars  = $ico('<path d="M6 6v12M18 6v12M3 9v6M21 9v6M6 12h12"/>');
$icoRoles = $ico('<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>');
$icoCoach = $ico('<circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6s7 2 7 6M19 8v6M16 11h6"/>');
$icoAdmin = $ico('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>');
$icoLink  = $ico('<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>');
$icoOff   = $ico('<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/>');
$icoOn    = $ico('<path d="M20 6 9 17l-5-5"/>');
?>
<header class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= e(t('admin.lead')) ?></p>
  </div>
</header>

<section class="totals totals--admin" aria-label="<?= e(t('admin.stats')) ?>">
  <div><strong><?= (int)$stats['users'] ?></strong><span><?= e(t('admin.s.users', ['n' => (int)$stats['users_30d']])) ?><?php if ($stats['disabled']): ?><br><?= e(t('admin.s.disabled', ['n' => (int)$stats['disabled']])) ?><?php endif; ?></span></div>
  <div><strong><?= (int)$stats['coaches'] ?></strong><span><?= e(t('admin.s.coaches')) ?></span></div>
  <div><strong><?= (int)$stats['workouts'] ?></strong><span><?= e(t('admin.s.workouts', ['n' => (int)$stats['workouts_7d']])) ?></span></div>
  <div><strong><?= (int)$stats['connections'] ?></strong><span><?= e(t('admin.s.connections')) ?></span></div>
</section>

<form method="get" class="filters" role="search">
  <div class="field field--sm field--grow">
    <label for="aq"><?= e(t('admin.search')) ?></label>
    <input id="aq" name="q" type="search" value="<?= e($term) ?>">
  </div>
  <div class="filters__actions">
    <button class="btn btn--primary btn--sm" type="submit"><?= e(t('net.search_btn')) ?></button>
    <?php if ($term !== ''): ?><a class="link small" href="<?= url('admin.php') ?>"><?= e(t('w.list.clear')) ?></a><?php endif; ?>
  </div>
</form>

<p class="muted small"><?= e(t('admin.count', ['n' => $total])) ?></p>
<div class="table-scroll">
<table class="admin-table">
  <thead>
    <tr>
      <th scope="col"><?= e(t('admin.col.user')) ?></th>
      <th scope="col"><?= e(t('admin.col.since')) ?></th>
      <th scope="col" class="num"><?= e(t('admin.col.workouts')) ?></th>
      <th scope="col"><?= e(t('admin.col.roles')) ?></th>
      <th scope="col"><span class="sr-only"><?= e(t('athletes.col.actions')) ?></span></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $u): $self = (int)$u['id'] === (int)$me['id']; ?>
    <tr<?= $u['active'] ? '' : ' class="is-off"' ?>>
      <th scope="row">
        <a class="person person--row" href="<?= url($self ? 'profile.php' : 'user.php?id=' . (int)$u['id']) ?>">
          <?php view('partials/avatar', ['u' => $u, 'size' => 'sm']); ?>
          <span><?= e($u['display_name']) ?><small class="muted"><?= e($u['email']) ?></small></span>
        </a>
      </th>
      <td class="admin-table__stat">
        <?= $icoCal ?>
        <span><span class="admin-card__label"><?= e(t('admin.col.since')) ?></span><?= e(fmt_date(substr($u['created_at'], 0, 10))) ?></span>
      </td>
      <td class="num admin-table__stat">
        <?= $icoBars ?>
        <span><span class="admin-card__label"><?= e(t('admin.col.workouts')) ?></span><?= (int)$u['workouts'] ?><?php if ($u['last_workout']): ?><small class="muted"><?= e(fmt_date($u['last_workout'])) ?></small><?php endif; ?></span>
      </td>
      <td class="admin-table__roles">
        <?= $icoRoles ?><span class="admin-card__label"><?= e(t('admin.col.roles')) ?></span>
        <?php if (!$u['active']): ?><span class="badge badge--admin"><?= e(t('admin.role.disabled')) ?></span><?php endif; ?>
        <?php if ($u['is_admin']): ?><span class="badge badge--admin"><?= e(t('admin.role.admin')) ?></span><?php endif; ?>
        <?php if ($u['is_coach']): ?><span class="badge badge--coach"><?= e(t('coach.badge')) ?></span><?php endif; ?>
        <?php if ($u['google_id']): ?><span class="badge">Google</span><?php endif; ?>
        <?php if ($u['strava_keep']): ?><span class="badge"><?= e(t('admin.role.strava')) ?></span><?php endif; ?>
      </td>
      <td class="admin-table__actions">
        <?php if (!$self): ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
          <button class="btn btn--ghost btn--sm admin-card__btn" type="submit" name="action" value="coach"><?= $icoCoach ?><?= e(t($u['is_coach'] ? 'admin.coach_off' : 'admin.coach_on')) ?></button>
          <button class="btn btn--ghost btn--sm admin-card__btn" type="submit" name="action" value="admin" data-confirm="<?= e(t('admin.admin_confirm', ['name' => $u['display_name']])) ?>"><?= $icoAdmin ?><?= e(t($u['is_admin'] ? 'admin.admin_off' : 'admin.admin_on')) ?></button>
          <button class="btn btn--ghost btn--sm admin-card__btn" type="submit" name="action" value="strava"><?= $icoLink ?><?= e(t($u['strava_keep'] ? 'admin.strava_off' : 'admin.strava_on')) ?></button>
          <?php if ($u['active']): ?>
            <button class="btn btn--danger btn--sm admin-card__btn" type="submit" name="action" value="active" data-confirm="<?= e(t('admin.disable_confirm', ['name' => $u['display_name']])) ?>"><?= $icoOff ?><?= e(t('admin.disable')) ?></button>
          <?php else: ?>
            <button class="btn btn--ghost btn--sm admin-card__btn" type="submit" name="action" value="active"><?= $icoOn ?><?= e(t('admin.enable')) ?></button>
          <?php endif; ?>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php if ($pages > 1): ?>
<nav class="pager" aria-label="<?= e(t('pager.page', ['p' => $page, 'n' => $pages])) ?>">
  <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="?<?= e($qs(['page' => $page - 1])) ?>"><?= e(t('pager.prev')) ?></a><?php endif; ?>
  <span class="muted small"><?= e(t('pager.page', ['p' => $page, 'n' => $pages])) ?></span>
  <?php if ($page < $pages): ?><a class="btn btn--ghost btn--sm" href="?<?= e($qs(['page' => $page + 1])) ?>"><?= e(t('pager.next')) ?></a><?php endif; ?>
</nav>
<?php endif; ?>
