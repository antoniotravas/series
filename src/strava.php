<?php
declare(strict_types=1);

// Ligação ao Strava (OAuth) e importação de corridas.
// Os tokens ficam em strava_accounts. As atividades são lidas como séries de pontos,
// convertidas num GPX temporário e analisadas por gpx_detect(); esse GPX é apagado logo a seguir.

const STRAVA_SCOPE = 'activity:read_all';
const STRAVA_RUN_TYPES = ['Run', 'TrailRun', 'VirtualRun'];

function strava_config(): array
{
    $c = (array)config('strava', []);
    return ['client_id' => (string)($c['client_id'] ?? ''), 'client_secret' => (string)($c['client_secret'] ?? '')];
}

function strava_configured(): bool
{
    $c = strava_config();
    return $c['client_id'] !== '' && $c['client_secret'] !== '';
}

function strava_redirect_uri(string $page): string
{
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . url($page);
}

function strava_authorize_url(string $redirectUri, string $state): string
{
    return 'https://www.strava.com/oauth/authorize?' . http_build_query([
        'client_id'       => strava_config()['client_id'],
        'redirect_uri'    => $redirectUri,
        'response_type'   => 'code',
        'approval_prompt' => 'auto',
        'scope'           => STRAVA_SCOPE,
        'state'           => $state,
    ]);
}

/** Pedido HTTP com curl. Devolve o estado e o corpo. */
function strava_http(string $url, ?string $bearer = null, ?string $postBody = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER     => $bearer ? ['Authorization: Bearer ' . $bearer] : [],
    ]);
    if ($postBody !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
    }
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => is_string($body) ? $body : ''];
}

/** Pedido de token (troca do code ou renovação). Devolve null se falhar. */
function strava_token_request(array $params): ?array
{
    $c = strava_config();
    $r = strava_http('https://www.strava.com/oauth/token', null,
        http_build_query($params + ['client_id' => $c['client_id'], 'client_secret' => $c['client_secret']]));
    $t = json_decode($r['body'], true);
    if ($r['status'] !== 200 || empty($t['access_token']) || empty($t['refresh_token'])) {
        error_log('Strava OAuth (' . $r['status'] . '): ' . $r['body']);
        return null;
    }
    return $t;
}

function strava_exchange_code(string $code): ?array
{
    return strava_token_request(['code' => $code, 'grant_type' => 'authorization_code']);
}

function strava_account(int $userId): ?array
{
    return q('SELECT * FROM strava_accounts WHERE user_id = ?', [$userId])->fetch() ?: null;
}

function strava_save_tokens(int $userId, array $t, ?int $athleteId = null): void
{
    q('INSERT INTO strava_accounts (user_id, athlete_id, access_token, refresh_token, expires_at)
       VALUES (?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE athlete_id = COALESCE(VALUES(athlete_id), athlete_id),
                               access_token = VALUES(access_token),
                               refresh_token = VALUES(refresh_token),
                               expires_at = VALUES(expires_at)',
        [$userId, $athleteId ?: null, $t['access_token'], $t['refresh_token'], (int)$t['expires_at']]);
}

/** Token de acesso do utilizador; renova-o se estiver para expirar. */
function strava_access_token(int $userId): string
{
    $acc = strava_account($userId);
    if (!$acc) {
        throw new RuntimeException('strava: conta não ligada');
    }
    if ((int)$acc['expires_at'] > time() + 60) {
        return $acc['access_token'];
    }
    $t = strava_token_request(['grant_type' => 'refresh_token', 'refresh_token' => $acc['refresh_token']]);
    if (!$t) {
        throw new RuntimeException('strava: não foi possível renovar o token');
    }
    strava_save_tokens($userId, $t);
    return $t['access_token'];
}

/** Chamada à API do Strava (GET). Devolve o JSON descodificado. */
function strava_api(int $userId, string $path, array $query = []): array
{
    $url = 'https://www.strava.com/api/v3' . $path . ($query ? '?' . http_build_query($query) : '');
    $r = strava_http($url, strava_access_token($userId));
    $data = json_decode($r['body'], true);
    if ($r['status'] !== 200 || !is_array($data)) {
        throw new RuntimeException('strava: pedido falhou (' . $r['status'] . ')');
    }
    return $data;
}

/**
 * Corridas a partir de uma página do Strava. Como nem todas as atividades são corridas, lê páginas
 * de 100 atividades até juntar pelo menos 20 corridas (ou acabarem as atividades).
 * $range pode ter 'after' e 'before' (timestamps). Devolve 'runs', 'next' (página seguinte) e 'more'.
 */
function strava_list_runs(int $userId, int $page = 1, array $range = []): array
{
    $runs = [];
    $p = $page;
    $more = true;
    for ($i = 0; $i < 10 && count($runs) < 20 && $more; $i++, $p++) {
        $acts = strava_api($userId, '/athlete/activities', ['page' => $p, 'per_page' => 100] + $range);
        foreach ($acts as $a) {
            if (in_array($a['sport_type'] ?? $a['type'] ?? '', STRAVA_RUN_TYPES, true)) {
                $runs[] = $a;
            }
        }
        $more = count($acts) === 100;
    }
    return ['runs' => $runs, 'next' => $p, 'more' => $more];
}

/** Analisa uma atividade do Strava com a mesma deteção do GPX. */
function strava_detect(int $userId, int $activityId): array
{
    $act = strava_api($userId, '/activities/' . $activityId);
    $streams = strava_api($userId, '/activities/' . $activityId . '/streams', ['keys' => 'time,latlng,heartrate', 'key_type' => 'time']);

    $by = [];
    foreach ($streams as $s) {
        $by[$s['type'] ?? ''] = $s['data'] ?? null;
    }
    $latlng = $by['latlng'] ?? null;
    $time = $by['time'] ?? null;
    $hr = $by['heartrate'] ?? null;
    if (!is_array($latlng) || !is_array($time)) {
        throw new RuntimeException('strava: a atividade não tem GPS');
    }

    $start = strtotime((string)$act['start_date']);
    $gpx = '<?xml version="1.0" encoding="UTF-8"?>'
         . '<gpx version="1.1" creator="Series" xmlns="http://www.topografix.com/GPX/1/1"><trk><trkseg>';
    foreach ($latlng as $i => $p) {
        if (!is_array($p) || !isset($time[$i])) {
            continue;
        }
        $gpx .= sprintf('<trkpt lat="%.6F" lon="%.6F"><time>%s</time>',
            $p[0], $p[1], gmdate('Y-m-d\TH:i:s\Z', $start + (int)$time[$i]));
        if (isset($hr[$i])) {
            $gpx .= '<extensions><hr>' . (int)$hr[$i] . '</hr></extensions>';
        }
        $gpx .= '</trkpt>';
    }
    $gpx .= '</trkseg></trk></gpx>';

    $tmp = tempnam(sys_get_temp_dir(), 'strava');
    try {
        file_put_contents($tmp, $gpx);
        return gpx_detect($tmp);
    } finally {
        @unlink($tmp);
    }
}

/** Revoga o acesso no Strava e apaga os tokens locais. */
function strava_disconnect(int $userId): void
{
    $acc = strava_account($userId);
    if ($acc) {
        strava_http('https://www.strava.com/oauth/deauthorize', null,
            http_build_query(['access_token' => $acc['access_token']]));
    }
    q('DELETE FROM strava_accounts WHERE user_id = ?', [$userId]);
}
