<?php
/** @var array $u utilizador */
/** @var string $size sm | md | lg */
$size = $size ?? 'sm';
?>
<?php if ($av = avatar_url($u)): ?>
  <img src="<?= e($av) ?>" alt="" class="avatar avatar--<?= e($size) ?>">
<?php else: ?>
  <span class="avatar avatar--<?= e($size) ?>" aria-hidden="true"><?= e(initials($u['display_name'])) ?></span>
<?php endif; ?>
