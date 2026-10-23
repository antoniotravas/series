<?php
// Vista só de leitura; a edição está em profile.php?edit=1 (views/pages/profile_edit.php).
$dash = '—';
$age = $u['birth_date'] ? (new DateTime($u['birth_date']))->diff(new DateTime('today'))->y : null;
$rows = [
    t('auth.display_name') => $u['display_name'],
    t('p.birth')  => $u['birth_date'] ? fmt_date($u['birth_date']) . ' · ' . t('p.age', ['n' => $age]) : null,
    t('p.sex')    => $u['sex'] ? t('p.sex.' . $u['sex']) : null,
    t('p.weight') => $u['weight_kg'] !== null ? rtrim(rtrim(number_format((float)$u['weight_kg'], 1, ',', ''), '0'), ',') : null,
    t('p.height') => $u['height_cm'] !== null ? (string)(int)$u['height_cm'] : null,
    t('p.club')   => $u['club'],
    t('p.event')  => $u['main_event'],
    t('p.country_short') => $u['country'],
    t('p.lang')   => LANGS[$u['lang']] ?? $u['lang'],
    t('coach.profile_toggle') => t($u['is_coach'] ? 'p.yes' : 'p.no'),
];
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
      <p class="muted"><?= e(t('p.member_since', ['d' => fmt_date(substr($u['created_at'], 0, 10))])) ?></p>
    </div>
  </div>
  <div class="actions"><a class="btn btn--primary btn--sm" href="<?= url('profile.php?edit=1') ?>"><?= e(t('p.edit')) ?></a></div>
</header>

<div class="profile">
  <section class="panel" aria-labelledby="pd-h">
    <h2 id="pd-h"><?= e(t('p.personal')) ?></h2>
    <dl class="datalist">
      <?php foreach ($rows as $label => $value): ?>
        <div><dt><?= e($label) ?></dt><dd<?= $value === null || $value === '' ? ' class="muted"' : '' ?>><?= e($value === null || $value === '' ? $dash : $value) ?></dd></div>
      <?php endforeach; ?>
    </dl>
    <?php if ($u['is_coach'] && $u['bio']): ?>
      <h3><?= e(t('p.bio')) ?></h3>
      <p class="bio"><?= e($u['bio']) ?></p>
    <?php endif; ?>
  </section>

  <div class="profile__side">
    <section class="panel" aria-labelledby="pa-h">
      <h2 id="pa-h"><?= e(t('p.account')) ?></h2>
      <dl class="datalist">
        <div><dt><?= e(t('auth.email')) ?></dt><dd><?= e($u['email']) ?></dd></div>
        <div><dt><?= e(t('p.signin')) ?></dt><dd><?= e(implode(' · ', array_filter([$u['password_hash'] ? t('auth.password') : null, $u['google_id'] ? 'Google' : null]))) ?></dd></div>
      </dl>
    </section>
    <p><a class="btn btn--ghost btn--block" href="<?= url('export.php') ?>"><?= e(t('p.export')) ?></a></p>
    <?php if ($u['is_admin']): ?><p><a class="btn btn--ghost btn--block" href="<?= url('admin.php') ?>"><?= e(t('admin.title')) ?></a></p><?php endif; ?>
  </div>
</div>
