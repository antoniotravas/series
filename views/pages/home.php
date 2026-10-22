<section class="cover">
  <picture class="cover__img">
    <source type="image/webp" srcset="<?= asset('img/atleta-800.webp') ?> 800w, <?= asset('img/atleta-1536.webp') ?> 1536w" sizes="100vw">
    <img src="<?= asset('img/atleta-1536.jpg') ?>" srcset="<?= asset('img/atleta-800.jpg') ?> 800w, <?= asset('img/atleta-1536.jpg') ?> 1536w" sizes="100vw" alt="" fetchpriority="high">
  </picture>
  <div class="wrap cover__content">
    <h1 class="cover__title"><?= e(t('home.title')) ?></h1>
    <div class="cover__actions">
      <a class="btn btn--primary btn--lg" href="<?= url('register.php') ?>"><?= e(t('nav.register')) ?></a>
      <a class="btn btn--light btn--lg" href="<?= url('login.php') ?>"><?= e(t('nav.login')) ?></a>
    </div>
  </div>
</section>
