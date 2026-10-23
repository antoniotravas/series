<?php
$o = fn(string $k) => old($k, $u[$k] ?? '');
$isSection = fn(string $s) => $section === $s;
?>
<header class="page-head">
  <div class="profile-head">
    <?php if ($av = avatar_url($u)): ?>
      <img src="<?= e($av) ?>" alt="" class="avatar avatar--lg">
    <?php else: ?>
      <span class="avatar avatar--lg" aria-hidden="true"><?= e(initials($u['display_name'])) ?></span>
    <?php endif; ?>
    <div>
      <h1><?= e($u['display_name']) ?><?php if ($u['is_coach']): ?> <span class="badge badge--coach"><?= e(t('coach.badge')) ?></span><?php endif; ?><?php if ($u['is_admin']): ?> <span class="badge badge--admin"><?= e(t('admin.role.admin')) ?></span><?php endif; ?></h1>
      <p class="muted"><?= e(t('p.edit')) ?></p>
    </div>
  </div>
  <div class="actions"><a class="btn btn--ghost btn--sm" href="<?= url('profile.php') ?>"><?= e(t('p.back')) ?></a></div>
</header>

<div class="profile">
  <form method="post" enctype="multipart/form-data" class="form panel" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="profile">
    <h2><?= e(t('p.personal')) ?></h2>
    <div class="grid-2">
      <div class="field field--wide">
        <label for="display_name"><?= e(t('auth.display_name')) ?></label>
        <input id="display_name" name="display_name" required maxlength="80" value="<?= e($isSection('profile') ? old('display_name') : $u['display_name']) ?>"<?= field_attrs($errors, 'display_name') ?>>
        <?= field_error($errors, 'display_name') ?>
      </div>
      <div class="field field--wide">
        <label for="avatar"><?= e(t('p.avatar')) ?></label>
        <input id="avatar" name="avatar" type="file" accept="image/*"<?= field_attrs($errors, 'avatar') ?>>
        <p class="field__hint"><?= e(t('p.avatar_hint')) ?></p>
        <?php if ($u['avatar']): ?>
          <label class="check"><input type="checkbox" name="remove_avatar" value="1"> <?= e(t('p.avatar_remove')) ?></label>
        <?php endif; ?>
        <?= field_error($errors, 'avatar') ?>
      </div>
      <div class="field">
        <label for="birth_date"><?= e(t('p.birth')) ?></label>
        <input id="birth_date" name="birth_date" type="date" max="<?= date('Y-m-d') ?>" value="<?= e((string)$o('birth_date')) ?>"<?= field_attrs($errors, 'birth_date') ?>>
        <?= field_error($errors, 'birth_date') ?>
      </div>
      <div class="field">
        <label for="sex"><?= e(t('p.sex')) ?></label>
        <select id="sex" name="sex">
          <option value=""></option>
          <?php foreach (['M', 'F', 'X'] as $sx): ?><option value="<?= $sx ?>" <?= $o('sex') === $sx ? 'selected' : '' ?>><?= e(t('p.sex.' . $sx)) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="weight_kg"><?= e(t('p.weight')) ?></label>
        <input id="weight_kg" name="weight_kg" inputmode="decimal" value="<?= e((string)$o('weight_kg')) ?>"<?= field_attrs($errors, 'weight_kg') ?>>
        <?= field_error($errors, 'weight_kg') ?>
      </div>
      <div class="field">
        <label for="height_cm"><?= e(t('p.height')) ?></label>
        <input id="height_cm" name="height_cm" inputmode="numeric" value="<?= e((string)$o('height_cm')) ?>"<?= field_attrs($errors, 'height_cm') ?>>
        <?= field_error($errors, 'height_cm') ?>
      </div>
      <div class="field">
        <label for="club"><?= e(t('p.club')) ?></label>
        <input id="club" name="club" maxlength="120" value="<?= e((string)$o('club')) ?>">
      </div>
      <div class="field">
        <label for="main_event"><?= e(t('p.event')) ?></label>
        <input id="main_event" name="main_event" maxlength="40" placeholder="<?= e(t('p.event_ph')) ?>" value="<?= e((string)$o('main_event')) ?>">
      </div>
      <div class="field">
        <label for="country"><?= e(t('p.country')) ?></label>
        <input id="country" name="country" maxlength="2" autocomplete="country" value="<?= e((string)$o('country')) ?>">
      </div>
      <div class="field">
        <label for="lang"><?= e(t('p.lang')) ?></label>
        <select id="lang" name="lang">
          <?php foreach (LANGS as $code => $label): ?><option value="<?= $code ?>" <?= $o('lang') === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field field--wide">
        <label class="check"><input type="checkbox" name="is_coach" value="1" <?= ($isSection('profile') ? old('is_coach') : $u['is_coach']) ? 'checked' : '' ?>> <?= e(t('coach.profile_toggle')) ?></label>
        <p class="field__hint"><?= e(t('coach.profile_hint')) ?></p>
      </div>
      <div class="field field--wide" data-coach-only>
        <label for="bio"><?= e(t('p.bio')) ?></label>
        <textarea id="bio" name="bio" rows="5" maxlength="1000" placeholder="<?= e(t('p.bio_ph')) ?>"<?= field_attrs($errors, 'bio') ?>><?= e((string)$o('bio')) ?></textarea>
        <p class="field__hint"><?= e(t('p.bio_hint')) ?></p>
        <?= field_error($errors, 'bio') ?>
      </div>
    </div>
    <fieldset class="privacy">
      <legend><?= e(t('p.priv.title')) ?></legend>
      <p class="field__hint"><?= e(t('p.priv.lead')) ?></p>
      <?php $priv = profile_privacy($u); $oldPriv = $isSection('profile') ? (array)old('privacy', []) : []; ?>
      <div class="privacy__list">
        <?php foreach (PROFILE_FIELDS as $f => $default): $cur = $oldPriv[$f] ?? $priv[$f]; ?>
        <div class="privacy__row"<?= $f === 'bio' ? ' data-coach-only' : '' ?>>
          <label for="priv-<?= $f ?>"><?= e(t('p.priv.' . $f)) ?></label>
          <select id="priv-<?= $f ?>" name="privacy[<?= $f ?>]">
            <?php foreach (VISIBILITIES as $vis): ?><option value="<?= $vis ?>" <?= $cur === $vis ? 'selected' : '' ?>><?= e(t('vis.' . $vis)) ?></option><?php endforeach; ?>
          </select>
        </div>
        <?php endforeach; ?>
      </div>
      <p class="field__hint"><?= e(t('p.priv.coach')) ?></p>
    </fieldset>
    <button class="btn btn--primary" type="submit"><?= e(t('p.save')) ?></button>
  </form>

  <div class="profile__side">
    <form method="post" class="form panel" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="email">
      <h2><?= e(t('p.email_section')) ?></h2>
      <div class="field">
        <label for="email"><?= e(t('auth.email')) ?></label>
        <input id="email" name="email" type="email" autocomplete="email" value="<?= e($isSection('email') ? old('email') : $u['email']) ?>"<?= field_attrs($errors, 'email') ?>>
        <?= field_error($errors, 'email') ?>
      </div>
      <?php if ($u['password_hash']): ?>
      <div class="field">
        <label for="email_current_password"><?= e(t('p.current_pw')) ?></label>
        <input id="email_current_password" name="current_password" type="password" autocomplete="current-password"<?= field_attrs($errors, 'email_current_password') ?>>
        <?= field_error($errors, 'email_current_password') ?>
      </div>
      <?php endif; ?>
      <button class="btn btn--ghost" type="submit"><?= e(t('p.email_save')) ?></button>
    </form>

    <form method="post" class="form panel" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">
      <h2><?= e(t('p.pw_section')) ?></h2>
      <?php if ($u['password_hash']): ?>
      <div class="field">
        <label for="current_password"><?= e(t('p.current_pw')) ?></label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password"<?= field_attrs($errors, 'current_password') ?>>
        <?= field_error($errors, 'current_password') ?>
      </div>
      <?php endif; ?>
      <div class="field">
        <label for="password"><?= e(t('p.new_pw')) ?></label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8"<?= field_attrs($errors, 'password') ?>>
        <p class="field__hint"><?= e(t('auth.password_hint')) ?></p>
        <?= field_error($errors, 'password') ?>
      </div>
      <div class="field">
        <label for="password_confirm"><?= e(t('auth.password_confirm')) ?></label>
        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password"<?= field_attrs($errors, 'password_confirm') ?>>
        <?= field_error($errors, 'password_confirm') ?>
      </div>
      <button class="btn btn--ghost" type="submit"><?= e(t('p.pw_save')) ?></button>
    </form>

    <details class="panel panel--danger" <?= $isSection('delete') ? 'open' : '' ?>>
      <summary><?= e(t('p.danger')) ?></summary>
      <form method="post" class="form" data-confirm="<?= e(t('p.danger_confirm')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <p class="small"><?= e(t('p.danger_lead')) ?></p>
        <?php if ($u['password_hash']): ?>
        <div class="field">
          <label for="delete_password"><?= e(t('p.current_pw')) ?></label>
          <input id="delete_password" name="current_password" type="password" autocomplete="current-password"<?= field_attrs($errors, 'delete_password') ?>>
          <?= field_error($errors, 'delete_password') ?>
        </div>
        <?php endif; ?>
        <button class="btn btn--danger" type="submit"><?= e(t('p.danger_btn')) ?></button>
      </form>
    </details>
  </div>
</div>
