<?php
declare(strict_types=1);

const VENUE_TYPES = ['outdoor', 'indoor', 'road', 'trail', 'other'];
const REST_TYPES  = ['stand', 'walk', 'jog'];
const MAX_SETS    = 20;
const MAX_REPS    = 60;
const GPX_MAX_BYTES = 10 * 1024 * 1024;

/**
 * Valida e normaliza o POST do formulário de treino.
 * Devolve ['data' => ..., 'sets' => ...] ou ['errors' => ...].
 */
function workout_from_input(array $in): array
{
    $errors = [];
    $date = $in['workout_date'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        $errors['workout_date'] = t('error.date_invalid');
    }
    $time = trim($in['start_time'] ?? '');
    if ($time !== '' && !preg_match('/^\d{2}:\d{2}$/', $time)) {
        $errors['start_time'] = t('error.time_invalid');
    }
    $venue = in_array($in['venue_type'] ?? '', VENUE_TYPES, true) ? $in['venue_type'] : 'outdoor';
    $rpe = ($in['rpe'] ?? '') === '' ? null : max(1, min(10, (int)$in['rpe']));
    $temp = ($in['temperature_c'] ?? '') === '' ? null : max(-30, min(50, (int)$in['temperature_c']));

    $sets = [];
    foreach (array_slice(array_values($in['sets'] ?? []), 0, MAX_SETS) as $i => $s) {
        $dist = (int)($s['distance_m'] ?? 0);
        $reps = [];
        $hr = [];   // FC média de cada repetição, alinhada com $reps
        $hrIn = array_values((array)($s['hr'] ?? []));
        foreach (array_slice(array_values((array)($s['reps'] ?? [])), 0, MAX_REPS) as $k => $raw) {
            if (trim((string)$raw) === '') {
                continue;
            }
            $sec = parse_time((string)$raw);
            if ($sec === null) {
                $errors["sets.$i"] = t('error.rep_time_invalid', ['v' => e((string)$raw)]);
                continue;
            }
            $reps[] = $sec;
            $bpm = (int)($hrIn[$k] ?? 0);
            $hr[] = $bpm >= 30 && $bpm <= 250 ? $bpm : null;
        }
        if ($dist === 0 && !$reps) {
            continue; // bloco vazio, ignorar
        }
        if ($dist < 50 || $dist > 42195) {
            $errors["sets.$i"] = t('error.distance_invalid');
        }
        if (!$reps && !isset($errors["sets.$i"])) {
            $errors["sets.$i"] = t('error.reps_required');
        }
        $rest = trim((string)($s['rest'] ?? ''));
        $restSec = $rest === '' ? null : parse_time($rest);
        $target = trim((string)($s['target'] ?? ''));
        $sets[] = [
            'distance_m'     => $dist,
            'rest_seconds'   => $restSec === null ? null : (int)round($restSec),
            'rest_type'      => in_array($s['rest_type'] ?? '', REST_TYPES, true) ? $s['rest_type'] : 'stand',
            'target_seconds' => $target === '' ? null : parse_time($target),
            'reps'           => $reps,
            'hr'             => $hr,
        ];
    }
    if (!$sets && !isset($errors['sets'])) {
        $errors['sets'] = t('error.sets_required');
    }
    if ($errors) {
        return ['errors' => $errors];
    }

    return [
        'data' => [
            'title'         => mb_substr(trim($in['title'] ?? ''), 0, 120) ?: null,
            'workout_date'  => $date,
            'start_time'    => $time ?: null,
            'location'      => mb_substr(trim($in['location'] ?? ''), 0, 120) ?: null,
            'venue_type'    => $venue,
            'weather'       => mb_substr(trim($in['weather'] ?? ''), 0, 60) ?: null,
            'temperature_c' => $temp,
            'rpe'           => $rpe,
            'notes'         => trim($in['notes'] ?? '') ?: null,
        ],
        'sets' => $sets,
    ];
}

function save_workout(int $userId, array $data, array $sets, ?int $workoutId = null): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $cols = array_keys($data);
        if ($workoutId) {
            $assign = implode(', ', array_map(fn($c) => "$c = ?", $cols));
            q("UPDATE workouts SET $assign WHERE id = ? AND user_id = ?", [...array_values($data), $workoutId, $userId]);
            q('DELETE FROM workout_sets WHERE workout_id = ?', [$workoutId]);
        } else {
            $ph = implode(', ', array_fill(0, count($cols) + 1, '?'));
            q('INSERT INTO workouts (user_id, ' . implode(', ', $cols) . ") VALUES ($ph)", [$userId, ...array_values($data)]);
            $workoutId = (int)$pdo->lastInsertId();
        }
        $insSet = $pdo->prepare('INSERT INTO workout_sets (workout_id, position, distance_m, rest_seconds, rest_type, target_seconds) VALUES (?, ?, ?, ?, ?, ?)');
        $insRep = $pdo->prepare('INSERT INTO workout_reps (set_id, rep_number, time_seconds, hr_avg) VALUES (?, ?, ?, ?)');
        foreach ($sets as $pos => $s) {
            $insSet->execute([$workoutId, $pos + 1, $s['distance_m'], $s['rest_seconds'], $s['rest_type'], $s['target_seconds']]);
            $setId = (int)$pdo->lastInsertId();
            foreach ($s['reps'] as $n => $sec) {
                $insRep->execute([$setId, $n + 1, $sec, $s['hr'][$n] ?? null]);
            }
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return $workoutId;
}

