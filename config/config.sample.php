<?php
// Copiar para config.php e ajustar ao servidor.
return [
    'app_name' => 'Séries',
    'base_url' => '/series',          // caminho público (sem barra final)
    'debug'    => false,

    'db' => [
        'host' => '127.0.0.1',
        'name' => 'series',
        'user' => 'series',
        'pass' => 'mudar-esta-password',
    ],

    // Email: se 'smtp' estiver vazio, os emails são escritos em storage/logs/mail.log
    'mail_from' => 'no-reply@exemplo.com',

    // Login com Google (Fase 2): https://console.cloud.google.com/apis/credentials
    'google' => [
        'client_id'     => '',
        'client_secret' => '',
    ],
];
