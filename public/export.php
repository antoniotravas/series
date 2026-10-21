<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$rows = q(
    'SELECT w.workout_date, w.start_time, w.title, w.location, w.venue_type, w.rpe,
            s.position AS set_no, s.distance_m, s.rest_seconds, s.rest_type, s.target_seconds,
            r.rep_number, r.time_seconds, r.hr_avg
       FROM workouts w
       JOIN workout_sets s ON s.workout_id = w.id
       JOIN workout_reps r ON r.set_id = s.id
      WHERE w.user_id = ?
   ORDER BY w.workout_date, w.id, s.position, r.rep_number',
    [$user['id']]
);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="series-' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM para o Excel abrir em UTF-8
fputcsv($out, ['date', 'time', 'title', 'location', 'venue', 'rpe', 'set', 'distance_m', 'rest_s', 'rest_type', 'target_s', 'rep', 'time_s', 'hr'], ';');
foreach ($rows as $r) {
    // evitar injeção de fórmulas em folhas de cálculo
    $r = array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $r);
    fputcsv($out, array_values($r), ';');
}
fclose($out);
