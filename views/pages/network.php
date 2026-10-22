<?php $u = current_user(); ?>
<header class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= e(t('net.lead')) ?></p>
  </div>
  <?php if ($u['is_coach']): ?>
  <div class="actions"><a class="btn btn--ghost btn--sm" href="<?= url('athletes.php') ?>"><?= e(t('athletes.title')) ?></a></div>
  <?php endif; ?>
</header>

<div class="net">
<div class="net__main">
  <?php if ($incoming || $coachIn): ?>
  <section class="panel people-panel people-panel--alert" aria-labelledby="req-h">
    <h2 id="req-h"><?= e(t('net.requests')) ?></h2>
    <?php if ($coachIn): ?>
      <p class="small muted"><?= e(t('coach.accept_note')) ?></p>
      <?php view('partials/people', ['people' => $coachIn, 'mode' => 'coaching']); ?>
    <?php endif; ?>
    <?php if ($incoming): ?><?php view('partials/people', ['people' => $incoming, 'mode' => 'incoming']); ?><?php endif; ?>
  </section>
  <?php endif; ?>

  <section class="panel" aria-labelledby="feed-h">
    <h2 id="feed-h"><?= e(t('net.feed')) ?></h2>
    <?php if ($feed): ?>
      <?php view('partials/workout_rows', ['rows' => $feed, 'showAuthor' => true]); ?>
    <?php else: ?>
      <p class="muted"><?= e(t('net.feed.empty')) ?></p>
    <?php endif; ?>
  </section>
</div>

<div class="net__side">
  <section class="panel" id="search" aria-labelledby="search-h">
    <h2 id="search-h"><?= e(t('net.search')) ?></h2>
    <form method="get" action="<?= url('network.php') ?>#search" class="search-row" role="search">
      <label class="sr-only" for="q"><?= e(t('net.search')) ?></label>
      <input id="q" name="q" type="search" minlength="2" placeholder="<?= e(t('net.search_ph')) ?>" value="<?= e($term) ?>">
      <button class="btn btn--primary btn--sm" type="submit"><?= e(t('net.search_btn')) ?></button>
    </form>
    <?php if ($results !== null): ?>
      <?php if ($results): ?>
        <?php view('partials/people', ['people' => $results, 'mode' => 'search']); ?>
      <?php else: ?>
        <p class="muted small"><?= e(t('net.search_none')) ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </section>

  <?php if ($coaches): ?>
  <section class="panel" aria-labelledby="coaches-h">
    <h2 id="coaches-h"><?= e(t('coach.my_coaches')) ?></h2>
    <p class="small muted"><?= e(t('coach.my_coaches_note')) ?></p>
    <?php view('partials/people', ['people' => $coaches, 'mode' => 'coaches']); ?>
  </section>
  <?php endif; ?>

  <section class="panel" aria-labelledby="conn-h">
    <h2 id="conn-h"><?= e(t('net.connections')) ?> <span class="muted">(<?= count($connections) ?>)</span></h2>
    <?php if ($connections): ?>
      <?php view('partials/people', ['people' => $connections, 'mode' => 'connections']); ?>
    <?php else: ?>
      <p class="muted small"><?= e(t('net.connections.empty')) ?></p>
    <?php endif; ?>
  </section>

  <?php if ($outgoing || $coachOut): ?>
  <section class="panel" aria-labelledby="sent-h">
    <h2 id="sent-h"><?= e(t('net.sent')) ?></h2>
    <?php if ($coachOut): ?><?php view('partials/people', ['people' => $coachOut, 'mode' => 'coaching']); ?><?php endif; ?>
    <?php if ($outgoing): ?><?php view('partials/people', ['people' => $outgoing, 'mode' => 'outgoing']); ?><?php endif; ?>
  </section>
  <?php endif; ?>
</div>
</div>
