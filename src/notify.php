<?php
declare(strict_types=1);

// Notificações por email: pedidos de conexão/treino e treinos novos dos atletas.
// Cada email vai na língua de quem o recebe e pode ser desligado no perfil.

/** Endereço completo (para links nos emails). Usa 'site_url' da config, se existir. */
function absolute_url(string $path = ''): string
{
    $base = rtrim((string)config('site_url', ''), '/');
    if ($base === '') {
        $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . config('base_url');
    }
    return $base . '/' . ltrim($path, '/');
}

/**
 * Envia a notificação $key ao utilizador $to, se ele não a tiver desligado.
 * $pref: 'notify_requests' ou 'notify_workouts'. Falhas no envio não interrompem a ação.
 */
function notify_user(array $to, string $pref, string $key, array $vars, string $path): void
{
    if (empty($to[$pref]) || empty($to['active'])) {
        return;
    }
    in_lang((string)$to['lang'], function () use ($to, $key, $vars, $path) {
        $vars += ['app' => config('app_name')];
        $body = t("mail.$key.body", $vars) . "\n" . absolute_url($path)
              . "\n\n—\n" . t('mail.footer', $vars + ['link' => absolute_url('profile.php?edit=1#notify')]);
        try {
            send_mail($to['email'], t("mail.$key.subject", $vars), $body);
        } catch (Throwable $e) {
            error_log('notify: ' . $e->getMessage());
        }
    });
}

function notify_connection_request(int $fromId, int $toId): void
{
    $from = find_user($fromId);
    $to = find_user($toId);
    if ($from && $to) {
        notify_user($to, 'notify_requests', 'connect', ['name' => $from['display_name']], 'user.php?id=' . $fromId);
    }
}

/** Pedido treinador/atleta: avisa o lado que não fez o pedido. */
function notify_coaching_request(int $by, int $coachId, int $athleteId): void
{
    $from = find_user($by);
    $to = find_user($by === $coachId ? $athleteId : $coachId);
    if ($from && $to) {
        $key = $by === $coachId ? 'coach_invite' : 'coach_ask';
        notify_user($to, 'notify_requests', $key, ['name' => $from['display_name']], 'network.php');
    }
}

/**
 * Avisa os treinadores de que o atleta registou treinos. Com um só treino o link
 * abre esse treino; com vários (importação) abre a lista de treinos do atleta.
 */
function notify_new_workouts(int $athleteId, array $workoutIds): void
{
    $athlete = find_user($athleteId);
    if (!$athlete || !$workoutIds) {
        return;
    }
    $n = count($workoutIds);
    $vars = ['name' => $athlete['display_name'], 'n' => $n];
    $w = $n === 1 ? find_workout_any((int)$workoutIds[0]) : null;
    $path = $w ? 'workout.php?id=' . (int)$w['id'] : 'workouts.php?u=' . $athleteId;
    foreach (list_coaches($athleteId) as $coach) {
        if ($w) {
            // a data e o resumo ficam na língua do treinador
            $summary = in_lang((string)$coach['lang'], fn() => fmt_date($w['workout_date'], true)
                . ($w['sets'] ? ' · ' . sets_summary($w['sets']) : ''));
            notify_user($coach, 'notify_workouts', 'workout', $vars + ['summary' => $summary], $path);
        } else {
            notify_user($coach, 'notify_workouts', 'workouts', $vars, $path);
        }
    }
}
