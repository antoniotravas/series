<?php
declare(strict_types=1);

// Nome do local de treino a partir de coordenadas.
//
// 1. treinos anteriores do atleta a menos de PLACE_RADIUS_M: usa o nome que ele escreveu;
// 2. OpenStreetMap (Overpass): pista de atletismo, estádio ou complexo desportivo mais próximo;
//    o Overpass fica muitas vezes sobrecarregado, por isso tem pouco tempo de espera e há
//    alternativa pelo Nominatim (pesquisa de estádio / complexo desportivo à volta do ponto);
// 3. OpenStreetMap (Nominatim): localidade.
// Os serviços do OpenStreetMap são gratuitos mas pedem um User-Agent identificável e
// no máximo 1 pedido por segundo ao Nominatim: só se consulta ao analisar um GPX.

const PLACE_RADIUS_M = 400;

/** Devolve ['name' => string, 'source' => 'mine'|'osm'|'town'] ou null. */
function place_name(int $userId, float $lat, float $lon): ?array
{
    $here = ['lat' => $lat, 'lon' => $lon];

    $best = null;
    $rows = q(
        'SELECT location, lat, lon FROM workouts
          WHERE user_id = ? AND lat IS NOT NULL AND location IS NOT NULL
            AND lat BETWEEN ? AND ? AND lon BETWEEN ? AND ?
       ORDER BY workout_date DESC LIMIT 200',
        [$userId, $lat - 0.01, $lat + 0.01, $lon - 0.015, $lon + 0.015]
    );
    foreach ($rows as $r) {
        $d = haversine($here, ['lat' => (float)$r['lat'], 'lon' => (float)$r['lon']]);
        if ($d <= PLACE_RADIUS_M && ($best === null || $d < $best[1])) {
            $best = [$r['location'], $d];
        }
    }
    if ($best) {
        return ['name' => $best[0], 'source' => 'mine'];
    }

    if ($name = osm_sports_place($lat, $lon) ?? nominatim_sports_place($lat, $lon)) {
        return ['name' => $name, 'source' => 'osm'];
    }
    if ($name = osm_town($lat, $lon)) {
        return ['name' => $name, 'source' => 'town'];
    }
    return null;
}

function osm_get(string $url, ?string $post = null, int $timeout = 6): ?array
{
    // Nominatim: no máximo 1 pedido por segundo
    static $lastNominatim = 0.0;
    if (str_contains($url, 'nominatim.')) {
        $wait = 1.05 - (microtime(true) - $lastNominatim);
        if ($wait > 0) {
            usleep((int)($wait * 1e6));
        }
        $lastNominatim = microtime(true);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_USERAGENT      => config('app_name') . ' (' . config('mail_from') . ')',
        CURLOPT_HTTPHEADER     => ['Accept-Language: ' . current_lang()],
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status !== 200 || !is_string($body)) {
        error_log("OpenStreetMap: $status em $url");
        return null;
    }
    return json_decode($body, true) ?: null;
}

/** Pista de atletismo / estádio / complexo desportivo com nome mais próximo. */
function osm_sports_place(float $lat, float $lon): ?string
{
    $r = PLACE_RADIUS_M + 100;
    $around = sprintf('around:%d,%.6F,%.6F', $r, $lat, $lon);
    $query = "[out:json][timeout:4];(nwr($around)[leisure~\"^(track|stadium|sports_centre)$\"][name];"
           . "nwr($around)[sport=athletics][name];);out tags center 30;";
    $res = osm_get('https://overpass-api.de/api/interpreter', http_build_query(['data' => $query]), 4);
    if (!$res || empty($res['elements'])) {
        return null;
    }
    // preferir o que é de atletismo, depois estádio, depois complexo; em empate, o mais perto
    $rank = fn(array $tags) => match (true) {
        ($tags['sport'] ?? '') === 'athletics' && ($tags['leisure'] ?? '') !== 'sports_centre' => 0,
        ($tags['leisure'] ?? '') === 'stadium' => 1,
        ($tags['leisure'] ?? '') === 'sports_centre' => 2,
        default => 3,
    };
    $best = null;
    foreach ($res['elements'] as $el) {
        $c = $el['center'] ?? $el;
        if (!isset($c['lat'], $c['lon'], $el['tags']['name'])) {
            continue;
        }
        $key = [$rank($el['tags']), haversine(['lat' => $lat, 'lon' => $lon], ['lat' => $c['lat'], 'lon' => $c['lon']])];
        if ($best === null || $key < $best[0]) {
            $best = [$key, $el['tags']['name']];
        }
    }
    return $best ? mb_substr($best[1], 0, 120) : null;
}

/** Alternativa ao Overpass: estádio ou complexo desportivo mais próximo pela pesquisa do Nominatim. */
function nominatim_sports_place(float $lat, float $lon): ?string
{
    $r = PLACE_RADIUS_M + 100;
    $dLat = $r / 111320;
    $dLon = $r / (111320 * cos(deg2rad($lat)));
    $viewbox = sprintf('%.6F,%.6F,%.6F,%.6F', $lon - $dLon, $lat + $dLat, $lon + $dLon, $lat - $dLat);
    foreach (['[stadium]', '[sports centre]'] as $phrase) {
        $res = osm_get('https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $phrase, 'format' => 'jsonv2', 'bounded' => 1, 'viewbox' => $viewbox, 'limit' => 10,
        ]));
        $best = null;
        foreach ($res ?? [] as $p) {
            if (empty($p['name'])) {
                continue;
            }
            $d = haversine(['lat' => $lat, 'lon' => $lon], ['lat' => (float)$p['lat'], 'lon' => (float)$p['lon']]);
            if ($d <= $r && ($best === null || $d < $best[0])) {
                $best = [$d, $p['name']];
            }
        }
        if ($best) {
            return mb_substr($best[1], 0, 120);
        }
    }
    return null;
}

/** Localidade (cidade, vila ou freguesia). */
function osm_town(float $lat, float $lon): ?string
{
    $res = osm_get(sprintf('https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=14&lat=%.6F&lon=%.6F', $lat, $lon));
    $a = $res['address'] ?? [];
    $name = $a['city'] ?? $a['town'] ?? $a['village'] ?? $a['suburb'] ?? $a['municipality'] ?? null;
    return $name ? mb_substr($name, 0, 120) : null;
}
