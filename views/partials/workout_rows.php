<?php
/** @var array $rows resultado de list_workouts() */
/** @var bool $selectable mostrar caixas de seleção (ações em lote) */
$selectable = $selectable ?? false;
$showVis = $showVis ?? $selectable;        // marcar treinos partilhados (lista do próprio)
$showAuthor = $showAuthor ?? false;        // feed: nome de quem treinou
?>
<ul class="wlist">
  <?php foreach ($rows as $r):
      $ts = strtotime($r['workout_date']);
  ?>
  <li class="wlist__item<?= $r['active'] ? '' : ' wlist__item--off' ?><?= $selectable ? ' wlist__item--pick' : '' ?>">
    <?php if ($selectable): ?>
      <input type="checkbox" class="wlist__pick" name="ids[]" value="<?= (int)$r['id'] ?>" aria-label="<?= e(t('w.bulk.pick', ['d' => fmt_date($r['workout_date'])])) ?>">
    <?php endif; ?>
    <a href="<?= url('workout.php?id=' . (int)$r['id']) ?>" class="wlist__link">
      <time class="wlist__date" datetime="<?= e($r['workout_date']) ?>">
        <span class="wlist__day"><?= date('j', $ts) ?></span>
        <span class="wlist__mon"><?= e(mb_substr(month_name((int)date('n', $ts)), 0, 3)) ?> <?= date('Y', $ts) ?></span>
      </time>
      <span class="wlist__body">
        <span class="wlist__sets"><?= e(sets_summary($r['sets'])) ?></span>
        <span class="wlist__meta">
          <?php if ($showAuthor): ?><span class="wlist__author"><?php view('partials/avatar', ['u' => ['avatar' => $r['author_avatar'], 'display_name' => $r['author_name']], 'size' => 'xs']); ?><?= e($r['author_name']) ?></span><?php endif; ?>
          <?php if ($showVis && $r['visibility'] !== 'private'): ?><span class="badge badge--vis-<?= e($r['visibility']) ?>"><?= e(t('vis.' . $r['visibility'])) ?></span><?php endif; ?>
          <?php if (!$r['active']): ?><span class="badge"><?= e(t('w.inactive')) ?></span><?php endif; ?>
          <?php if ($r['title']): ?><span><?= e($r['title']) ?></span><?php endif; ?>
          <?php if ($r['location']): ?><span><?= e($r['location']) ?></span><?php endif; ?>
          <?php if ($r['start_time']): ?><span><?= e(substr($r['start_time'], 0, 5)) ?></span><?php endif; ?>
        </span>
      </span>
      <span class="wlist__vol"><?= fmt_km((float)$r['volume_m']) ?><small>km</small></span>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
