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
    <form method="get" class="filters" role="search">
      <div class="field field--sm">
        <label for="s-year"><?= e(t('strava.filter.year')) ?></label>
        <select id="s-year" name="year" data-autosubmit>
          <option value="0"><?= e(t('strava.filter.all_years')) ?></option>
          <?php foreach ($years as $y): ?>
          <option value="<?= $y ?>"<?= $y === $year ? ' selected' : '' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field field--sm">
        <label for="s-month"><?= e(t('w.filter.month')) ?></label>
        <select id="s-month" name="month" data-autosubmit>
          <option value="0"><?= e(t('strava.filter.all_months')) ?></option>
          <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>"<?= $m === $month ? ' selected' : '' ?>><?= e(t('month.' . $m)) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <noscript><button class="btn btn--ghost btn--sm" type="submit"><?= e(t('strava.filter.apply')) ?></button></noscript>
    </form>
    <p class="muted small"><?= e(t('strava.idle_note')) ?></p>

    <?php if (!$runs): ?>
      <p class="muted"><?= e(t('strava.none')) ?></p>
    <?php else: ?>
    <ul class="sact-list">
      <?php foreach ($runs as $r): ?>
        <li class="sact">
          <div class="sact__info">
            <span class="sact__name"><?= e((string)$r['name']) ?></span>
            <span class="sact__meta">
              <?= e(date('d/m/Y H:i', strtotime((string)$r['start_date_local']))) ?>
              · <b><?= e(fmt_km((float)$r['distance'])) ?> km</b>
              · <?= e(fmt_time((float)$r['moving_time'], 0)) ?>
            </span>
          </div>
          <a class="btn btn--primary btn--sm sact__btn" href="<?= url('workout_edit.php?strava=' . (int)$r['id']) ?>"><?= e(t('strava.import')) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if ($more): ?>
    <p><a class="btn btn--ghost btn--sm" href="<?= url('strava_activities.php?' . http_build_query(array_filter(['year' => $year, 'month' => $month, 'page' => $next]))) ?>"><?= e(t('strava.more')) ?></a></p>
    <?php else: ?>
    <p class="muted small"><?= e(t('strava.end')) ?></p>
    <?php endif; ?>
  </section>
<?php endif; ?>
</content>
