<?php
/**
 * Cria um utilizador de demonstração com ~12 semanas de treinos de meio fundo.
 * Uso (só em desenvolvimento):  php sql/seed_demo.php
 * Login: demo@series.local / demo1234
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('Só pela linha de comandos.');
}

$_SERVER['HTTP_HOST'] = 'localhost';
require __DIR__ . '/../src/bootstrap.php';

mt_srand(42);
$email = 'demo@series.local';
q('DELETE FROM users WHERE email = ?', [$email]);
q(
    'INSERT INTO users (email, password_hash, display_name, weight_kg, height_cm, club, country, main_event, lang)
     VALUES (?, ?, ?, 62.5, 176, ?, ?, ?, ?)',
    [$email, password_hash('demo1234', PASSWORD_DEFAULT), 'Rita Demo', 'Clube de Atletismo', 'PT', '1500 m', 'pt']
);
$uid = (int)db()->lastInsertId();

// [distância, reps, recuperação s, tipo, tempo base s]
$templates = [
    [[400, 10, 60, 'stand', 70.5]],
    [[500, 10, 70, 'stand', 92.0]],
    [[1000, 5, 120, 'jog', 196.0]],
    [[200, 12, 45, 'walk', 33.0]],
    [[800, 6, 90, 'jog', 152.0], [200, 4, 60, 'walk', 32.0]],
    [[300, 8, 60, 'stand', 51.0]],
    [[600, 6, 80, 'stand', 108.0]],
];
$places = ['Estádio Universitário', 'Estádio Universitário', 'Pista do Jamor', 'Pista Municipal'];

$start = new DateTimeImmutable('-12 weeks monday');
$n = 0;
for ($week = 0; $week < 12; $week++) {
    $fitness = 1 - $week * 0.0035; // melhoria gradual
    foreach ([1, 3, 5] as $dow) {
        if (mt_rand(0, 9) < 2) {
            continue; // falha um treino de vez em quando
        }
        $date = $start->modify("+$week weeks +$dow days");
        if ($date > new DateTimeImmutable('today')) {
            break 2;
        }
        $tpl = $templates[($week * 3 + $dow) % count($templates)];
        $sets = [];
        foreach ($tpl as [$dist, $reps, $rest, $type, $base]) {
            $times = [];
            for ($r = 0; $r < $reps; $r++) {
                $fatigue = $r / $reps * 0.012 * $base;
                $noise = (mt_rand(-100, 100) / 100) * 0.008 * $base;
                $kick = $r === $reps - 1 ? -0.012 * $base : 0;
                $times[] = round($base * $fitness + $fatigue + $noise + $kick, 1);
            }
            $sets[] = ['distance_m' => $dist, 'rest_seconds' => $rest, 'rest_type' => $type, 'target_seconds' => $base, 'reps' => $times];
        }
        save_workout($uid, [
            'title'        => null,
            'workout_date' => $date->format('Y-m-d'),
            'start_time'   => mt_rand(0, 1) ? '18:30' : '07:15',
            'location'     => $places[mt_rand(0, count($places) - 1)],
            'venue_type'   => 'outdoor',
            'weather'      => null,
            'temperature_c'=> mt_rand(12, 26),
            'rpe'          => mt_rand(6, 9),
            'notes'        => null,
        ], $sets);
        $n++;
    }
}
echo "Utilizador demo criado ($email / demo1234) com $n treinos.\n";