function find_workout(int $userId, int $id): ?array
{
    $w = q('SELECT * FROM workouts WHERE id = ? AND user_id = ?', [$id, $userId])->fetch();
    if (!$w) {
        return null;
    }
    $sets = q('SELECT * FROM workout_sets WHERE workout_id = ? ORDER BY position', [$id])->fetchAll();
    $setIds = array_column($sets, 'id');
    $reps = $hr = [];
    if ($setIds) {
        $ph = implode(',', array_fill(0, count($setIds), '?'));
        foreach (q("SELECT * FROM workout_reps WHERE set_id IN ($ph) ORDER BY rep_number", $setIds) as $r) {
            $reps[$r['set_id']][] = (float)$r['time_seconds'];
            $hr[$r['set_id']][] = $r['hr_avg'] === null ? null : (int)$r['hr_avg'];
        }
    }
    foreach ($sets as &$s) {
        $s['reps'] = $reps[$s['id']] ?? [];
        $s['hr'] = $hr[$s['id']] ?? [];
        $s['stats'] = set_stats($s['reps']);
    }
    unset($s);
    $w['sets'] = $sets;
    return $w;
}

function set_stats(array $reps): array
{
    $n = count($reps);
    if (!$n) {
        return ['n' => 0, 'avg' => null, 'best' => null, 'worst' => null, 'sd' => null, 'total' => 0];
    }
    $avg = array_sum($reps) / $n;
    $var = array_sum(array_map(fn($r) => ($r - $avg) ** 2, $reps)) / $n;
    return [
        'n'     => $n,
        'avg'   => $avg,
        'best'  => min($reps),
        'worst' => max($reps),
        'sd'    => sqrt($var),
        'total' => array_sum($reps),
    ];
}

function delete_workout(int $userId, int $id): void
{
    $w = q('SELECT gpx_file FROM workouts WHERE id = ? AND user_id = ?', [$id, $userId])->fetch();
    if (!$w) {
        return;
    }
    q('DELETE FROM workouts WHERE id = ? AND user_id = ?', [$id, $userId]);
    if ($w['gpx_file']) {
        @unlink(ROOT . '/storage/gpx/' . $w['gpx_file']);
    }
}

/** Ativa/desativa vários treinos do utilizador. Devolve quantos mudaram. */
function set_workouts_active(int $userId, array $ids, bool $active): int
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) {
        return 0;
    }
    $ph = implode(',', array_fill(0, count($ids), '?'));
    return q("UPDATE workouts SET active = ? WHERE user_id = ? AND id IN ($ph)", [(int)$active, $userId, ...$ids])->rowCount();
}

/** Guarda um ficheiro GPX enviado. Devolve o nome guardado ou lança mensagem de erro. */
function store_gpx(array $file, int $userId): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > GPX_MAX_BYTES) {
        throw new RuntimeException(t('error.gpx_upload'));
    }
    $head = file_get_contents($file['tmp_name'], false, null, 0, 2048);
    if ($head === false || stripos($head, '<gpx') === false) {
        throw new RuntimeException(t('error.gpx_invalid'));
    }
    $name = $userId . '_' . bin2hex(random_bytes(8)) . '.gpx';
    if (!move_uploaded_file($file['tmp_name'], ROOT . '/storage/gpx/' . $name)) {
        throw new RuntimeException(t('error.gpx_upload'));
    }
    return $name;
}

/** Texto resumo "10 × 500 m + 4 × 200 m" */
function sets_summary(array $sets): string
{
    return implode(' + ', array_map(
        fn($s) => count($s['reps']) . ' × ' . fmt_distance((int)$s['distance_m']),
        $sets
    ));
}

// ---------- Listagem com filtros ----------

function workout_filters_from_query(array $qs): array
{
    return [
        'distance' => (int)($qs['distance'] ?? 0) ?: null,
        'year'     => (int)($qs['year'] ?? 0) ?: null,
        'month'    => (int)($qs['month'] ?? 0) ?: null,
        'location' => trim((string)($qs['location'] ?? '')) ?: null,
        'q'        => trim((string)($qs['q'] ?? '')) ?: null,
        'status'   => in_array($qs['status'] ?? '', ['active', 'inactive'], true) ? $qs['status'] : null,
    ];
}

