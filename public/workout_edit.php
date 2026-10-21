<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$id = (int)($_GET['id'] ?? 0);
$existing = $id ? find_workout($uid, $id) : null;
if ($id && !$existing) {
    flash('error', t('w.not_found'));
    redirect('workouts.php');
}

$errors = [];
$form = null;

if (is_post()) {
    csrf_check();
    $res = workout_from_input($_POST);
    $gpxName = null;
    if (!isset($res['errors'])) {
        try {
            $gpxName = store_gpx($_FILES['gpx'] ?? [], $uid);
        } catch (RuntimeException $ex) {
            $res['errors']['gpx'] = $ex->getMessage();
        }
    }
    if (isset($res['errors'])) {
        $errors = $res['errors'];
        $form = $_POST; // repor o formulário tal como foi enviado
    } else {
        $data = $res['data'];
        if ($gpxName) {
            $data['gpx_file'] = $gpxName;
            if ($existing && $existing['gpx_file']) {
                @unlink(ROOT . '/storage/gpx/' . $existing['gpx_file']);
            }
        }
        $newId = save_workout($uid, $data, $res['sets'], $existing ? $id : null);
        flash('ok', t('w.saved'));
        redirect('workout.php?id=' . $newId);
    }
}

// Valores iniciais do formulário
if ($form === null) {
    $copyFrom = !$existing && isset($_GET['copy']) ? find_workout($uid, (int)$_GET['copy']) : null;
    $src = $existing ?? $copyFrom;
    if ($src) {
        $form = $src;
        $form['start_time'] = $src['start_time'] ? substr($src['start_time'], 0, 5) : '';
        $form['sets'] = array_map(fn($s) => [
            'distance_m' => $s['distance_m'],
            'rest'       => $s['rest_seconds'] !== null ? fmt_time((float)$s['rest_seconds'], 0) : '',
            'rest_type'  => $s['rest_type'],
            'target'     => $s['target_seconds'] !== null ? fmt_time((float)$s['target_seconds']) : '',
            // numa cópia mantém-se o nº de repetições mas os tempos ficam em branco
            'reps'       => $copyFrom ? array_fill(0, count($s['reps']), '') : array_map(fn($r) => fmt_time($r, 2), $s['reps']),
            'hr'         => $copyFrom ? [] : $s['hr'],
            'hr_max'     => $copyFrom ? [] : $s['hr_max'],
        ], $src['sets']);
        if ($copyFrom) {
            $form['workout_date'] = date('Y-m-d');
            $form['start_time'] = '';
            $form['notes'] = '';
            $form['rpe'] = '';
            $form['weather'] = '';
            $form['temperature_c'] = '';
            $form['lat'] = $form['lon'] = '';   // a cópia pode ser feita noutro local
        }
    } else {
        $form = [
            'workout_date' => date('Y-m-d'),
            'start_time'   => '',
            'venue_type'   => 'outdoor',
            'location'     => user_locations($uid)[0] ?? '',
            'sets'         => [['distance_m' => '', 'rest' => '', 'rest_type' => 'stand', 'target' => '', 'reps' => array_fill(0, 6, '')]],
        ];
    }
}

render('workout_form', [
    'title'     => $existing ? t('w.edit.title') : t('w.new.title'),
    'active'    => 'workouts',
    'existing'  => $existing,
    'form'      => $form,
    'errors'    => $errors,
    'locations' => user_locations($uid),
]);
