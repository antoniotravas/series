<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$id = (int)($_GET['id'] ?? 0);
$existing = $id ? find_workout_any($id) : null;
if ($id && (!$existing || !can_edit_workout($uid, $existing))) {
    flash('error', t('w.not_found'));
    redirect('workouts.php');
}

// modo treinador: primeiro escolhe-se o atleta
if (coach_mode($user) && !$existing && !isset($_GET['for'])) {
    $athletes = list_athletes($uid);
    if (count($athletes) === 1) {
        redirect('workout_edit.php?for=' . (int)$athletes[0]['id']);
    }
    if (!$athletes) {
        flash('error', t('athletes.empty.title'));
        redirect('athletes.php');
    }
    render('pick_athlete', ['title' => t('w.new.title'), 'active' => 'workouts', 'athletes' => $athletes]);
    exit;
}

// dono do treino: o próprio, ou o atleta para quem o treinador está a registar (?for=)
$ownerId = $existing ? (int)$existing['user_id'] : (int)($_GET['for'] ?? $uid);
if ($ownerId !== $uid && !$existing && !is_coach_of($uid, $ownerId)) {
    flash('error', t('w.not_found'));
    redirect('athletes.php');
}
$athlete = $ownerId !== $uid ? find_user($ownerId) : null;
if ($ownerId !== $uid && !$athlete) {
    redirect('athletes.php');
}

$errors = [];
$form = null;

if (is_post()) {
    csrf_check();
    $res = workout_from_input($_POST);
    if (isset($res['errors'])) {
        $errors = $res['errors'];
        $form = $_POST; // repor o formulário tal como foi enviado
    } else {
        $newId = save_workout($ownerId, $res['data'], $res['sets'], $existing ? $id : null, $ownerId !== $uid ? $uid : null);
        if (!$existing && $ownerId === $uid) {
            notify_new_workouts($uid, [$newId]);   // treino novo do próprio atleta: avisar os treinadores
        }
        flash('ok', t('w.saved'));
        redirect('workout.php?id=' . $newId);
    }
}

// Valores iniciais do formulário
if ($form === null) {
    $copyFrom = !$existing && isset($_GET['copy']) ? find_workout($ownerId, (int)$_GET['copy']) : null;
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
            'location'     => user_locations($ownerId)[0] ?? '',
            'visibility'   => last_visibility($ownerId),
            'sets'         => [['distance_m' => '', 'rest' => '', 'rest_type' => 'stand', 'target' => '', 'reps' => array_fill(0, 6, '')]],
        ];
    }
}

render('workout_form', [
    'title'     => $existing ? t('w.edit.title') : t('w.new.title'),
    'active'    => $athlete ? 'athletes' : 'workouts',
    'existing'  => $existing,
    'form'      => $form,
    'errors'    => $errors,
    'locations' => user_locations($ownerId),
    'athlete'   => $athlete,
]);
