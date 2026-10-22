<?php
/** Lista de pessoas com as ações próprias de cada secção da rede. */
/** @var array $people */
/** @var string $mode incoming | outgoing | coaching | connections | coaches | search */
?>
<ul class="people">
  <?php foreach ($people as $p):
      $meta = array_filter([$p['club'] ?? null, $p['main_event'] ?? null, $p['country'] ?? null]);
  ?>
  <li class="people__item">
    <a class="person" href="<?= url('user.php?id=' . (int)$p['id']) ?>">
      <?php view('partials/avatar', ['u' => $p, 'size' => 'md']); ?>
      <span class="person__text">
        <span class="person__name"><?= e($p['display_name']) ?><?php if (!empty($p['is_coach'])): ?> <span class="badge badge--coach"><?= e(t('coach.badge')) ?></span><?php endif; ?></span>
        <?php if ($mode === 'coaching'): ?>
          <span class="person__meta"><?= e(t($p['incoming']
              ? ($p['i_am_coach'] ? 'coach.req_in_athlete' : 'coach.req_in_coach')
              : ($p['i_am_coach'] ? 'coach.req_out_athlete' : 'coach.req_out_coach'))) ?></span>
        <?php elseif ($meta): ?>
          <span class="person__meta"><?= e(implode(' · ', $meta)) ?></span>
        <?php endif; ?>
      </span>
    </a>
    <?php if (in_array($mode, ['incoming', 'outgoing', 'coaching', 'coaches'], true)): ?>
    <form method="post" action="<?= url('network_action.php') ?>" class="people__actions">
      <?= csrf_field() ?>
      <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
      <?php if ($mode === 'incoming'): ?>
        <input type="hidden" name="id" value="<?= (int)$p['link_id'] ?>">
        <button class="btn btn--primary btn--sm" type="submit" name="action" value="accept"><?= e(t('net.accept')) ?></button>
        <button class="btn btn--ghost btn--sm" type="submit" name="action" value="reject"><?= e(t('net.reject')) ?></button>
      <?php elseif ($mode === 'outgoing'): ?>
        <input type="hidden" name="user_id" value="<?= (int)$p['id'] ?>">
        <button class="btn btn--ghost btn--sm" type="submit" name="action" value="remove"><?= e(t('net.cancel')) ?></button>
      <?php elseif ($mode === 'coaching'): ?>
        <input type="hidden" name="id" value="<?= (int)$p['link_id'] ?>">
        <?php if ($p['incoming']): ?>
          <button class="btn btn--primary btn--sm" type="submit" name="action" value="coach_accept"><?= e(t('net.accept')) ?></button>
          <button class="btn btn--ghost btn--sm" type="submit" name="action" value="coach_reject"><?= e(t('net.reject')) ?></button>
        <?php else: ?>
          <button class="btn btn--ghost btn--sm" type="submit" name="action" value="coach_remove"><?= e(t('net.cancel')) ?></button>
        <?php endif; ?>
      <?php elseif ($mode === 'coaches'): ?>
        <input type="hidden" name="id" value="<?= (int)$p['link_id'] ?>">
        <button class="btn btn--ghost btn--sm" type="submit" name="action" value="coach_remove" data-confirm="<?= e(t('coach.end_confirm', ['name' => $p['display_name']])) ?>"><?= e(t('coach.end')) ?></button>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </li>
  <?php endforeach; ?>
</ul>
