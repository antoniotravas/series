<?php
declare(strict_types=1);

// Deteção automática de repetições num ficheiro GPX.
//
// 1. velocidade suavizada (janela de 5 s) a partir das posições;
// 2. limiar esforço/descanso escolhido pelo método de Otsu sobre as velocidades;
// 3. cada trecho acima do limiar é uma repetição; a distância GPS é aproximada
//    à distância de pista mais próxima (ex. 294 m -> 300 m);
// 4. repetições seguidas com a mesma distância formam um bloco; o intervalo é a
//    mediana das pausas, arredondada aos 15 s.

const TRACK_DISTANCES = [60, 80, 100, 120, 150, 200, 250, 300, 350, 400, 500, 600, 700, 800,
                         1000, 1200, 1500, 1600, 2000, 3000, 5000];

/** Lê os pontos de um GPX: [['t' => unix, 'lat', 'lon', 'hr' => ?int, 'temp' => ?float]] */
function gpx_points(string $path): array
{
    $x = @simplexml_load_file($path, SimpleXMLElement::class, LIBXML_NONET);
    if ($x === false) {
        throw new RuntimeException('gpx');
    }
    $x->registerXPathNamespace('g', $x->getNamespaces()[''] ?? 'http://www.topografix.com/GPX/1/1');
    $pts = [];
    foreach ($x->xpath('//g:trkpt') ?: [] as $p) {
        $t = strtotime((string)$p->time);
        if ($t === false) {
            continue;
        }
        $hr = $p->xpath('.//*[local-name()="hr"]');
        $temp = $p->xpath('.//*[local-name()="atemp"]');
        $pts[] = [
            't' => $t, 'lat' => (float)$p['lat'], 'lon' => (float)$p['lon'],
            'hr' => $hr ? (int)$hr[0] : null, 'temp' => $temp ? (float)$temp[0] : null,
        ];
    }
    usort($pts, fn($a, $b) => $a['t'] <=> $b['t']);
    return $pts;
}

function haversine(array $a, array $b): float
{
    $dLat = deg2rad($b['lat'] - $a['lat']);
    $dLon = deg2rad($b['lon'] - $a['lon']);
    $h = sin($dLat / 2) ** 2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLon / 2) ** 2;
    return 2 * 6371008.8 * asin(min(1, sqrt($h)));
}

/** Limiar que melhor separa duas classes de valores (método de Otsu, histograma de 0,1 m/s). */
function otsu_threshold(array $values, float $step = 0.1): float
{
    $hist = [];
    foreach ($values as $v) {
        $bin = (int)floor($v / $step);
        $hist[$bin] = ($hist[$bin] ?? 0) + 1;
    }
    ksort($hist);
    $total = count($values);
    $sumAll = 0.0;
    foreach ($hist as $bin => $n) {
        $sumAll += $bin * $n;
    }
    $best = 0.0;
    $threshold = 0;
    $wB = 0;
    $sumB = 0.0;
    foreach ($hist as $bin => $n) {
        $wB += $n;
        $wF = $total - $wB;
        if ($wB === 0 || $wF === 0) {
            continue;
        }
        $sumB += $bin * $n;
        $between = $wB * $wF * (($sumB / $wB) - (($sumAll - $sumB) / $wF)) ** 2;
        if ($between > $best) {
            $best = $between;
            $threshold = $bin;
        }
    }
    return ($threshold + 1) * $step;
}

/** Distância de pista mais próxima (tolerância 7% ou 15 m); fora disso arredonda à dezena. */
function snap_distance(float $m): array
{
    $best = null;
    foreach (TRACK_DISTANCES as $d) {
        if (abs($m - $d) <= max(15, $d * 0.07) &&($best === null || abs($m - $d) < abs($m - $best))) {
            $best = $d;
        }
    }
    return [$best ?? (int)(round($m / 10) * 10), $best !== null];
}

/**
 * Analisa o GPX e devolve:
 *  - 'date', 'start_time' (hora local do início da atividade)
 *  - 'reps': cada esforço detetado (distância GPS, distância aferida, tempo, FC média, 'work' = conta como repetição)
 *  - 'sets': blocos prontos para o formulário (distance_m, reps, rest, rest_type)
 */
