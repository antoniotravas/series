<?php
// Ações da rede: pedidos de conexão e ligação treinador/atleta.
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];

// voltar à página de onde veio (só caminhos internos)
$back = (string)($_POST['back'] ?? '');
if (!str_starts_with($back, config('base_url') . '/') || str_contains($back, '//')) {
    $back = url('network.php');
}

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);           // id do pedido/ligação
    $other = (int)($_POST['user_id'] ?? 0);   // id do outro utilizador
    $msg = null;
    switch ($_POST['action'] ?? '') {
        case 'connect':
            $msg = request_connection($uid, $other);
            break;
        case 'accept':
            $msg = respond_connection($uid, $id, true) ? 'net.accepted' : 'net.err';
            break;
        case 'reject':
            $msg = respond_connection($uid, $id, false) ? 'net.rejected' : 'net.err';
            break;
        case 'remove':
            remove_connection($uid, $other);
            $msg = 'net.removed';
            break;
        case 'coach_invite':      // eu (treinador) convido o outro para ser meu atleta
            $msg = $user['is_coach'] ? request_coaching($uid, $uid, $other) : 'net.err';
            break;
        case 'coach_ask':         // peço ao outro (treinador) que me treine
            $msg = request_coaching($uid, $other, $uid);
            break;
        case 'coach_accept':
            $msg = respond_coaching($uid, $id, true) ? 'coach.accepted' : 'net.err';
            break;
        case 'coach_reject':
            $msg = respond_coaching($uid, $id, false) ? 'net.rejected' : 'net.err';
            break;
        case 'coach_remove':
            remove_coaching($uid, $id);
            $msg = 'coach.removed';
            break;
    }
    if ($msg) {
        flash($msg === 'net.err' ? 'error' : 'ok', t($msg));
    }
}
header('Location: ' . $back);
exit;
