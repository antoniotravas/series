<?php
declare(strict_types=1);

// Rede de utilizadores: conexões, treinadores e quem pode ver cada treino.

const VISIBILITIES = ['private', 'connections', 'public'];

/** Utilizador ativo (as contas desativadas pelo administrador não aparecem na rede). */
function find_user(int $id): ?array
{
    return q('SELECT * FROM users WHERE id = ? AND active = 1', [$id])->fetch() ?: null;
}

// ---------- Conexões ----------

/** Conexão (pendente ou aceite) entre dois utilizadores, em qualquer sentido. */
function connection_between(int $a, int $b): ?array
{
    return q(
        'SELECT * FROM connections WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)',
        [$a, $b, $b, $a]
    )->fetch() ?: null;
}

function are_connected(int $a, int $b): bool
{
    $c = connection_between($a, $b);
    return $c && $c['status'] === 'accepted';
}

/** Pede conexão. Se o outro já tinha pedido, a conexão fica aceite. Devolve a chave da mensagem. */
function request_connection(int $from, int $to): string
{
    if ($from === $to || !find_user($to)) {
        return 'net.err';
    }
    $c = connection_between($from, $to);
    if (!$c) {
        q('INSERT INTO connections (requester_id, addressee_id) VALUES (?, ?)', [$from, $to]);
        return 'net.requested';
    }
    if ($c['status'] === 'pending' && (int)$c['addressee_id'] === $from) {
        q("UPDATE connections SET status = 'accepted', accepted_at = NOW() WHERE id = ?", [$c['id']]);
        return 'net.accepted';
    }
    return 'net.requested';
}

/** Aceitar ou rejeitar um pedido recebido. Rejeitar apaga o pedido. */
function respond_connection(int $me, int $id, bool $accept): bool
{
    if ($accept) {
        return q(
            "UPDATE connections SET status = 'accepted', accepted_at = NOW() WHERE id = ? AND addressee_id = ? AND status = 'pending'",
            [$id, $me]
        )->rowCount() > 0;
    }
    return q("DELETE FROM connections WHERE id = ? AND addressee_id = ? AND status = 'pending'", [$id, $me])->rowCount() > 0;
}

/** Cancelar um pedido enviado ou remover uma conexão. */
function remove_connection(int $me, int $other): void
{
    q(
        'DELETE FROM connections WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)',
        [$me, $other, $other, $me]
    );
}

/** Conexões aceites, com os dados do outro utilizador. */
function list_connections(int $uid): array
{
    return q(
        "SELECT c.id AS link_id, c.accepted_at, u.*
           FROM connections c
           JOIN users u ON u.id = IF(c.requester_id = ?, c.addressee_id, c.requester_id)
          WHERE (c.requester_id = ? OR c.addressee_id = ?) AND c.status = 'accepted' AND u.active = 1
       ORDER BY u.display_name",
        [$uid, $uid, $uid]
    )->fetchAll();
}

function connection_requests_in(int $uid): array
{
    return q(
        "SELECT c.id AS link_id, c.created_at AS requested_at, u.*
           FROM connections c JOIN users u ON u.id = c.requester_id
          WHERE c.addressee_id = ? AND c.status = 'pending' AND u.active = 1 ORDER BY c.created_at DESC",
        [$uid]
    )->fetchAll();
}

function connection_requests_out(int $uid): array
{
    return q(
        "SELECT c.id AS link_id, c.created_at AS requested_at, u.*
           FROM connections c JOIN users u ON u.id = c.addressee_id
          WHERE c.requester_id = ? AND c.status = 'pending' AND u.active = 1 ORDER BY c.created_at DESC",
        [$uid]
    )->fetchAll();
}

// ---------- Treinador e atletas ----------

function coach_link(int $coachId, int $athleteId): ?array
{
    return q('SELECT * FROM coach_athletes WHERE coach_id = ? AND athlete_id = ?', [$coachId, $athleteId])->fetch() ?: null;
}

/** O treinador tem acesso a todos os treinos do atleta (só enquanto a conta for de treinador). */
function is_coach_of(int $coachId, int $athleteId): bool
{
    return (bool)q(
        "SELECT 1 FROM coach_athletes ca JOIN users u ON u.id = ca.coach_id
          WHERE ca.coach_id = ? AND ca.athlete_id = ? AND ca.status = 'accepted' AND u.is_coach = 1 AND u.active = 1",
        [$coachId, $athleteId]
    )->fetchColumn();
}

