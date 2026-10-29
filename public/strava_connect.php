<?php
// Envia o utilizador para a página de autorização do Strava.
require __DIR__ . '/../src/bootstrap.php';

require_login();

if (!strava_configured()) {
    flash('error', t('strava.not_configured'));
    redirect('strava_activities.php');
}

$_SESSION['strava_state'] = bin2hex(random_bytes(16));
header('Location: ' . strava_authorize_url(strava_redirect_uri('strava_callback.php'), $_SESSION['strava_state']));
exit;
