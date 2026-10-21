<?php
declare(strict_types=1);

// Importação de treinos a partir de folhas Excel (.xlsx).
//
// Formato esperado (um separador por distância ou com a distância no título da tabela):
//   título    "200m"  ou  "150m (Progressivos)"
//   cabeçalho Data | 1 | 2 | ... | Média | Intervalo
//   linhas    data | tempos das repetições | (ignorada) | descanso | *antes 300m 64 63 ... | *notas
// Linhas com a mesma data, em separadores diferentes, formam um só treino.

const IMPORT_MAX_BYTES = 5 * 1024 * 1024;

// ---------- Leitura do ficheiro .xlsx (zip + XML, sem extensões extra) ----------

/** Devolve o conteúdo dos ficheiros do zip pedidos em $names (os que existirem). */
function zip_extract(string $zip, array $names): array
{
    $eocd = strrpos($zip, "PK\x05\x06");
    if ($eocd === false) {
        throw new RuntimeException('zip');
    }
    $e = unpack('vtotal/Vsize/Voffset', substr($zip, $eocd + 10, 10));
    $pos = $e['offset'];
    $out = [];
    for ($i = 0; $i < $e['total']; $i++) {
        if (substr($zip, $pos, 4) !== "PK\x01\x02") {
            throw new RuntimeException('zip');
        }
        $h = unpack('vmethod', substr($zip, $pos + 10, 2))
           + unpack('Vcsize/Vusize/vnlen/velen/vclen', substr($zip, $pos + 20, 14))
           + unpack('Vlocal', substr($zip, $pos + 42, 4));
        $name = substr($zip, $pos + 46, $h['nlen']);
        $pos += 46 + $h['nlen'] + $h['elen'] + $h['clen'];
        if (!in_array($name, $names, true) || $h['usize'] > 20 * 1024 * 1024) {
            continue;
        }
        $l = unpack('vnlen/velen', substr($zip, $h['local'] + 26, 4));
        $raw = substr($zip, $h['local'] + 30 + $l['nlen'] + $l['elen'], $h['csize']);
        $data = match ($h['method']) {
            0 => $raw,
            8 => @gzinflate($raw, $h['usize'] + 1),
            default => false,
        };
        if ($data !== false) {
            $out[$name] = $data;
        }
    }
    return $out;
}

function xml(string $s): SimpleXMLElement
{
    $x = simplexml_load_string($s, SimpleXMLElement::class, LIBXML_NONET);
    if ($x === false) {
        throw new RuntimeException('xml');
    }
    return $x;
}

/**
 * Lê um .xlsx e devolve [nome do separador => [linha => [coluna => célula]]].
 * Célula: ['v' => string|float, 'fmt' => código do formato numérico].
 */
