<header class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= e(t('strava.lead')) ?></p>
  </div>
  <?php if ($connected): ?>
  <div class="actions">
    <form method="post" action="<?= url('strava_disconnect.php') ?>">
      <?= csrf_field() ?>
      <button class="btn btn--ghost btn--sm" type="submit"><?= e(t('strava.disconnect')) ?></button>
    </form>
  </div>
  <?php endif; ?>
</header>

<?php if (!$configured): ?>
  <section class="panel"><p><?= e(t('strava.not_configured')) ?></p></section>
<?php elseif (!$connected): ?>
  <section class="panel">
    <p><?= e(t('strava.connect_lead')) ?></p>
    <p><a class="btn btn--primary" href="<?= url('strava_connect.php') ?>"><svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path fill="#fff" d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/></svg><?= e(t('strava.connect')) ?></a></p>
  </section>
<?php else: ?>
  <section class="panel">
    <?php if (!$runs): ?>
      <p class="muted"><?= e(t('strava.none')) ?></p>
    <?php else: ?>
    <div class="table-scroll">
    <table class="cmp-table">
      <thead>
        <tr>
          <th><?= e(t('strava.col.date')) ?></th>
          <th><?= e(t('strava.col.name')) ?></th>
          <th class="num"><?= e(t('strava.col.distance')) ?></th>
          <th class="num"><?= e(t('strava.col.time')) ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($runs as $r): ?>
        <tr>
          <td><?= e(date('d/m/Y H:i', strtotime((string)$r['start_date_local']))) ?></td>
          <td><?= e((string)$r['name']) ?></td>
          <td class="num"><?= e(fmt_km((float)$r['distance'])) ?> km</td>
          <td class="num"><?= e(fmt_time((float)$r['moving_time'], 0)) ?></td>
          <td><a class="btn btn--ghost btn--sm" href="<?= url('workout_edit.php?strava=' . (int)$r['id']) ?>"><?= e(t('strava.import')) ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
    <p><a class="btn btn--ghost btn--sm" href="<?= url('strava_activities.php?page=' . ($page + 1)) ?>"><?= e(t('strava.more')) ?></a></p>
  </section>
<?php endif; ?>
