<?php /** @var array $rows resultado de list_workouts() */ ?>
<ul class="wlist">
  <?php foreach ($rows as $r):
      $ts = strtotime($r['workout_date']);
  ?>
  <li class="wlist__item">
    <a href="<?= url('workout.php?id=' . (int)$r['id']) ?>" class="wlist__link">
      <time class="wlist__date" datetime="<?= e($r['workout_date']) ?>">
        <span class="wlist__day"><?= date('j', $ts) ?></span>
        <span class="wlist__mon"><?= e(mb_substr(month_name((int)date('n', $ts)), 0, 3)) ?> <?= date('Y', $ts) ?></span>
      </time>
      <span class="wlist__body">
        <span class="wlist__sets"><?= e(sets_summary($r['sets'])) ?></span>
        <span class="wlist__meta">
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