function xlsx_read(string $path): array
{
    $zip = (string)file_get_contents($path);
    $base = zip_extract($zip, ['xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/sharedStrings.xml', 'xl/styles.xml']);
    if (!isset($base['xl/workbook.xml'], $base['xl/_rels/workbook.xml.rels'])) {
        throw new RuntimeException('xlsx');
    }

    $strings = [];
    if (isset($base['xl/sharedStrings.xml'])) {
        foreach (xml($base['xl/sharedStrings.xml'])->si as $si) {
            $t = isset($si->t) ? (string)$si->t : '';
            foreach ($si->r as $r) {
                $t .= (string)$r->t;
            }
            $strings[] = $t;
        }
    }

    // formatos numéricos: índice de estilo -> código (ex. "mm:ss.0")
    $codes = [14 => 'dd/mm/yyyy', 15 => 'd-mmm-yy', 16 => 'd-mmm', 17 => 'mmm-yy', 18 => 'h:mm AM/PM',
              19 => 'h:mm:ss AM/PM', 20 => 'h:mm', 21 => 'h:mm:ss', 22 => 'dd/mm/yyyy h:mm',
              45 => 'mm:ss', 46 => '[h]:mm:ss', 47 => 'mm:ss.0'];
    $styles = [];
    if (isset($base['xl/styles.xml'])) {
        $st = xml($base['xl/styles.xml']);
        foreach ($st->numFmts->numFmt ?? [] as $f) {
            $codes[(int)$f['numFmtId']] = (string)$f['formatCode'];
        }
        foreach ($st->cellXfs->xf ?? [] as $xf) {
            $styles[] = $codes[(int)$xf['numFmtId']] ?? '';
        }
    }

    $targets = [];
    foreach (xml($base['xl/_rels/workbook.xml.rels'])->Relationship as $r) {
        $targets[(string)$r['Id']] = 'xl/' . ltrim(preg_replace('#^/?xl/#', '', (string)$r['Target']), '/');
    }
    $sheets = [];
    foreach (xml($base['xl/workbook.xml'])->sheets->sheet as $s) {
        $rid = (string)$s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        if (isset($targets[$rid])) {
            $sheets[(string)$s['name']] = $targets[$rid];
        }
    }
    $files = zip_extract($zip, array_values($sheets));

    $out = [];
    foreach ($sheets as $name => $file) {
        if (!isset($files[$file])) {
            continue;
        }
        $grid = [];
        foreach (xml($files[$file])->sheetData->row as $row) {
            foreach ($row->c as $c) {
                if (!preg_match('/^([A-Z]+)(\d+)$/', (string)$c['r'], $m)) {
                    continue;
                }
                $type = (string)$c['t'];
                $v = match ($type) {
                    's'         => $strings[(int)$c->v] ?? '',
                    'inlineStr' => (string)$c->is->t,
                    'str'       => (string)$c->v,
                    'b', 'e'    => '',
                    default     => (string)$c->v === '' ? '' : (float)$c->v,
                };
                if (is_string($v) && trim($v) === '') {
                    continue;
                }
                $col = 0;
                foreach (str_split($m[1]) as $ch) {
                    $col = $col * 26 + ord($ch) - 64;
                }
                $grid[(int)$m[2]][$col] = ['v' => is_string($v) ? trim($v) : $v, 'fmt' => $styles[(int)$c['s']] ?? ''];
            }
        }
        ksort($grid);
        foreach ($grid as &$cells) {
            ksort($cells);
        }
        unset($cells);
        $out[$name] = $grid;
    }
    return $out;
}

// ---------- Interpretação das células ----------

/**
 * Duração em segundos. Números sem formato = segundos; formatos com segundos (mm:ss.0)
 * = fração de dia; formatos só h:mm = minutos:segundos escritos como horas:minutos.
 */
function cell_seconds(array $cell): ?float
{
    $v = $cell['v'];
    if (is_string($v)) {
        return parse_time($v);
    }
    $fmt = preg_replace('/"[^"]*"|\[[^\]]*\]/', '', $cell['fmt']);
    if (str_contains($fmt, ':')) {
        $v *= stripos($fmt, 's') !== false ? 86400 : 1440;
    }
    return $v > 0 ? round($v, 2) : null;
}

/** Data (Y-m-d) a partir de número de série do Excel ou texto. */
function cell_date(array $cell): ?string
{
    $v = $cell['v'];
    if (is_float($v)) {
        return $v > 20000 && $v < 80000 ? gmdate('Y-m-d', (int)round(($v - 25569) * 86400)) : null;
    }
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $v, $m)) {
        [$y, $mo, $d] = [(int)$m[1], (int)$m[2], (int)$m[3]];
    } elseif (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $v, $m)) {
        [$d, $mo, $y] = [(int)$m[1], (int)$m[2], (int)$m[3]];
    } else {
        return null;
    }
    return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
}

/**
 * Descanso: 60" | 3' | 3´ | 1:30' | 01:30 | 90 | célula h:mm.
 * Devolve [segundos|null, resto do texto depois de "/" ou null].
 */
function parse_rest(array $cell): array
{
    if (!is_string($cell['v'])) {
        $s = cell_seconds($cell);
        return [$s === null ? null : (int)round($s), null];
    }
    $raw = str_replace(['´', '’', '`', "''", '”', '″', '′'], ["'", "'", "'", '"', '"', '"', "'"], $cell['v']);
    $parts = explode('/', $raw, 2);
    $first = trim($parts[0]);
    $extra = isset($parts[1]) ? $cell['v'] : null;

    if (preg_match("/^(\d+)(?::(\d{1,2}))?\s*'$/", $first, $m)) {
        $sec = (int)$m[1] * 60 + (int)($m[2] ?? 0);
    } elseif (preg_match('/^(\d+)\s*"$/', $first, $m)) {
        $sec = (int)$m[1];
    } elseif (preg_match('/^(\d+):(\d{2})$/', $first, $m)) {
        $sec = (int)$m[1] * 60 + (int)$m[2];
    } elseif (ctype_digit($first)) {
        $sec = (int)$first;
    } else {
        return [null, $cell['v']];
    }
    return [$sec ?: null, $extra];
}