/**
 * Cria a ligação treinador -> atleta. Pode ser pedida pelo treinador (convite)
 * ou pelo atleta; fica pendente até o outro aceitar. Se o outro lado já tinha
 * pedido, fica aceite.
 */
function request_coaching(int $by, int $coachId, int $athleteId): string
{
    $coach = find_user($coachId);
    if ($coachId === $athleteId || !$coach || !$coach['is_coach'] || !find_user($athleteId)) {
        return 'net.err';
    }
    $link = coach_link($coachId, $athleteId);
    if (!$link) {
        q('INSERT INTO coach_athletes (coach_id, athlete_id, requested_by) VALUES (?, ?, ?)', [$coachId, $athleteId, $by]);
        return 'coach.requested';
    }
    if ($link['status'] === 'pending' && (int)$link['requested_by'] !== $by) {
        q("UPDATE coach_athletes SET status = 'accepted', accepted_at = NOW() WHERE id = ?", [$link['id']]);
        return 'coach.accepted';
    }
    return 'coach.requested';
}

/** Aceitar ou rejeitar um pedido de treino recebido (quem responde é quem não pediu). */
function respond_coaching(int $me, int $id, bool $accept): bool
{
    $where = "id = ? AND (coach_id = ? OR athlete_id = ?) AND requested_by <> ? AND status = 'pending'";
    if ($accept) {
        return q("UPDATE coach_athletes SET status = 'accepted', accepted_at = NOW() WHERE $where", [$id, $me, $me, $me])->rowCount() > 0;
    }
    return q("DELETE FROM coach_athletes WHERE $where", [$id, $me, $me, $me])->rowCount() > 0;
}

/** Qualquer um dos lados pode terminar a ligação (ou cancelar o pedido). */
function remove_coaching(int $me, int $id): void
{
    q('DELETE FROM coach_athletes WHERE id = ? AND (coach_id = ? OR athlete_id = ?)', [$id, $me, $me]);
}

/** Atletas do treinador, com um resumo da atividade recente. */
function list_athletes(int $coachId): array
{
    return q(
        "SELECT ca.id AS link_id, ca.accepted_at, u.*,
                (SELECT MAX(w.workout_date) FROM workouts w WHERE w.user_id = u.id) AS last_workout,
                (SELECT COUNT(*) FROM workouts w WHERE w.user_id = u.id AND w.active = 1
                    AND w.workout_date >= CURDATE() - INTERVAL 30 DAY) AS workouts_30d,
                (SELECT COALESCE(SUM(s.distance_m), 0) FROM workouts w
                   JOIN workout_sets s ON s.workout_id = w.id JOIN workout_reps r ON r.set_id = s.id
                  WHERE w.user_id = u.id AND w.active = 1 AND w.workout_date >= CURDATE() - INTERVAL 30 DAY) AS meters_30d
           FROM coach_athletes ca JOIN users u ON u.id = ca.athlete_id
          WHERE ca.coach_id = ? AND ca.status = 'accepted' AND u.active = 1
       ORDER BY u.display_name",
        [$coachId]
    )->fetchAll();
}

/** Treinadores do atleta (aceites). */
function list_coaches(int $athleteId): array
{
    return q(
        "SELECT ca.id AS link_id, ca.accepted_at, u.*
           FROM coach_athletes ca JOIN users u ON u.id = ca.coach_id
          WHERE ca.athlete_id = ? AND ca.status = 'accepted' AND u.is_coach = 1 AND u.active = 1
       ORDER BY u.display_name",
        [$athleteId]
    )->fetchAll();
}

/** Pedidos de treino pendentes que envolvem o utilizador; 'incoming' = cabe-lhe responder. */
function coaching_requests(int $uid): array
{
    $rows = q(
        "SELECT ca.id AS link_id, ca.coach_id, ca.athlete_id, ca.requested_by, ca.created_at AS requested_at, u.*
           FROM coach_athletes ca
           JOIN users u ON u.id = IF(ca.coach_id = ?, ca.athlete_id, ca.coach_id)
          WHERE (ca.coach_id = ? OR ca.athlete_id = ?) AND ca.status = 'pending' AND u.active = 1
       ORDER BY ca.created_at DESC",
        [$uid, $uid, $uid]
    )->fetchAll();
    foreach ($rows as &$r) {
        $r['incoming'] = (int)$r['requested_by'] !== $uid;
        $r['i_am_coach'] = (int)$r['coach_id'] === $uid;
    }
    unset($r);
    return $rows;
}

