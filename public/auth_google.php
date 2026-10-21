<?php
// Login com Google (OAuth 2.0 / OpenID Connect, fluxo "authorization code").
// Necessita de google.client_id / google.client_secret em config/config.php.
require __DIR__ . '/../src/bootstrap.php';

$clientId     = (string)config('google.client_id', '');
$clientSecret = (string)config('google.client_secret', '');
if ($clientId === '' || $clientSecret === '') {
    flash('error', t('auth.google_soon'));
    redirect('login.php');
}

$scheme      = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$redirectUri = $scheme . '://' . $_SERVER['HTTP_HOST'] . url('auth_google.php');

// 1.º passo: enviar o utilizador para o Google
if (!isset($_GET['code']) && !isset($_GET['error'])) {
    $_SESSION['google_state'] = bin2hex(random_bytes(16));
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $_SESSION['google_state'],
        'prompt'        => 'select_account',
    ]));
    exit;
}

// 2.º passo: o Google devolve o utilizador com ?code=...&state=...
$expected = $_SESSION['google_state'] ?? '';
unset($_SESSION['google_state']);
$state = $_GET['state'] ?? '';

if (isset($_GET['error'])) {
    // ex.: o utilizador carregou em "Cancelar"
    redirect('login.php');
}
if ($expected === '' || !is_string($state) || !hash_equals($expected, $state) || !is_string($_GET['code'])) {
    flash('error', t('auth.google_error'));
    redirect('login.php');
}

$claims = google_exchange_code($_GET['code'], $clientId, $clientSecret, $redirectUri);
if (!$claims) {
    flash('error', t('auth.google_error'));
    redirect('login.php');
}
if (empty($claims['email_verified'])) {
    flash('error', t('auth.google_unverified'));
    redirect('login.php');
}

$res = google_login($claims);
login_user($res['user']);
if ($res['created']) {
    flash('ok', t('auth.welcome', ['name' => $res['user']['display_name']]));
}
redirect_after_login();
