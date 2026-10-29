<?php
// Copiar para config.php e ajustar ao servidor.
return [
    'app_name' => 'Séries',
    'base_url' => '/series',          // caminho público (sem barra final)
    'site_host' => 'www.series.corridas.eu', // endereço mostrado nas imagens de partilha
    'debug'    => false,

    'db' => [
        'host' => '127.0.0.1',
        'name' => 'series',
        'user' => 'series',
        'pass' => 'mudar-esta-password',
    ],

    // Email. Com 'debug' => true os emails só são escritos em storage/logs/mail.log
    // (para enviar mesmo em debug, pôr 'force' => true em 'smtp').
    // Sem 'smtp' => 'host' usa a função mail() do PHP.
    'mail_from' => 'no-reply@exemplo.com',   // tem de ser a conta (ou do domínio) do SMTP
    'mail_from_name' => 'Séries',
    'smtp' => [
        'host'   => '',            // ex.: mail.corridas.eu
        'port'   => 465,
        'secure' => 'ssl',         // 'ssl' (porta 465) ou 'tls' (STARTTLS, porta 587)
        'user'   => '',            // normalmente o endereço de email completo
        'pass'   => '',
    ],
    // Endereço completo do site para os links dos emails (vazio = usa o endereço do pedido)
    'site_url'  => '',          // ex.: https://series.corridas.eu

    // Login com Google (Fase 2): https://console.cloud.google.com/apis/credentials
    'google' => [
        'client_id'     => '',
        'client_secret' => '',
    ],

    // Strava: https://www.strava.com/settings/api (o client_secret é uma senha: só aqui, nunca no código)
    'strava' => [
        'client_id'     => '',
        'client_secret' => '',
    ],
];