function distance_from_text(string $s): ?int
{
    if (!preg_match('/(\d+(?:[.,]\d+)?)\s*(km|m)\b/i', $s, $m)) {
        return null;
    }
    $n = (float)str_replace(',', '.', $m[1]);
    $d = (int)round(strtolower($m[2]) === 'km' ? $n * 1000 : $n);
    return $d >= 50 && $d <= 42195 ? $d : null;
}

function is_header_label(array $cell, array $words): bool
{
    return is_string($cell['v']) && in_array(mb_strtolower($cell['v']), $words, true);
}

// ---------- Folha -> treinos ----------

/**
 * Lê todas as tabelas de todos os separadores.
 * Devolve linhas: ['date', 'distance', 'reps', 'rest', 'title', 'notes', 'groups' => blocos antes/depois].
 */
function import_rows(array $book): array
{
    $rows = [];
    foreach ($book as $sheetName => $grid) {
        $table = null;
        $pendingTitle = null;
        foreach ($grid as $cells) {
            // cabeçalho: "Data | 1 | 2 | ... | Média | Intervalo"
            $dateCol = null;
            foreach ($cells as $col => $cell) {
                if (is_header_label($cell, ['data', 'date'])) {
                    $dateCol = $col;
                    break;
                }
            }
            if ($dateCol !== null) {
                $table = ['dateCol' => $dateCol, 'reps' => [], 'rest' => null, 'last' => $dateCol];
                foreach ($cells as $col => $cell) {
                    if ($col <= $dateCol) {
                        continue;
                    }
                    if (is_float($cell['v']) || ctype_digit((string)$cell['v'])) {
                        $table['reps'][] = $col;
                    } elseif (is_header_label($cell, ['intervalo', 'descanso', 'recuperação', 'recuperacao', 'rest'])) {
                        $table['rest'] = $col;
                    }
                    $table['last'] = max($table['last'], $col);
                }
                $title = $pendingTitle ?? (string)$sheetName;
                $table['distance'] = distance_from_text($title) ?? distance_from_text((string)$sheetName);
                $table['title'] = preg_match('/\(([^)]+)\)/', $title, $m) ? trim($m[1]) : null;
                $pendingTitle = null;
                continue;
            }

            $first = reset($cells);
            $date = $table && isset($cells[$table['dateCol']]) ? cell_date($cells[$table['dateCol']]) : null;
            if (!$date) {
                // possível título da próxima tabela
                if ($first && is_string($first['v']) && distance_from_text($first['v'])) {
                    $pendingTitle = $first['v'];
                }
                continue;
            }
            if (!$table['distance']) {
                continue;
            }

            $reps = [];
            foreach ($table['reps'] as $col) {
                if (isset($cells[$col]) && ($s = cell_seconds($cells[$col])) !== null) {
                    $reps[] = $s;
                }
            }
            [$rest, $restNote] = $table['rest'] && isset($cells[$table['rest']]) ? parse_rest($cells[$table['rest']]) : [null, null];
            $row = [
                'date' => $date, 'distance' => $table['distance'], 'reps' => $reps, 'rest' => $rest,
                'title' => $table['title'], 'notes' => $restNote ? [t('imp.note_rest', ['v' => $restNote])] : [], 'groups' => [],
            ];

            // à direita da tabela: *antes / *depois + distância + tempos, ou notas
            $modes = ['after'];
            $group = null;
            foreach ($cells as $col => $cell) {
                if ($col <= $table['last']) {
                    continue;
                }
                $v = $cell['v'];
                if (is_string($v) && str_starts_with($v, '*')) {
                    $text = trim(substr($v, 1));
                    if (preg_match('#^(antes|depois|before|after)(?:\s*/\s*(antes|depois|before|after))?$#iu', $text, $m)) {
                        $modes = array_map(fn($w) => in_array(mb_strtolower($w), ['antes', 'before'], true) ? 'before' : 'after', array_slice(array_filter($m), 1));
                        continue;
                    }
                    if ($group !== null && ($r = parse_rest(['v' => $text, 'fmt' => ''])[0]) !== null) {
                        $row['groups'][$group]['rest'] = $r;
                        continue;
                    }
                    $row['notes'][] = $text;
                    continue;
                }
                if (is_string($v) && preg_match('/^\d+(?:[.,]\d+)?\s*k?m$/i', $v) && ($d = distance_from_text($v))) {
                    $i = count($row['groups']);
                    $row['groups'][] = ['distance' => $d, 'reps' => [], 'rest' => null, 'when' => $modes[min($i, count($modes) - 1)]];
                    $group = array_key_last($row['groups']);
                    continue;
                }
                if ($group !== null) {
                    if (is_string($v) && preg_match('/[\'´"]/', $v)) {
                        $row['groups'][$group]['rest'] = parse_rest($cell)[0];
                    } elseif (($s = cell_seconds($cell)) !== null) {
                        $row['groups'][$group]['reps'][] = $s;
                    }
                    continue;
                }
                if (is_string($v)) {
                    $row['notes'][] = $v;
                }
            }
            $row['groups'] = array_values(array_filter($row['groups'], fn($g) => $g['reps']));
            if ($reps) {
                $rows[] = $row;
            }
        }
    }
    return $rows;
}

