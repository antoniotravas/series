<?php
// Login com Google: previsto para a Fase 2.
// Necessita de google.client_id / google.client_secret em config/config.php.
require __DIR__ . '/../src/bootstrap.php';

flash('error', t('auth.google_soon'));
redirect('login.php');
