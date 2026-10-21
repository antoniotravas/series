<div class="auth">
  <h1><?= e(t('auth.reset.title')) ?></h1>
  <?php if (!$reset): ?>
    <div class="flash flash--error" role="alert"><?= e(t('auth.reset.invalid')) ?></div>
    <p class="auth__links"><a href="<?= url('forgot.php') ?>"><?= e(t('auth.forgot')) ?></a></p>
  <?php else: ?>
    <form method="post" class="form" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="field">
        <label for="password"><?= e(t('p.new_pw')) ?></label>
        <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8"<?= field_attrs($errors, 'password') ?>>
        <p class="field__hint"><?= e(t('auth.password_hint')) ?></p>
        <?= field_error($errors, 'password') ?>
      </div>
      <div class="field">
        <label for="password_confirm"><?= e(t('auth.password_confirm')) ?></label>
        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required<?= field_attrs($errors, 'password_confirm') ?>>
        <?= field_error($errors, 'password_confirm') ?>
      </div>
      <button class="btn btn--primary btn--block" type="submit"><?= e(t('auth.reset.submit')) ?></button>
    </form>
  <?php endif; ?>
</div>
