<?php
/**
 * Copia o local escrito nas notas ("Local: …", vindo da importação das folhas antigas)
 * para o campo Local dos treinos de um utilizador que ainda não o têm preenchido.
 *
 * Só mostra o que faria:   php tools/notes_to_location.php email@exemplo.com
 * Grava as alterações:     php tools/notes_to_location.php email@exemplo.com --aplicar
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit;
}

define('ROOT', dirname(__DIR__));
$GLOBALS['config'] = require ROOT . '/config/config.php';
require ROOT . '/src/helpers.php';
require ROOT . '/src/db.php';

$email = $argv[1] ?? '';
$apply = in_array('--aplicar', $argv, true);
if ($email === '' || $email[0] === '-') {
    fwrite(STDERR, "Uso: php tools/notes_to_location.php EMAIL [--aplicar]\n");
    exit(1);
}

$user = q('SELECT id, display_name FROM users WHERE email = ?', [$email])->fetch();
if (!$user) {
    fwrite(STDERR, "Não existe nenhum utilizador com o email $email\n");
    exit(1);
}

$rows = q('SELECT id, workout_date, location, notes FROM workouts WHERE user_id = ? ORDER BY workout_date', [$user['id']])->fetchAll();
$fill = $differs = 0;
$upd = db()->prepare('UPDATE workouts SET location = ? WHERE id = ? AND user_id = ?');

foreach ($rows as $w) {
    // linha "Local: …" (com ou sem * no início), em qualquer posição das notas
    if (!preg_match('/^\s*\*?\s*Local\s*:\s*(.+?)\s*$/miu', (string)$w['notes'], $m)) {
        continue;
    }
    $place = mb_substr($m[1], 0, 120);
    $current = trim((string)$w['location']);
    if ($current === '') {
        $fill++;
        echo "{$w['workout_date']}  #{$w['id']}  → $place\n";
        if ($apply) {
            $upd->execute([$place, $w['id'], $user['id']]);
        }
    } elseif (mb_strtolower($current) !== mb_strtolower($place)) {
        $differs++;
        echo "{$w['workout_date']}  #{$w['id']}  (fica \"$current\"; nas notas: \"$place\")\n";
    }
}

echo "\n{$user['display_name']}: " . count($rows) . " treinos, $fill com o local por preencher";
echo $differs ? ", $differs já com outro local (não mexidos)" : '';
echo $apply ? ". Alterações gravadas.\n" : ".\nNada foi gravado. Para gravar, repete o comando com --aplicar no fim.\n";
