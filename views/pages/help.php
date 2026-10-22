<?php
// O texto do manual vive num ficheiro por idioma (views/help/pt.php, en.php).
// Cada ficheiro define $toc (âncora => título) e $body (função que imprime as secções).
$file = ROOT . '/views/help/' . current_lang() . '.php';
if (!is_file($file)) {
    $file = ROOT . '/views/help/pt.php';
}
require $file;
?>
<header class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= e(t('help.lead')) ?></p>
  </div>
  <div class="actions"><button class="btn btn--ghost btn--sm" type="button" onclick="window.print()"><?= e(t('help.print')) ?></button></div>
</header>

<div class="help">
  <nav class="help__toc" aria-label="<?= e(t('help.toc')) ?>">
    <p class="help__toc-title"><?= e(t('help.toc')) ?></p>
    <ol>
      <?php foreach ($toc as $id => $label): ?>
        <li><a href="#<?= e($id) ?>"><?= e($label) ?></a></li>
      <?php endforeach; ?>
    </ol>
  </nav>
  <div class="help__body">
    <?php $body(); ?>
  </div>
</div>
