<?php
declare(strict_types=1);

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return config('base_url') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT . '/public/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 0;
    return url('assets/' . $path) . '?v=' . $v;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require ROOT . '/views/' . $name . '.php';
}

function render(string $name, array $data = []): void
{
    $data['_view'] = $name;
    view('layout', $data);
}

// ---- CSRF ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit(t('error.csrf'));
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// ---- Mensagens flash ----
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---- Old input (repor formulários após erro) ----
function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function keep_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['current_password'], $data['_csrf']);
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

// ---- Tempos ----

/**
 * Converte "1:32.4", "92.4", "4:05:12" em segundos (float). Devolve null se inválido.
 */
function parse_time(?string $s): ?float
{
    $s = trim(str_replace(',', '.', (string)$s));
    if ($s === '') {
        return null;
    }
    $s = preg_replace('/\s+/', ':', $s);
    // "1.32.4" (teclado numérico sem ':') -> "1:32.4"
    if (substr_count($s, '.') >= 2) {
        $last = strrpos($s, '.');
        $s = str_replace('.', ':', substr($s, 0, $last)) . substr($s, $last);
    }
    if (!preg_match('/^(?:(\d+):)?(?:(\d+):)?(\d+(?:\.\d{1,2})?)$/', $s, $m)) {
        return null;
    }
    $parts = array_values(array_filter([$m[1] ?? '', $m[2] ?? ''], fn($p) => $p !== ''));
    $sec = (float)$m[3];
    if (count($parts) === 2) {
        $sec += (int)$parts[0] * 3600 + (int)$parts[1] * 60;
    } elseif (count($parts) === 1) {
        $sec += (int)$parts[0] * 60;
    }
    return $sec > 0 ? round($sec, 2) : null;
}

/** 92.4 -> "1:32.4"; 58 -> "58.0" */
function fmt_time(?float $sec, int $decimals = 1): string
{
    if ($sec === null) {
        return '–';
    }
    $neg = $sec < 0;
    $sec = abs($sec);
    $h = (int)floor($sec / 3600);
    $m = (int)floor(($sec - $h * 3600) / 60);
    $s = $sec - $h * 3600 - $m * 60;
    $sStr = number_format($s, $decimals, '.', '');
    if ($sStr === number_format(60, $decimals, '.', '')) {
        $m++;
        $sStr = number_format(0, $decimals, '.', '');
    }
    if ($h > 0 || $m > 0) {
        $sStr = str_pad($sStr, $decimals ? 3 + $decimals : 2, '0', STR_PAD_LEFT);
    }
    $out = $h > 0 ? sprintf('%d:%02d:%s', $h, $m, $sStr) : ($m > 0 ? sprintf('%d:%s', $m, $sStr) : $sStr);
    return ($neg ? '−' : '') . $out;
}

/** Ritmo em min/km a partir de distância e tempo */
function fmt_pace(float $distanceM, float $sec): string
{
    if ($distanceM <= 0) {
        return '–';
    }
    $perKm = $sec / ($distanceM / 1000);
    $m = (int)floor($perKm / 60);
    $s = (int)round($perKm - $m * 60);
    if ($s === 60) {
        $m++;
        $s = 0;
    }
    return sprintf('%d:%02d', $m, $s);
}

function fmt_distance(int|float $m): string
{
    if ($m >= 1000 && fmod((float)$m, 1000.0) !== 0.0) {
        return number_format($m / 1000, 1, ',', ' ') . ' km';
    }
    if ($m >= 1000) {
        return number_format($m / 1000, 0, ',', ' ') . ' km';
    }
    return $m . ' m';
}

function fmt_km(float $m): string
{
    return number_format($m / 1000, 1, current_lang() === 'pt' ? ',' : '.', ' ');
}

/** Mensagem de erro de um campo + atributos aria (usar dentro do <input>) */
function field_error(array $errors, string $key): string
{
    if (empty($errors[$key])) {
        return '';
    }
    return '<p class="field__error" id="err-' . e($key) . '">' . e($errors[$key]) . '</p>';
}

function field_attrs(array $errors, string $key): string
{
    return empty($errors[$key]) ? '' : ' aria-invalid="true" aria-describedby="err-' . e($key) . '"';
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function log_mail(string $to, string $subject, string $body): void
{
    $line = sprintf("[%s] To: %s\nSubject: %s\n%s\n\n", date('c'), $to, $subject, $body);
    file_put_contents(ROOT . '/storage/logs/mail.log', $line, FILE_APPEND | LOCK_EX);
}

function send_mail(string $to, string $subject, string $body): bool
{
    if (config('debug')) {
        log_mail($to, $subject, $body);
        return true;
    }
    $headers = 'From: ' . config('mail_from') . "\r\nContent-Type: text/plain; charset=UTF-8";
    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}
