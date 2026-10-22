<?php
/** @var array $athletes resultado de list_athletes() */
/** @var bool $compact versão do painel (sem ações) */
$compact = $compact ?? false;
?>
<section class="panel athletes" aria-labelledby="ath-h">
  <div class="panel__head">
    <h2 id="ath-h"><?= e(t('athletes.title')) ?></h2>
    <?php if ($compact): ?><a class="link small" href="<?= url('athletes.php') ?>"><?= e(t('athletes.manage')) ?></a><?php endif; ?>
  </div>
  <table class="athletes__table">
    <thead>
      <tr>
        <th scope="col"><?= e(t('athletes.col.athlete')) ?></th>
        <th scope="col"><?= e(t('athletes.col.last')) ?></th>
        <th scope="col" class="num"><?= e(t('athletes.col.workouts30')) ?></th>
        <th scope="col" class="num"><?= e(t('athletes.col.km30')) ?></th>
        <?php if (!$compact): ?><th scope="col"><span class="sr-only"><?= e(t('athletes.col.actions')) ?></span></th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($athletes as $a): ?>
      <tr>
        <th scope="row">
          <a class="person person--row" href="<?= url('dashboard.php?u=' . (int)$a['id']) ?>">
            <?php view('partials/avatar', ['u' => $a, 'size' => 'sm']); ?>
            <span><?= e($a['display_name']) ?><?php if ($a['club']): ?><small class="muted"><?= e($a['club']) ?></small><?php endif; ?></span>
          </a>
        </th>
        <td data-label="<?= e(t('athletes.col.last')) ?>"><?= $a['last_workout'] ? e(fmt_date($a['last_workout'])) : '–' ?></td>
        <td class="num" data-label="<?= e(t('athletes.col.workouts30')) ?>"><?= (int)$a['workouts_30d'] ?></td>
        <td class="num" data-label="<?= e(t('athletes.col.km30')) ?>"><?= fmt_km((float)$a['meters_30d']) ?></td>
        <?php if (!$compact): ?>
        <td class="athletes__actions">
          <a class="btn btn--ghost btn--sm" href="<?= url('workouts.php?u=' . (int)$a['id']) ?>"><?= e(t('nav.workouts')) ?></a>
          <form method="post" action="<?= url('network_action.php') ?>" data-confirm="<?= e(t('athletes.remove_confirm', ['name' => $a['display_name']])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$a['link_id'] ?>">
            <button class="btn btn--icon" type="submit" name="action" value="coach_remove" title="<?= e(t('athletes.remove')) ?>" aria-label="<?= e(t('athletes.remove')) ?>: <?= e($a['display_name']) ?>">
              <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
