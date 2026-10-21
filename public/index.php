<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

// Série de exemplo para a demonstração na página inicial
$demo = [
    'distance_m' => 500, 'rest_seconds' => 70, 'rest_type' => 'stand', 'target_seconds' => 92.0,
    'reps' => [91.8, 92.4, 91.6, 92.9, 92.1, 93.4, 92.6, 93.8, 92.2, 90.4],
];
$demo['stats'] = set_stats($demo['reps']);

render('home', ['demo' => $demo]);
