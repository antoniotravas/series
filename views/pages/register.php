<div class="auth">
  <h1><?= e(t('auth.register.title')) ?></h1>
  <?php if ($errors): ?><div class="flash flash--error" role="alert"><?= e(t('error.form')) ?></div><?php endif; ?>

  <a class="btn btn--google" href="<?= url('auth_google.php') ?>">
    <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.6 5.4 2.7 13.3l7.9 6.2C12.5 13.6 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.2 5.3-4.6 6.9l7.4 5.7c4.3-4 6.9-9.9 6.9-17.1z"/><path fill="#FBBC05" d="M10.6 28.5c-.5-1.4-.8-2.9-.8-4.5s.3-3.1.8-4.5l-7.9-6.2C1 16.6 0 20.2 0 24s1 7.4 2.7 10.7l7.9-6.2z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.4-5.7c-2.1 1.4-4.8 2.3-8.5 2.3-6.2 0-11.5-4.1-13.4-9.8l-7.9 6.2C6.6 42.6 14.6 48 24 48z"/></svg>
    <?= e(t('auth.google')) ?>
  </a>
  <p class="divider"><span><?= e(t('auth.or')) ?></span></p>

  <form method="post" class="form" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label for="display_name"><?= e(t('auth.display_name')) ?></label>
      <input id="display_name" name="display_name" autocomplete="name" required maxlength="80" value="<?= e(old('display_name')) ?>"<?= field_attrs($errors, 'display_name') ?>>
      <?= field_error($errors, 'display_name') ?>
    </div>
    <div class="field">
      <label for="email"><?= e(t('auth.email')) ?></label>
      <input id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>"<?= field_attrs($errors, 'email') ?>>
      <?= field_error($errors, 'email') ?>
    </div>
    <div class="field">
      <label for="password"><?= e(t('auth.password')) ?></label>
      <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8"<?= field_attrs($errors, 'password') ?>>
      <p class="field__hint"><?= e(t('auth.password_hint')) ?></p>
      <?= field_error($errors, 'password') ?>
    </div>
    <div class="field">
      <label for="password_confirm"><?= e(t('auth.password_confirm')) ?></label>
      <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required<?= field_attrs($errors, 'password_confirm') ?>>
      <?= field_error($errors, 'password_confirm') ?>
    </div>
    <button class="btn btn--primary btn--block" type="submit"><?= e(t('auth.submit_register')) ?></button>
  </form>
  <p class="auth__links"><?= e(t('auth.has_account')) ?> <a href="<?= url('login.php') ?>"><?= e(t('nav.login')) ?></a></p>
</div>
