<section class="hero">
  <div class="hero__text">
    <h1 class="hero__title"><?= e(t('home.title')) ?></h1>
    <p class="hero__lead"><?= e(t('home.lead')) ?></p>
    <div class="hero__actions">
      <a class="btn btn--primary btn--lg" href="<?= url('register.php') ?>"><?= e(t('home.cta')) ?></a>
      <a class="link" href="<?= url('login.php') ?>"><?= e(t('home.have_account')) ?></a>
    </div>
  </div>
  <figure class="hero__demo">
    <?php view('partials/set_lanes', ['set' => $demo, 'index' => 0, 'pbs' => []]); ?>
    <figcaption><?= e(t('home.demo.label')) ?></figcaption>
  </figure>
</section>

<section class="features">
  <div><h2><?= e(t('home.f1.t')) ?></h2><p><?= e(t('home.f1.d')) ?></p></div>
  <div><h2><?= e(t('home.f2.t')) ?></h2><p><?= e(t('home.f2.d')) ?></p></div>
  <div><h2><?= e(t('home.f3.t')) ?></h2><p><?= e(t('home.f3.d')) ?></p></div>
</section>