function list_workouts(int $userId, array $f, int $page = 1, int $perPage = 20): array
{
    $where = ['w.user_id = ?'];
    $params = [$userId];
    if ($f['distance']) {
        $where[] = 'EXISTS (SELECT 1 FROM workout_sets s WHERE s.workout_id = w.id AND s.distance_m = ?)';
        $params[] = $f['distance'];
    }
    if ($f['year']) {
        $where[] = 'YEAR(w.workout_date) = ?';
        $params[] = $f['year'];
    }
    if ($f['month']) {
        $where[] = 'MONTH(w.workout_date) = ?';
        $params[] = $f['month'];
    }
    if ($f['location']) {
        $where[] = 'w.location = ?';
        $params[] = $f['location'];
    }
    if ($f['q']) {
        $where[] = '(w.title LIKE ? OR w.notes LIKE ? OR w.location LIKE ?)';
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        array_push($params, $like, $like, $like);
    }
    if (!empty($f['status'])) {
        $where[] = 'w.active = ' . ($f['status'] === 'active' ? 1 : 0);
    }
    $w = implode(' AND ', $where);
    $total = (int)q("SELECT COUNT(*) FROM workouts w WHERE $w", $params)->fetchColumn();
    $offset = max(0, ($page - 1) * $perPage);
    $rows = q(
        "SELECT w.* FROM workouts w WHERE $w ORDER BY w.workout_date DESC, w.start_time DESC, w.id DESC LIMIT $perPage OFFSET $offset",
        $params
    )->fetchAll();

    // carregar séries em lote
    $ids = array_column($rows, 'id');
    $byWorkout = [];
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sets = q(
            "SELECT s.workout_id, s.id, s.distance_m, s.position,
                    COUNT(r.id) AS n, AVG(r.time_seconds) AS avg_t, MIN(r.time_seconds) AS best_t
               FROM workout_sets s LEFT JOIN workout_reps r ON r.set_id = s.id
              WHERE s.workout_id IN ($ph)
           GROUP BY s.id ORDER BY s.workout_id, s.position",
            $ids
        )->fetchAll();
        foreach ($sets as $s) {
            $s['reps'] = array_fill(0, (int)$s['n'], 0);
            $byWorkout[$s['workout_id']][] = $s;
        }
    }
    foreach ($rows as &$r) {
        $r['sets'] = $byWorkout[$r['id']] ?? [];
        $r['volume_m'] = array_sum(array_map(fn($s) => $s['distance_m'] * $s['n'], $r['sets']));
    }
    unset($r);
    return ['rows' => $rows, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
}

function user_distances(int $userId): array
{
    return q(
        'SELECT DISTINCT s.distance_m FROM workout_sets s JOIN workouts w ON w.id = s.workout_id
          WHERE w.user_id = ? ORDER BY s.distance_m',
        [$userId]
    )->fetchAll(PDO::FETCH_COLUMN);
}

function user_years(int $userId): array
{
    return q('SELECT DISTINCT YEAR(workout_date) FROM workouts WHERE user_id = ? ORDER BY 1 DESC', [$userId])
        ->fetchAll(PDO::FETCH_COLUMN);
}

function user_locations(int $userId): array
{
    return q(
        'SELECT location FROM workouts WHERE user_id = ? AND location IS NOT NULL
       GROUP BY location ORDER BY COUNT(*) DESC, location LIMIT 50',
        [$userId]
    )->fetchAll(PDO::FETCH_COLUMN);
}

// ---------- Estatísticas (dashboard) ----------
// Os treinos inativos (active = 0) não contam.

function stats_totals(int $userId): array
{
    $row = q(
        'SELECT COUNT(DISTINCT w.id) AS workouts, COUNT(r.id) AS reps,
                COALESCE(SUM(s.distance_m), 0) AS meters,
                COALESCE(SUM(CASE WHEN w.workout_date >= CURDATE() - INTERVAL 30 DAY THEN s.distance_m END), 0) AS meters_30d,
                COUNT(DISTINCT CASE WHEN w.workout_date >= CURDATE() - INTERVAL 30 DAY THEN w.id END) AS workouts_30d
           FROM workouts w
      LEFT JOIN workout_sets s ON s.workout_id = w.id
      LEFT JOIN workout_reps r ON r.set_id = s.id
          WHERE w.user_id = ? AND w.active = 1',
        [$userId]
    )->fetch();
    // s.distance_m é somado uma vez por repetição graças ao JOIN com reps
    return array_map('floatval', $row);
}

