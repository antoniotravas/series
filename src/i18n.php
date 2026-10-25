<?php
declare(strict_types=1);

const LANGS = ['pt' => 'Português', 'en' => 'English'];

function current_lang(): string
{
    if (!empty($GLOBALS['lang_override'])) {
        return $GLOBALS['lang_override'];
    }
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    $lang = $_SESSION['lang'] ?? null;
    if (!$lang) {
        $accept = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'pt', 0, 2));
        $lang = isset(LANGS[$accept]) ? $accept : 'en';
        $_SESSION['lang'] = $lang;
    }
    return $lang;
}

function set_lang(string $lang): void
{
    if (isset(LANGS[$lang])) {
        $_SESSION['lang'] = $lang;
    }
}

/** Corre $fn noutra língua (ex.: um email na língua de quem o recebe). */
function in_lang(string $lang, callable $fn): mixed
{
    $prev = $GLOBALS['lang_override'] ?? null;
    $GLOBALS['lang_override'] = isset(LANGS[$lang]) ? $lang : null;
    try {
        return $fn();
    } finally {
        $GLOBALS['lang_override'] = $prev;
    }
}

/** Tradução com substituições :nome */
function t(string $key, array $vars = []): string
{
    static $strings = [];
    $lang = current_lang();
    if (!isset($strings[$lang])) {
        $strings[$lang] = require ROOT . "/src/lang/$lang.php";
        if (!isset($strings['pt'])) {
            $strings['pt'] = require ROOT . '/src/lang/pt.php';
        }
    }
    $s = $strings[$lang][$key] ?? $strings['pt'][$key] ?? $key;
    if ($vars) {
        // strtr substitui primeiro as chaves mais longas (":type" antes de ":t")
        $map = [];
        foreach ($vars as $k => $v) {
            $map[':' . $k] = (string)$v;
        }
        $s = strtr($s, $map);
    }
    return $s;
}

function month_name(int $m): string
{
    return t('month.' . $m);
}

function fmt_date(string $ymd, bool $withWeekday = false): string
{
    $ts = strtotime($ymd);
    $d = (int)date('j', $ts) . ' ' . mb_strtolower(mb_substr(month_name((int)date('n', $ts)), 0, 3)) . ' ' . date('Y', $ts);
    if ($withWeekday) {
        $d = t('weekday.' . date('N', $ts)) . ', ' . $d;
    }
    return $d;
}