function gpx_detect(string $path): array
{
    $pts = gpx_points($path);
    $n = count($pts);
    if ($n < 30) {
        throw new RuntimeException('gpx_short');
    }

    $cum = [0.0];
    for ($i = 1; $i < $n; $i++) {
        $cum[$i] = $cum[$i - 1] + haversine($pts[$i - 1], $pts[$i]);
    }
    // velocidade suavizada: deslocação em linha reta numa janela centrada de ~5 s
    // (somar os saltos de cada segundo acumularia o ruído do GPS nas pausas)
    $speed = [];
    for ($i = 0, $a = 0, $b = 0; $i < $n; $i++) {
        while ($pts[$i]['t'] - $pts[$a]['t'] > 2) {
            $a++;
        }
        while ($b + 1 < $n && $pts[$b + 1]['t'] - $pts[$i]['t'] <= 2) {
            $b++;
        }
        $dt = $pts[$b]['t'] - $pts[$a]['t'];
        $speed[$i] = $dt > 0 ? haversine($pts[$a], $pts[$b]) / $dt : 0.0;
    }

    $threshold = max(1.8, otsu_threshold($speed));
    // duração de uma amostra (normalmente 1 s): o último ponto rápido também conta
    $sample = max(1, (int)round(($pts[$n - 1]['t'] - $pts[0]['t']) / ($n - 1)));

    // trechos acima do limiar; pausas até 20 s não cortam o trecho (o GPS congela posições
    // durante ~15 s dentro de um esforço; as recuperações de treino duram quase sempre mais)
    $segs = [];
    $start = null;
    $lastFast = null;
    for ($i = 0; $i < $n; $i++) {
        if ($speed[$i] >= $threshold) {
            if ($start === null) {
                $start = $i;
            } elseif ($pts[$i]['t'] - $pts[$lastFast]['t'] > 20) {
                $segs[] = [$start, $lastFast];
                $start = $i;
            }
            $lastFast = $i;
        }
    }
    if ($start !== null) {
        $segs[] = [$start, $lastFast];
    }
    $reps = [];
    foreach ($segs as [$a, $b]) {
        $time = $pts[$b]['t'] - $pts[$a]['t'] + $sample;
        $dist = $cum[$b] - $cum[$a];
        if ($time < 10 || $dist < 50) {
            continue;
        }
        [$snapped, $isTrack] = snap_distance($dist);
        $hr = array_filter(array_column(array_slice($pts, $a, $b - $a + 1), 'hr'));
        $reps[] = [
            'from' => $a, 'to' => $b,
            'start' => $pts[$a]['t'] - $pts[0]['t'],
            'gps_m' => (int)round($dist), 'distance_m' => $snapped, 'track' => $isTrack,
            'seconds' => $time, 'speed' => $dist / $time,
            'hr' => $hr ? (int)round(array_sum($hr) / count($hr)) : null,
            'hr_max' => $hr ? max($hr) : null,
        ];
    }
    if (!$reps) {
        return ['date' => date('Y-m-d', $pts[0]['t']), 'start_time' => date('H:i', $pts[0]['t']), 'reps' => [], 'sets' => [],
                'temperature_c' => null, 'lat' => null, 'lon' => null];
    }

    // trechos muito mais lentos que o esforço mais rápido = aquecimento/retorno à calma
    $fastest = max(array_column($reps, 'speed'));
    foreach ($reps as &$r) {
        $r['work'] = $r['speed'] >= $fastest * 0.75;
    }
    unset($r);

    // distância que não encaixa na pista: usar a das repetições vizinhas, se estiver a menos de 10%
    $workIdx = array_keys(array_filter($reps, fn($r) => $r['work']));
    foreach ($workIdx as $p => $i) {
        if ($reps[$i]['track']) {
            continue;
        }
        foreach ([$workIdx[$p - 1] ?? null, $workIdx[$p + 1] ?? null] as $j) {
            if ($j !== null && $reps[$j]['track'] && abs($reps[$i]['gps_m'] - $reps[$j]['distance_m']) <= $reps[$j]['distance_m'] * 0.10) {
                $reps[$i]['distance_m'] = $reps[$j]['distance_m'];
                $reps[$i]['track'] = true;
                break;
            }
        }
    }
    $work = array_map(fn($i) => $reps[$i], $workIdx);

    // agrupar repetições seguidas com a mesma distância
    $sets = [];
    foreach ($work as $k => $r) {
        $next = $work[$k + 1] ?? null;
        $rest = $next ? $pts[$next['from']]['t'] - $pts[$r['to']]['t'] - $sample : null;
        // tipo de recuperação: mediana da velocidade suavizada na pausa (resiste ao ruído do GPS)
        $restSpeed = null;
        if ($next && $next['from'] > $r['to'] + 1) {
            $gap = array_slice($speed, $r['to'] + 1, $next['from'] - $r['to'] - 1);
            sort($gap);
            $restSpeed = $gap[intdiv(count($gap), 2)];
        }
        $last = array_key_last($sets);
        if ($last === null || $sets[$last]['distance_m'] !== $r['distance_m']) {
            $sets[] = ['distance_m' => $r['distance_m'], 'reps' => [], 'hr' => [], 'hr_max' => [], 'rests' => [], 'rest_speeds' => []];
            $last = array_key_last($sets);
        }
        $sets[$last]['reps'][] = $r['seconds'];
        $sets[$last]['hr'][] = $r['hr'];
        $sets[$last]['hr_max'][] = $r['hr_max'];
        if ($rest !== null) {
            $sets[$last]['rests'][] = $rest;
            if ($restSpeed !== null) {
                $sets[$last]['rest_speeds'][] = $restSpeed;
            }
        }
    }
    foreach ($sets as &$s) {
        // descanso dentro do bloco (ou, num bloco de 1 rep, até ao bloco seguinte)
        $rests = count($s['rests']) > 1 && count($s['rests']) >= count($s['reps']) ? array_slice($s['rests'], 0, -1) : $s['rests'];
        $speeds = count($s['rest_speeds']) > 1 && count($s['rest_speeds']) >= count($s['reps']) ? array_slice($s['rest_speeds'], 0, -1) : $s['rest_speeds'];
        sort($rests);
        // os intervalos costumam ser redondos (60", 70", 1:30, 2:30...): arredondar aos 5 s
        $s['rest'] = $rests ? max(15, (int)(round($rests[intdiv(count($rests), 2)] / 5) * 5)) : null;
        $v = $speeds ? array_sum($speeds) / count($speeds) : 0;
        $s['rest_type'] = $v < 0.6 ? 'stand' : ($v < 1.9 ? 'walk' : 'jog');
        unset($s['rests'], $s['rest_speeds']);
    }
    unset($s);

    // local: mediana das posições durante as repetições (o início pode ser no estacionamento)
    $lats = $lons = [];
    foreach ($work as $r) {
        for ($i = $r['from']; $i <= $r['to']; $i++) {
            $lats[] = $pts[$i]['lat'];
            $lons[] = $pts[$i]['lon'];
        }
    }
    sort($lats);
    sort($lons);
    $mid = intdiv(count($lats), 2);

    // temperatura do sensor do relógio (média da atividade); nem todos os GPX a têm
    $temps = array_filter(array_column($pts, 'temp'), fn($v) => $v !== null);

    return [
        'date'       => date('Y-m-d', $pts[0]['t']),
        'start_time' => date('H:i', $pts[0]['t']),
        'temperature_c' => $temps ? (int)round(array_sum($temps) / count($temps)) : null,
        'lat'        => $lats ? round($lats[$mid], 6) : null,
        'lon'        => $lons ? round($lons[$mid], 6) : null,
        'threshold'  => round($threshold, 2),
        'reps'       => array_map(fn($r) => array_diff_key($r, ['from' => 1, 'to' => 1]), $reps),
        'sets'       => $sets,
    ];
}