/** Melhor repetição por distância */
function stats_personal_bests(int $userId, int $limit = 8): array
{
    return q(
        'SELECT x.distance_m, x.best, x.times,
                (SELECT w2.id FROM workouts w2
                   JOIN workout_sets s2 ON s2.workout_id = w2.id
                   JOIN workout_reps r2 ON r2.set_id = s2.id
                  WHERE w2.user_id = ? AND w2.active = 1 AND s2.distance_m = x.distance_m AND r2.time_seconds = x.best
               ORDER BY w2.workout_date LIMIT 1) AS workout_id,
                (SELECT w3.workout_date FROM workouts w3
                   JOIN workout_sets s3 ON s3.workout_id = w3.id
                   JOIN workout_reps r3 ON r3.set_id = s3.id
                  WHERE w3.user_id = ? AND w3.active = 1 AND s3.distance_m = x.distance_m AND r3.time_seconds = x.best
               ORDER BY w3.workout_date LIMIT 1) AS workout_date
           FROM (SELECT s.distance_m, MIN(r.time_seconds) AS best, COUNT(r.id) AS times
                   FROM workouts w
                   JOIN workout_sets s ON s.workout_id = w.id
                   JOIN workout_reps r ON r.set_id = s.id
                  WHERE w.user_id = ? AND w.active = 1
               GROUP BY s.distance_m) x
       ORDER BY x.times DESC, x.distance_m
          LIMIT ' . (int)$limit,
        [$userId, $userId, $userId]
    )->fetchAll();
}

/** Volume (metros em repetições) por semana, últimas N semanas, incluindo semanas a zero */
function stats_weekly_volume(int $userId, int $weeks = 12): array
{
    $start = new DateTimeImmutable('monday this week');
    $start = $start->modify('-' . ($weeks - 1) . ' weeks');
    $rows = q(
        "SELECT DATE_SUB(w.workout_date, INTERVAL WEEKDAY(w.workout_date) DAY) AS wk,
                SUM(s.distance_m) AS meters, COUNT(DISTINCT w.id) AS workouts
           FROM workouts w
           JOIN workout_sets s ON s.workout_id = w.id
           JOIN workout_reps r ON r.set_id = s.id
          WHERE w.user_id = ? AND w.active = 1 AND w.workout_date >= ?
       GROUP BY wk",
        [$userId, $start->format('Y-m-d')]
    )->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[$r['wk']] = $r;
    }
    $out = [];
    for ($i = 0; $i < $weeks; $i++) {
        $d = $start->modify("+$i weeks")->format('Y-m-d');
        $out[] = [
            'week'     => $d,
            'meters'   => (float)($map[$d]['meters'] ?? 0),
            'workouts' => (int)($map[$d]['workouts'] ?? 0),
        ];
    }
    return $out;
}

/** Evolução de uma distância: média e melhor rep por treino */
function stats_progression(int $userId, int $distance, int $limit = 30): array
{
    $rows = q(
        'SELECT w.id, w.workout_date, AVG(r.time_seconds) AS avg_t, MIN(r.time_seconds) AS best_t, COUNT(r.id) AS n
           FROM workouts w
           JOIN workout_sets s ON s.workout_id = w.id
           JOIN workout_reps r ON r.set_id = s.id
          WHERE w.user_id = ? AND w.active = 1 AND s.distance_m = ?
       GROUP BY w.id, w.workout_date
       ORDER BY w.workout_date DESC
          LIMIT ' . (int)$limit,
        [$userId, $distance]
    )->fetchAll();
    return array_reverse($rows);
}

function stats_top_locations(int $userId, int $limit = 5): array
{
    return q(
        'SELECT location, COUNT(*) AS n FROM workouts WHERE user_id = ? AND active = 1 AND location IS NOT NULL
       GROUP BY location ORDER BY n DESC LIMIT ' . (int)$limit,
        [$userId]
    )->fetchAll();
}

/** Dias consecutivos/semana: semanas seguidas com pelo menos um treino */
function stats_week_streak(int $userId): int
{
    $weeks = q(
        'SELECT DISTINCT DATE_SUB(workout_date, INTERVAL WEEKDAY(workout_date) DAY) AS wk
           FROM workouts WHERE user_id = ? AND active = 1 ORDER BY wk DESC LIMIT 104',
        [$userId]
    )->fetchAll(PDO::FETCH_COLUMN);
    $streak = 0;
    $expect = new DateTimeImmutable('monday this week');
    foreach ($weeks as $wk) {
        if ($wk === $expect->format('Y-m-d')) {
            $streak++;
            $expect = $expect->modify('-1 week');
        } elseif ($streak === 0 && $wk === $expect->modify('-1 week')->format('Y-m-d')) {
            // semana actual ainda sem treino: contar a partir da anterior
            $streak = 1;
            $expect = $expect->modify('-2 weeks');
        } else {
            break;
        }
    }
    return $streak;
}
