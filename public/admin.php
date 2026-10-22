<?php
// Administração: utilizadores, contas de treinador e administradores.
require __DIR__ . '/../src/bootstrap.php';

$admin = require_admin();
$aid = (int)$admin['id'];

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $target = $id && $id !== $aid ? find_user($id) : null;   // nunca sobre a própria conta
    $action = $_POST['action'] ?? '';
    if (!$target) {
        flash('error', t('admin.err_self'));
    } elseif ($action === 'coach' || $action === 'admin') {
        $col = $action === 'coach' ? 'is_coach' : 'is_admin';
        q("UPDATE users SET $col = 1 - $col WHERE id = ?", [$id]);
        flash('ok', t('admin.updated', ['name' => $target['display_name']]));
    } elseif ($action === 'delete') {
        if ($target['avatar']) {
            @unlink(ROOT . '/public/uploads/avatars/' . basename($target['avatar']));
        }
        q('DELETE FROM users WHERE id = ?', [$id]);
        flash('ok', t('admin.deleted', ['name' => $target['display_name']]));
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
