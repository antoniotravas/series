<?php
// Administração: utilizadores, contas de treinador e administradores.
require __DIR__ . '/../src/bootstrap.php';

$admin = require_admin();
$aid = (int)$admin['id'];

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    // nunca sobre a própria conta; inclui contas desativadas (find_user só devolve ativas)
    $target = $id && $id !== $aid ? (q('SELECT * FROM users WHERE id = ?', [$id])->fetch() ?: null) : null;
    $action = $_POST['action'] ?? '';
    if (!$target) {
        flash('error', t('admin.err_self'));
    } elseif (in_array($action, ['coach', 'admin', 'active', 'strava'], true)) {
        // o administrador não apaga contas: só desativa (os dados ficam guardados)
        $col = ['coach' => 'is_coach', 'admin' => 'is_admin', 'active' => 'active', 'strava' => 'strava_keep'][$action];
        q("UPDATE users SET $col = 1 - $col WHERE id = ?", [$id]);
        $msg = $action === 'active' ? ($target['active'] ? 'admin.disabled' : 'admin.enabled') : 'admin.updated';
        flash('ok', t($msg, ['name' => $target['display_name']]));
    }
    $back = (string)($_POST['back'] ?? '');
    if (!str_starts_with($back, config('base_url') . '/admin.php')) {
        $back = url('admin.php');
    }
    header('Location: ' . $back);
    exit;
}

$term = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$where = '1';
$params = [];
if ($term !== '') {
    $where = '(u.display_name LIKE ? OR u.email LIKE ? OR u.club LIKE ?)';
    $like = '%' . addcslashes($term, '%_\\') . '%';
    $params = [$like, $like, $like];
}
$total = (int)q("SELECT COUNT(*) FROM users u WHERE $where", $params)->fetchColumn();
$offset = ($page - 1) * $perPage;
$users = q(
    "SELECT u.*, (SELECT COUNT(*) FROM workouts w WHERE w.user_id = u.id) AS workouts,
            (SELECT MAX(w.workout_date) FROM workouts w WHERE w.user_id = u.id) AS last_workout
       FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
)->fetchAll();

$stats = q(
    "SELECT (SELECT COUNT(*) FROM users) AS users,
            (SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY) AS users_30d,
            (SELECT COUNT(*) FROM users WHERE active = 0) AS disabled,
            (SELECT COUNT(*) FROM users WHERE is_coach = 1) AS coaches,
            (SELECT COUNT(*) FROM workouts) AS workouts,
            (SELECT COUNT(*) FROM workouts WHERE workout_date >= CURDATE() - INTERVAL 7 DAY) AS workouts_7d,
            (SELECT COUNT(*) FROM connections WHERE status = 'accepted') AS connections"
)->fetch();

render('admin', [
    'title'  => t('admin.title'),
    'active' => 'admin',
    'stats'  => $stats,
    'users'  => $users,
    'term'   => $term,
    'page'   => $page,
    'pages'  => (int)ceil($total / $perPage),
    'total'  => $total,
]);
