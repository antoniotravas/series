<?php
$me = current_user();
$oid = (int)$other['id'];
$meta = array_filter([$other['club'], $other['main_event'], $other['country']]);
$hidden = fn(string $action, array $extra = []) => csrf_field()
    . '<input type="hidden" name="back" value="' . e($_SERVER['REQUEST_URI'] ?? '') . '">'
    . '<input type="hidden" name="action" value="' . e($action) . '">'
    . implode('', array_map(fn($k, $v) => '<input type="hidden" name="' . e($k) . '" value="' . e((string)$v) . '">', array_keys($extra), $extra));
?>
<header class="page-head">
  <div class="profile-head">
    <?php view('partials/avatar', ['u' => $other, 'size' => 'lg']); ?>
    <div>
      <h1><?= e($other['display_name']) ?><?php if ($other['is_coach']): ?> <span class="badge badge--coach"><?= e(t('coach.badge')) ?></span><?php endif; ?></h1>
      <?php if ($meta): ?><p class="muted"><?= e(implode(' · ', $meta)) ?></p><?php endif; ?>
      <p class="muted small"><?= e(t('p.member_since', ['d' => fmt_date(substr($other['created_at'], 0, 10))])) ?></p>
    </div>
  </div>

  <div class="actions">
    <?php if ($rel['connection'] === null): ?>
      <form method="post" action="<?= url('network_action.php') ?>"><?= $hidden('connect', ['user_id' => $oid]) ?>
        <button class="btn btn--primary btn--sm" type="submit"><?= e(t('net.connect')) ?></button></form>
    <?php elseif ($rel['connection'] === 'received'): ?>
      <form method="post" action="<?= url('network_action.php') ?>"><?= $hidden('accept', ['id' => $rel['connection_id']]) ?>
        <button class="btn btn--primary btn--sm" type="submit"><?= e(t('net.accept')) ?></button></form>
      <form method="post" action="<?= url('network_action.php') ?>"><?= $hidden('reject', ['id' => $rel['connection_id']]) ?>
        <button class="btn btn--ghost btn--sm" type="submit"><?= e(t('net.reject')) ?></button></form>
    <?php elseif ($rel['connection'] === 'sent'): ?>
      <form method="post" action="<?= url('network_action.php') ?>"><?= $hidden('remove', ['user_id' => $oid]) ?>
        <button class="btn btn--ghost btn--sm" type="submit" title="<?= e(t('net.cancel')) ?>"><?= e(t('net.pending')) ?> · <?= e(t('net.cancel')) ?></button></form>
    <?php else: ?>
      <form method="post" action="<?= url('network_action.php') ?>" data-confirm="<?= e(t('net.remove_confirm', ['name' => $other['display_name']])) ?>"><?= $hidden('remove', ['user_id' => $oid]) ?>
        <button class="btn btn--ghost btn--sm" type="submit"><?= e(t('net.connected')) ?> · <?= e(t('net.remove')) ?></button></form>
    <?php endif; ?>

    <?php // Treinador: eu treino o outro ?>
    <?php if ($me['is_coach']): $l = $rel['as_coach']; ?>
      <?php if (!$l): ?>
        <form method="post" action="<?= url('network_action.php') ?>"><?= $hidden('coach_invite', ['user_id' => $oid]) ?>
          <button class="btn btn--ghost btn--sm" type="submit"><?= e(t('coach.invite')) ?></button></form>
      <?php elseif ($l['status'] === 'pending' && (int)$l['requested_by'] !== (int)$me['id']): ?>
        <form method="post" action="<?= url('network_action.php') ?>"><?= $hidden('coach_accept', ['id' => $l['id']]) ?>
          <button class="btn btn--primary btn--sm" type="submit"><?= e(t('coach.accept_athlete')) ?></button></form>
      <?php elseif ($l['status'] === 'pending'): ?>
        <form method="post" action="<?= url('network_action.php') ?>"><?= $hidden('coach_remove', ['id' => $l['id']]) ?>
          <button class="btn btn--ghost btn--sm" type="submit"><?= e(t('coach.invited')) ?> · <?= e(t('net.cancel')) ?></button></form>
      <?php endif; ?>
    <?php endif; ?>

    <?php // Atleta: o outro é treinador e pode treinar-me ?>
    <?php if ($other['is_coach']): $l = $rel['as_athlete']; ?>
      <?php if (!$l): ?>
        <form method="post" action="<?= url('network_action.php') ?>"><?= $hidden('coach_ask', ['user_id' => $oid]) ?>
          <button class="btn btn--ghost btn--sm" type="submit" title="<?= e(t('coach.accept_note')) ?>"><?= e(t('coach.ask')) ?></button></form>
      <?php elseif ($l['status'] === 'pending' && (int)$l['requested_by'] !== (int)$me['id']): ?>
        <form method="post" action="<?= url('network_action.php') ?>" data-confirm="<?= e(t('coach.accept_note')) ?>"><?= $hidden('coach_accept', ['id' => $l['id']]) ?>
          <button class="btn btn--primary btn--sm" type="submit"><?= e(t('coach.accept_coach')) ?></button></form>
      <?php elseif ($l['status'] === 'pending'): ?>
        <form method="post" action="<?= url('network_action.php') ?>"><?= $hidden('coach_remove', ['id' => $l['id']]) ?>
          <button class="btn btn--ghost btn--sm" type="submit"><?= e(t('coach.asked')) ?> · <?= e(t('net.cancel')) ?></button></form>
      <?php else: ?>
        <span class="badge badge--coach"><?= e(t('coach.is_my_coach')) ?></span>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</header>

<?php if ($isCoach): ?>
  <p class="callout">
    <?= e(t('athletes.you_coach', ['name' => $other['display_name']])) ?>
    <a class="link" href="<?= url('dashboard.php?u=' . $oid) ?>"><?= e(t('nav.dashboard')) ?></a> ·
    <a class="link" href="<?= url('workouts.php?u=' . $oid) ?>"><?= e(t('nav.workouts')) ?></a>
  </p>
<?php endif; ?>

<section aria-labelledby="uw-h">
  <h2 id="uw-h"><?= e(t('net.user_workouts')) ?> <span class="muted">(<?= (int)$result['total'] ?>)</span></h2>
  <?php if ($result['rows']): ?>
    <?php view('partials/workout_rows', ['rows' => $result['rows']]); ?>
    <?php if ($result['pages'] > 1): ?>
    <nav class="pager" aria-label="<?= e(t('pager.page', ['p' => $page, 'n' => $result['pages']])) ?>">
      <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="?id=<?= $oid ?>&amp;page=<?= $page - 1 ?>"><?= e(t('pager.prev')) ?></a><?php endif; ?>
      <span class="muted small"><?= e(t('pager.page', ['p' => $page, 'n' => $result['pages']])) ?></span>
      <?php if ($page < $result['pages']): ?><a class="btn btn--ghost btn--sm" href="?id=<?= $oid ?>&amp;page=<?= $page + 1 ?>"><?= e(t('pager.next')) ?></a><?php endif; ?>
    </nav>
    <?php endif; ?>
  <?php else: ?>
    <p class="muted"><?= e(t($rel['connection'] === 'accepted' ? 'net.user_none' : 'net.user_none_connect')) ?></p>
  <?php endif; ?>
</section>