/** Número de pedidos à espera de resposta (para o menu). */
function pending_requests_count(int $uid): int
{
    return (int)q(
        "SELECT (SELECT COUNT(*) FROM connections c JOIN users u ON u.id = c.requester_id
                  WHERE c.addressee_id = ? AND c.status = 'pending' AND u.active = 1)
              + (SELECT COUNT(*) FROM coach_athletes ca JOIN users u ON u.id = ca.requested_by
                  WHERE (ca.coach_id = ? OR ca.athlete_id = ?) AND ca.requested_by <> ? AND ca.status = 'pending' AND u.active = 1)",
        [$uid, $uid, $uid, $uid]
    )->fetchColumn();
}

// ---------- Acesso aos treinos ----------

/** O próprio e os seus treinadores veem tudo, incluindo treinos privados e estatísticas. */
function has_full_access(int $viewerId, int $ownerId): bool
{
    return $viewerId === $ownerId || is_coach_of($viewerId, $ownerId);
}

/** Visibilidades que o visitante pode ver nos treinos do dono. */
function visible_levels(int $viewerId, int $ownerId): array
{
    if (has_full_access($viewerId, $ownerId)) {
        return VISIBILITIES;
    }
    return are_connected($viewerId, $ownerId) ? ['connections', 'public'] : ['public'];
}

function can_view_workout(int $viewerId, array $w): bool
{
    return in_array($w['visibility'], visible_levels($viewerId, (int)$w['user_id']), true);
}

/**
 * Atleta cujos dados a página mostra: o próprio, ou ?u=ID se quem vê for
 * treinador desse atleta.
 */
function viewed_athlete(array $viewer): array
{
    $id = (int)($_GET['u'] ?? 0);
    if (!$id || $id === (int)$viewer['id']) {
        return $viewer;
    }
    $owner = find_user($id);
    if (!$owner || !is_coach_of((int)$viewer['id'], $id)) {
        flash('error', t('error.forbidden'));
        redirect($viewer['is_coach'] ? 'athletes.php' : 'dashboard.php');
    }
    return $owner;
}

// ---------- Pesquisa e atividade ----------

function search_users(int $me, string $term, int $limit = 30): array
{
    $like = '%' . addcslashes($term, '%_\\') . '%';
    return q(
        'SELECT * FROM users WHERE id <> ? AND active = 1 AND (display_name LIKE ? OR club LIKE ?)
       ORDER BY display_name LIMIT ' . (int)$limit,
        [$me, $like, $like]
    )->fetchAll();
}

/** Treinos recentes das conexões (os que me deixam ver) e de todos os meus atletas. */
const FEED_PAGE = 20;

/** Treinos partilhados pelas conexões e treinos dos atletas, mais recentes primeiro. */
function activity_feed(int $uid, int $limit = FEED_PAGE, int $offset = 0): array
{
    $rows = q(
        "SELECT w.*, u.display_name AS author_name, u.avatar AS author_avatar
           FROM workouts w JOIN users u ON u.id = w.user_id
          WHERE u.active = 1 AND ((w.user_id IN (
                    SELECT IF(c.requester_id = ?, c.addressee_id, c.requester_id) FROM connections c
                     WHERE (c.requester_id = ? OR c.addressee_id = ?) AND c.status = 'accepted')
                 AND w.visibility IN ('connections', 'public'))
             OR w.user_id IN (
                    SELECT ca.athlete_id FROM coach_athletes ca JOIN users cu ON cu.id = ca.coach_id
                     WHERE ca.coach_id = ? AND ca.status = 'accepted' AND cu.is_coach = 1))
       ORDER BY w.workout_date DESC, w.start_time DESC, w.id DESC
          LIMIT " . (int)$limit . ' OFFSET ' . max(0, $offset),
        [$uid, $uid, $uid, $uid]
    )->fetchAll();
    return attach_sets($rows);
}

/** Estado da relação com outro utilizador, para os botões do perfil. */
function relation_with(array $me, array $other): array
{
    $meId = (int)$me['id'];
    $otherId = (int)$other['id'];
    $c = connection_between($meId, $otherId);
    $asCoach = $me['is_coach'] ? coach_link($meId, $otherId) : null;      // eu treino o outro
    $asAthlete = $other['is_coach'] ? coach_link($otherId, $meId) : null; // o outro treina-me
    return [
        'connection' => $c ? ($c['status'] === 'accepted' ? 'accepted' : ((int)$c['requester_id'] === $meId ? 'sent' : 'received')) : null,
        'connection_id' => $c ? (int)$c['id'] : null,
        'as_coach' => $asCoach,
        'as_athlete' => $asAthlete,
    ];
}
