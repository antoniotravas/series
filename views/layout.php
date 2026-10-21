<?php
/** @var string $_view */
$user = current_user();
$docTitle = isset($title) ? $title . ' | ' . config('app_name') : config('app_name');
$active = $active ?? '';
$here = $_SERVER['REQUEST_URI'] ?? url();
?><!doctype html>
<html lang="<?= e(current_lang()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($docTitle) ?></title>
<meta name="description" content="<?= e(t('app.tagline')) ?>">
<meta name="theme-color" content="#16212B">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml">
</head>
<body class="page-<?= e($_view) ?>">
<a class="skip" href="#main"><?= e(t('nav.skip')) ?></a>
<header class="topbar">
  <div class="wrap topbar__inner">
    <a class="brand" href="<?= url($user ? 'dashboard.php' : '') ?>" aria-label="<?= e(config('app_name')) ?>">
      <span class="brand__lanes" aria-hidden="true"><i></i><i></i><i></i></span>
      <span class="brand__name"><?= e(config('app_name')) ?></span>
    </a>
    <?php if ($user): ?>
    <nav class="nav" aria-label="Principal">
      <a href="<?= url('dashboard.php') ?>" <?= $active === 'dashboard' ? 'aria-current="page"' : '' ?>><?= e(t('nav.dashboard')) ?></a>
      <a href="<?= url('workouts.php') ?>" <?= $active === 'workouts' ? 'aria-current="page"' : '' ?>><?= e(t('nav.workouts')) ?></a>
      <a href="<?= url('profile.php') ?>" class="nav__me" <?= $active === 'profile' ? 'aria-current="page"' : '' ?>>
        <?php if ($av = avatar_url($user)): ?>
          <img src="<?= e($av) ?>" alt="" class="avatar avatar--sm">
        <?php else: ?>
          <span class="avatar avatar--sm" aria-hidden="true"><?= e(initials($user['display_name'])) ?></span>
        <?php endif; ?>
        <span class="nav__label"><?= e(t('nav.profile')) ?></span>
      </a>
      <a href="<?= url('workout_edit.php') ?>" class="btn btn--primary btn--sm nav__new"><?= e(t('nav.new')) ?></a>
    </nav>
    <?php else: ?>
    <nav class="nav" aria-label="Principal">
      <a href="<?= url('login.php') ?>" <?= $active === 'login' ? 'aria-current="page"' : '' ?>><?= e(t('nav.login')) ?></a>
      <a href="<?= url('register.php') ?>" class="btn btn--primary btn--sm"><?= e(t('nav.register')) ?></a>
    </nav>
    <?php endif; ?>
  </div>
</header>

<main id="main" class="wrap main">
  <?php foreach (take_flashes() as $f): ?>
    <div class="flash flash--<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>
  <?php view('pages/' . $_view, get_defined_vars()); ?>
</main>

<footer class="footer">
  <div class="wrap footer__inner">
    <span><?= e(config('app_name')) ?>. <?= e(t('app.tagline')) ?></span>
    <form method="post" action="<?= url('lang.php') ?>" class="lang">
      <?= csrf_field() ?>
      <input type="hidden" name="back" value="<?= e($here) ?>">
      <?php foreach (LANGS as $code => $label): ?>
        <button type="submit" name="lang" value="<?= $code ?>" <?= current_lang() === $code ? 'aria-pressed="true"' : 'aria-pressed="false"' ?>><?= e($label) ?></button>
      <?php endforeach; ?>
    </form>
  </div>
</footer>
<script>window.I18N = <?= json_encode([
    'rep' => t('w.rep', ['n' => '%n']),
    'set' => t('w.set', ['n' => '%n']),
    'avg' => t('w.avg'), 'best' => t('w.best'), 'sd' => t('w.sd'), 'pace' => t('w.pace'),
    'confirmDelete' => t('w.delete_confirm'),
    'confirmAccount' => t('p.danger_confirm'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
