<?php
declare(strict_types=1);

/**
 * Calcula la tabla de posiciones a partir de los equipos y los partidos jugados.
 * Orden: Puntos de tabla (2 x victoria + 1 x derrota jugada) > Diferencia de puntos > Puntos a favor.
 *
 * @return array<int, array> Tabla ordenada, cada fila incluye los datos del equipo + estadísticas + posición.
 */
function calcular_tabla(array $equipos, array $partidos): array
{
    $stats = [];
    foreach ($equipos as $equipo) {
        $stats[$equipo['id']] = [
            'equipo' => $equipo,
            'pj' => 0,
            'pg' => 0,
            'pp' => 0,
            'pf' => 0,
            'pc' => 0,
            'racha' => [],
        ];
    }

    // Ordenar partidos por fecha para poder construir la racha reciente en orden cronológico
    $jugados = array_filter($partidos, fn($p) => ($p['estado'] ?? '') === 'jugado'
        && $p['marcador_local'] !== null && $p['marcador_visitante'] !== null);
    usort($jugados, fn($a, $b) => strcmp($a['fecha'] . $a['hora'], $b['fecha'] . $b['hora']));

    foreach ($jugados as $partido) {
        $localId = (int) $partido['equipo_local'];
        $visitId = (int) $partido['equipo_visitante'];
        if (!isset($stats[$localId]) || !isset($stats[$visitId])) {
            continue;
        }
        $pl = (int) $partido['marcador_local'];
        $pv = (int) $partido['marcador_visitante'];

        $stats[$localId]['pj']++;
        $stats[$visitId]['pj']++;
        $stats[$localId]['pf'] += $pl;
        $stats[$localId]['pc'] += $pv;
        $stats[$visitId]['pf'] += $pv;
        $stats[$visitId]['pc'] += $pl;

        if ($pl > $pv) {
            $stats[$localId]['pg']++;
            $stats[$visitId]['pp']++;
            $stats[$localId]['racha'][] = 'G';
            $stats[$visitId]['racha'][] = 'P';
        } else {
            $stats[$visitId]['pg']++;
            $stats[$localId]['pp']++;
            $stats[$visitId]['racha'][] = 'G';
            $stats[$localId]['racha'][] = 'P';
        }
    }

    $tabla = [];
    foreach ($stats as $fila) {
        $fila['dif'] = $fila['pf'] - $fila['pc'];
        $fila['pts'] = $fila['pg'] * 2 + $fila['pp'] * 1;
        $fila['porcentaje'] = $fila['pj'] > 0 ? round(($fila['pg'] / $fila['pj']) * 100) : 0;
        $fila['racha'] = array_slice(array_reverse($fila['racha']), 0, 5);
        $tabla[] = $fila;
    }

    usort($tabla, function ($a, $b) {
        if ($a['pts'] !== $b['pts']) {
            return $b['pts'] <=> $a['pts'];
        }
        if ($a['dif'] !== $b['dif']) {
            return $b['dif'] <=> $a['dif'];
        }
        if ($a['pf'] !== $b['pf']) {
            return $b['pf'] <=> $a['pf'];
        }
        return strcmp($a['equipo']['nombre'], $b['equipo']['nombre']);
    });

    foreach ($tabla as $i => &$fila) {
        $fila['posicion'] = $i + 1;
    }
    unset($fila);

    return $tabla;
}

/**
 * Devuelve los próximos N partidos programados (por fecha) y los últimos N resultados jugados.
 */
function proximos_partidos(array $partidos, int $limite = 4): array
{
    $prog = array_filter($partidos, fn($p) => ($p['estado'] ?? '') === 'programado');
    usort($prog, fn($a, $b) => strcmp($a['fecha'] . $a['hora'], $b['fecha'] . $b['hora']));
    return array_slice($prog, 0, $limite);
}

function ultimos_resultados(array $partidos, int $limite = 4): array
{
    $jugados = array_filter($partidos, fn($p) => ($p['estado'] ?? '') === 'jugado');
    usort($jugados, fn($a, $b) => strcmp($b['fecha'] . $b['hora'], $a['fecha'] . $a['hora']));
    return array_slice($jugados, 0, $limite);
}

function partidos_por_jornada(array $partidos): array
{
    $jornadas = [];
    foreach ($partidos as $p) {
        $j = (int) $p['jornada'];
        $jornadas[$j][] = $p;
    }
    ksort($jornadas);
    foreach ($jornadas as &$lista) {
        usort($lista, fn($a, $b) => strcmp($a['fecha'] . $a['hora'], $b['fecha'] . $b['hora']));
    }
    unset($lista);
    return $jornadas;
}