/** Junta as linhas da mesma data num treino, ordenando os blocos pelos "antes/depois". */
function import_workouts(array $rows): array
{
    $byDate = [];
    foreach ($rows as $r) {
        $byDate[$r['date']][] = $r;
    }
    ksort($byDate);

    $workouts = [];
    foreach ($byDate as $date => $list) {
        $sets = [];   // id => bloco
        $seq = [];    // ordem dos ids
        $notes = $titles = [];
        foreach ($list as $i => $r) {
            $sets["m$i"] = ['distance' => $r['distance'], 'reps' => $r['reps'], 'rest' => $r['rest'], 'main' => true];
            $seq[] = "m$i";
            array_push($notes, ...$r['notes']);
            if ($r['title']) {
                $titles[] = $r['title'];
            }
        }
        foreach ($list as $i => $r) {
            $before = $after = [];
            foreach ($r['groups'] as $g) {
                // o mesmo bloco pode estar registado no separador da sua distância
                $id = null;
                foreach ($sets as $k => $s) {
                    if ($k !== "m$i" && $s['distance'] === $g['distance']) {
                        $id = $k;
                        break;
                    }
                }
                if ($id === null) {
                    $id = 'x' . count($sets);
                    $sets[$id] = ['distance' => $g['distance'], 'reps' => $g['reps'], 'rest' => $g['rest'], 'main' => false];
                }
                if ($g['when'] === 'before') {
                    $before[] = $id;
                } else {
                    $after[] = $id;
                }
            }
            if (!$before && !$after) {
                continue;
            }
            $seq = array_values(array_diff($seq, $before, $after));
            $at = array_search("m$i", $seq, true);
            array_splice($seq, $at + 1, 0, $after);
            array_splice($seq, $at, 0, $before);
        }

        $workouts[] = [
            'workout_date' => $date,
            'title'        => implode(', ', array_unique($titles)),
            'notes'        => implode("\n", array_unique($notes)),
            'sets'         => array_map(fn($id) => [
                'distance_m' => $sets[$id]['distance'],
                'rest'       => $sets[$id]['rest'] === null ? '' : (string)$sets[$id]['rest'],
                'rest_type'  => 'stand',
                'reps'       => array_map(fn($s) => number_format($s, 2, '.', ''), $sets[$id]['reps']),
            ], $seq),
        ];
    }
    return $workouts;
}

/** Avisos para tempos pouco prováveis (ex. formato de célula mal interpretado). */
function import_warnings(array $w): array
{
    $out = [];
    foreach ($w['sets'] as $s) {
        foreach ($s['reps'] as $sec) {
            $speed = $s['distance_m'] / (float)$sec;   // m/s
            if ($speed > 11 || $speed < 1.5) {
                $out[] = t('imp.warn_time', ['d' => fmt_distance($s['distance_m']), 't' => fmt_time((float)$sec)]);
                break;
            }
        }
    }
    return $out;
}

/** Lê o ficheiro enviado e prepara a pré-visualização. */
function import_prepare(int $userId, string $path): array
{
    $workouts = import_workouts(import_rows(xlsx_read($path)));
    $existing = array_flip(q('SELECT DISTINCT workout_date FROM workouts WHERE user_id = ?', [$userId])->fetchAll(PDO::FETCH_COLUMN));
    $items = [];
    foreach ($workouts as $w) {
        $check = workout_from_input($w);
        $items[] = [
            'input'    => $w,
            'exists'   => isset($existing[$w['workout_date']]),
            'errors'   => $check['errors'] ?? [],
            'warnings' => import_warnings($w),
        ];
    }
    return $items;
}
