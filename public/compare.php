<?php
// Comparar dois treinos: do mesmo atleta ou de atletas diferentes (o treinador vê os seus atletas).
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];

// ?a=1&b=2, ou ids[]=1&ids[]=2 vindo da seleção na lista
$ids = array_values(array_filter(array_map('intval', (array)($_GET['ids'] ?? []))));
$aId = (int)($_GET['a'] ?? ($ids[0] ?? 0));
$bId = (int)($_GET['b'] ?? ($ids[1] ?? 0));

/** Treino com o dono, só se quem vê tiver acesso a todos os treinos do dono (o próprio ou o treinador). */
$load = function (int $id) use ($uid, $user): ?array {
    $w = $id ? find_workout_any($id) : null;
    if (!$w || !$w['sets']) {
        return null;
    }
    $ownerId = (int)$w['user_id'];
    if ($ownerId !== $uid && !is_coach_of($uid, $ownerId)) {
        return null;
    }
    $w['owner'] = $ownerId === $uid ? $user : find_user($ownerId);
    return $w['owner'] ? $w : null;
};

$a = $load($aId);
$b = $bId && $bId !== $aId ? $load($bId) : null;
if (!$a && $b) {
    [$a, $b] = [$b, null];
}
if (!$a) {
    http_response_code(404);
    flash('error', t('w.not_found'));
    redirect('workouts.php');
}

$data = ['title' => t('cmp.title'), 'active' => coach_mode($user) ? 'athletes' : 'workouts', 'a' => $a, 'b' => $b];

if (!$b) {
    // escolher o segundo treino: de quem, e com que distância
    $people = [$user];
    if ($user['is_coach']) {
        $people = array_merge($people, list_athletes($uid));
    }
    $peopleIds = array_map('intval', array_column($people, 'id'));
    $who = (int)($_GET['who'] ?? $a['user_id']);
    if (!in_array($who, $peopleIds, true)) {
        $who = (int)$a['user_id'];
    }
    $mainDist = compare_main_distance($a);
    $dist = isset($_GET['distance']) ? ((int)$_GET['distance'] ?: null) : $mainDist;
    $filters = workout_filters_from_query(['distance' => $dist]);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $result = list_workouts($who, $filters, $page);
    $result['rows'] = array_values(array_filter($result['rows'], fn($r) => (int)$r['id'] !== (int)$a['id']));
    render('compare', $data + [
        'people'    => $people,
        'who'       => $who,
        'dist'      => $dist,
        'distances' => user_distances($who),
        'result'    => $result,
        'page'      => $page,
    ]);
    exit;
}

render('compare', $data + ['groups' => compare_by_distance($a, $b)]);
