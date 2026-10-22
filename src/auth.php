<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 8;      // por IP em 15 minutos
const RESET_TTL_MINUTES  = 60;
// Contas com estes emails passam a administrador ao entrar (pode ser alterado em config 'admin_emails')
const ADMIN_EMAILS = ['antoniovicentetravassos@gmail.com'];

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = q('SELECT * FROM users WHERE id = ? AND active = 1', [$_SESSION['uid']])->fetch() ?: null;
            if (!$user) {   // conta apagada ou desativada: termina a sessão
                unset($_SESSION['uid']);
            }
        }
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? null;
        redirect('login.php');
    }
    return $u;
}

function login_user(array $user): void
{
    if (!$user['active']) {
        flash('error', t('auth.disabled'));
        redirect('login.php');
    }
    $admins = array_map('mb_strtolower', (array)config('admin_emails', ADMIN_EMAILS));
    if (!$user['is_admin'] && in_array($user['email'], $admins, true)) {
        q('UPDATE users SET is_admin = 1 WHERE id = ?', [$user['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['lang'] = $user['lang'];
}

/** Volta à página que pediu login (só caminhos internos) ou ao dashboard. */
function redirect_after_login(): never
{
    $next = $_SESSION['after_login'] ?? null;
    unset($_SESSION['after_login']);
    if (is_string($next) && str_starts_with($next, config('base_url') . '/') && !str_contains($next, '//')) {
        header('Location: ' . $next);
        exit;
    }
    redirect('dashboard.php');
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function too_many_attempts(): bool
{
    $n = q(
        'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)',
        [client_ip()]
    )->fetchColumn();
    return (int)$n >= LOGIN_MAX_ATTEMPTS;
}

function record_failed_login(string $email): void
{
    q('INSERT INTO login_attempts (ip, email) VALUES (?, ?)', [client_ip(), mb_substr($email, 0, 190)]);
}

function attempt_login(string $email, string $password): ?array
{
    $user = q('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))])->fetch();
    if (!$user || !$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
        record_failed_login($email);
        return null;
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    q('DELETE FROM login_attempts WHERE ip = ?', [client_ip()]);
    return $user;
}

/** Devolve lista de erros (chave do campo => mensagem). */
function validate_password(string $pw, string $confirm): array
{
    $errors = [];
    if (mb_strlen($pw) < 8) {
        $errors['password'] = t('error.password_short');
    }
    if ($pw !== $confirm) {
        $errors['password_confirm'] = t('error.password_mismatch');
    }
    return $errors;
}

function register_user(array $in): array
{
    $email = mb_strtolower(trim($in['email'] ?? ''));
    $name  = trim($in['display_name'] ?? '');
    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = t('error.email_invalid');
    } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetchColumn()) {
        $errors['email'] = t('error.email_taken');
    }
    if ($name === '' || mb_strlen($name) > 80) {
        $errors['display_name'] = t('error.name_required');
    }
    $errors += validate_password($in['password'] ?? '', $in['password_confirm'] ?? '');

    if ($errors) {
        return ['errors' => $errors];
    }

    q(
        'INSERT INTO users (email, password_hash, display_name, lang) VALUES (?, ?, ?, ?)',
        [$email, password_hash($in['password'], PASSWORD_DEFAULT), $name, current_lang()]
    );
    $id = (int)db()->lastInsertId();
    return ['user' => q('SELECT * FROM users WHERE id = ?', [$id])->fetch()];
}

/**
 * Troca o "code" do Google por tokens e devolve os dados do id_token
 * (sub, email, email_verified, name). O id_token chega diretamente do Google
 * por HTTPS, por isso basta validar emissor, destinatário e validade.
 */
function google_exchange_code(string $code, string $clientId, string $clientSecret, string $redirectUri): ?array
{
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $token = is_string($body) ? json_decode($body, true) : null;
    if ($status !== 200 || empty($token['id_token'])) {
        error_log('Google OAuth: falha na troca do code (' . $status . '): ' . $body);
        return null;
    }
    $parts = explode('.', $token['id_token']);
    $claims = json_decode(base64_decode(strtr($parts[1] ?? '', '-_', '+/')), true);
    if (
        !is_array($claims)
        || ($claims['aud'] ?? '') !== $clientId
        || !in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
        || ($claims['exp'] ?? 0) < time()
        || empty($claims['sub']) || empty($claims['email'])
    ) {
        return null;
    }
    return $claims;
}

/**
 * Encontra (ou cria) o utilizador da conta Google. Se já existir uma conta
 * com o mesmo email, fica ligada ao Google (o email foi verificado pelo Google).
 */
function google_login(array $claims): array
{
    $sub   = (string)$claims['sub'];
    $email = mb_strtolower(trim((string)$claims['email']));

    $user = q('SELECT * FROM users WHERE google_id = ?', [$sub])->fetch();
    if ($user) {
        return ['user' => $user, 'created' => false];
    }

    $user = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    if ($user) {
        q('UPDATE users SET google_id = ? WHERE id = ?', [$sub, $user['id']]);
        return ['user' => $user, 'created' => false];
    }

    $name = trim((string)($claims['name'] ?? ''));
    if ($name === '') {
        $name = strstr($email, '@', true);
    }
    q(
        'INSERT INTO users (email, google_id, display_name, lang) VALUES (?, ?, ?, ?)',
        [$email, $sub, mb_substr($name, 0, 80), current_lang()]
    );
    $id = (int)db()->lastInsertId();
    return ['user' => q('SELECT * FROM users WHERE id = ?', [$id])->fetch(), 'created' => true];
}

function create_password_reset(string $email): void
{
    $user = q('SELECT id, email FROM users WHERE email = ?', [mb_strtolower(trim($email))])->fetch();
    if (!$user) {
        return; // não revelar se o email existe
    }
    $token = bin2hex(random_bytes(32));
    q('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);
    q(
        'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL ' . RESET_TTL_MINUTES . ' MINUTE)',
        [$user['id'], hash('sha256', $token)]
    );
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $link = $scheme . '://' . $_SERVER['HTTP_HOST'] . url('reset.php?token=' . $token);
    send_mail($user['email'], t('mail.reset_subject'), t('mail.reset_body', ['link' => $link, 'min' => RESET_TTL_MINUTES]));
}

function find_password_reset(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    return q(
        'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()',
        [hash('sha256', $token)]
    )->fetch() ?: null;
}

function is_admin(?array $user = null): bool
{
    $user ??= current_user();
    return !empty($user['is_admin']);
}

function require_admin(): array
{
    $u = require_login();
    if (!is_admin($u)) {
        http_response_code(403);
        flash('error', t('error.forbidden'));
        redirect('dashboard.php');
    }
    return $u;
}

function avatar_url(?array $user): ?string
{
    if (!$user || empty($user['avatar'])) {
        return null;
    }
    return url('uploads/avatars/' . $user['avatar']);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $i = mb_substr($parts[0] ?? '', 0, 1);
    if (count($parts) > 1) {
        $i .= mb_substr(end($parts), 0, 1);
    }
    return mb_strtoupper($i);
}
