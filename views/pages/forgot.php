<div class="auth">
  <h1><?= e(t('auth.forgot.title')) ?></h1>
  <?php if ($sent): ?>
    <div class="flash flash--ok" role="status"><?= e(t('auth.forgot.sent')) ?></div>
    <?php if (config('debug')): ?><p class="muted"><?= e(t('auth.forgot.dev')) ?></p><?php endif; ?>
  <?php else: ?>
    <p><?= e(t('auth.forgot.lead')) ?></p>
    <form method="post" class="form" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="email"><?= e(t('auth.email')) ?></label>
        <input id="email" name="email" type="email" autocomplete="email" required autofocus>
      </div>
      <button class="btn btn--primary btn--block" type="submit"><?= e(t('auth.forgot.submit')) ?></button>
    </form>
  <?php endif; ?>
  <p class="auth__links"><a href="<?= url('login.php') ?>"><?= e(t('nav.login')) ?></a></p>
</div>
