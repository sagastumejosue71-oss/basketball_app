<?php
declare(strict_types=1);

// Catálogos del modo liga para fútbol. Se reutilizan las mismas columnas de
// partido_eventos ('gol'/'amarilla'/'roja'/'cambio') para basketball más abajo,
// cambiando solo los catálogos y las etiquetas según $torneo['deporte'].
const TIPOS_GOL_CATALOGO = ['jugada', 'penal', 'tiro_libre', 'autogol'];

const TIPOS_GOL_LABEL = [
    'jugada' => 'Jugada',
    'penal' => 'Penal',
    'tiro_libre' => 'Tiro libre',
    'autogol' => 'Autogol',
];

const MOTIVOS_ROJA_CATALOGO = ['directa', 'doble_amarilla'];

const MOTIVOS_ROJA_LABEL = [
    'directa' => 'Roja directa',
    'doble_amarilla' => 'Doble amarilla',
];

// Basketball: 'gol' se reutiliza como "anotación" (1/2/3 puntos), 'amarilla' como falta
// personal (acumulable, expulsa al llegar a 5 según reglas FIBA) y 'roja' como falta
// grave con motivo (técnica/antideportiva/descalificante), igual de estructura que fútbol.
const TIPOS_PUNTO_CATALOGO = ['libre', 'doble', 'triple'];

const TIPOS_PUNTO_LABEL = [
    'libre' => 'Tiro libre (1 pt)',
    'doble' => 'Canasta (2 pts)',
    'triple' => 'Triple (3 pts)',
];

const TIPOS_PUNTO_VALOR = [
    'libre' => 1,
    'doble' => 2,
    'triple' => 3,
];

const MOTIVOS_FALTA_CATALOGO = ['tecnica', 'antideportiva', 'descalificante'];

const MOTIVOS_FALTA_LABEL = [
    'tecnica' => 'Falta técnica',
    'antideportiva' => 'Falta antideportiva',
    'descalificante' => 'Falta descalificante',
];

// Regla FIBA (la más usada en ligas amateur): al llegar a esta cantidad de faltas
// personales en un mismo partido, el jugador queda expulsado del resto del encuentro.
const LIMITE_FALTAS_EXPULSION = 5;

function es_basketball(?string $deporte): bool
{
    return $deporte === 'basketball';
}

function tipos_anotacion_catalogo(?string $deporte): array
{
    return es_basketball($deporte) ? TIPOS_PUNTO_CATALOGO : TIPOS_GOL_CATALOGO;
}

function tipos_anotacion_label(?string $deporte): array
{
    return es_basketball($deporte) ? TIPOS_PUNTO_LABEL : TIPOS_GOL_LABEL;
}

function motivos_falta_grave_catalogo(?string $deporte): array
{
    return es_basketball($deporte) ? MOTIVOS_FALTA_CATALOGO : MOTIVOS_ROJA_CATALOGO;
}

function motivos_falta_grave_label(?string $deporte): array
{
    return es_basketball($deporte) ? MOTIVOS_FALTA_LABEL : MOTIVOS_ROJA_LABEL;
}

// Textos según deporte, usados en los encabezados de admin/partido_eventos.php, la
// ficha pública partido.php y la tabla de posiciones (columnas TA/TR).
function etiqueta_anotacion(?string $deporte): string
{
    return es_basketball($deporte) ? 'Punto' : 'Gol';
}

function etiqueta_anotaciones(?string $deporte): string
{
    return es_basketball($deporte) ? 'Puntos' : 'Goles';
}

function etiqueta_falta_leve(?string $deporte): string
{
    return es_basketball($deporte) ? 'Falta personal' : 'Tarjeta amarilla';
}

function etiqueta_faltas_leves(?string $deporte): string
{
    return es_basketball($deporte) ? 'Faltas personales' : 'Tarjetas amarillas';
}

function etiqueta_falta_grave(?string $deporte): string
{
    return es_basketball($deporte) ? 'Falta descalificante' : 'Tarjeta roja';
}

function etiqueta_faltas_graves(?string $deporte): string
{
    return es_basketball($deporte) ? 'Faltas descalificantes' : 'Tarjetas rojas';
}

function etiqueta_ta(?string $deporte): string
{
    return es_basketball($deporte) ? 'FP' : 'TA';
}

function etiqueta_tr(?string $deporte): string
{
    return es_basketball($deporte) ? 'FD' : 'TR';
}

/**
 * Agrupa la plantilla de jugadores por equipo_id, para llenar los <select> de un
 * partido puntual solo con los jugadores de los dos equipos que lo disputan.
 */
function jugadores_por_equipo(array $jugadores): array
{
    $porEquipo = [];
    foreach ($jugadores as $j) {
        $porEquipo[(int) $j['equipo_id']][] = $j;
    }
    return $porEquipo;
}

/**
 * Indexa la plantilla por id, para resolver rápido "jugador_id" -> nombre al describir eventos.
 */
function jugadores_por_id(array $jugadores): array
{
    $porId = [];
    foreach ($jugadores as $j) {
        $porId[(int) $j['id']] = $j;
    }
    return $porId;
}

function jugador_nombre(?array $jugador): string
{
    if ($jugador === null) {
        return 'Jugador no registrado';
    }
    return '#' . $jugador['dorsal'] . ' ' . $jugador['nombre'];
}

/**
 * Cuenta las faltas personales ('amarilla') acumuladas por cada jugador en la lista de
 * eventos dada (normalmente los de un solo partido), para detectar quién llegó al límite
 * de expulsión por faltas (LIMITE_FALTAS_EXPULSION). Solo tiene sentido en basketball,
 * pero funciona igual para cualquier deporte porque solo cuenta ocurrencias.
 */
function faltas_por_jugador(array $eventos): array
{
    $conteo = [];
    foreach ($eventos as $evento) {
        if ($evento['tipo'] !== 'amarilla') {
            continue;
        }
        $jugadorId = (int) ($evento['jugador_id'] ?? 0);
        if ($jugadorId === 0) {
            continue;
        }
        $conteo[$jugadorId] = ($conteo[$jugadorId] ?? 0) + 1;
    }
    return $conteo;
}

/**
 * Describe un evento del partido en una línea legible ("34' Gol de penal - #10 Juan Pérez"
 * en fútbol, "3er cuarto Canasta (2 pts) - #10 Juan Pérez" en basketball), reutilizado
 * tanto en admin/partido_eventos.php como en la ficha pública partido.php.
 */
function evento_descripcion(array $evento, array $jugadoresPorId, ?string $deporte = null): string
{
    $basketball = es_basketball($deporte);
    $minuto = $evento['minuto'] !== null ? $evento['minuto'] . "' " : '';
    $jugador = $jugadoresPorId[(int) ($evento['jugador_id'] ?? 0)] ?? null;
    $nombreJugador = jugador_nombre($jugador);

    switch ($evento['tipo']) {
        case 'gol':
            $tipoLabel = tipos_anotacion_label($deporte)[$evento['tipo_gol'] ?? ''] ?? '';
            if ($basketball) {
                // El label del tipo de canasta ya se lee bien solo ("Triple (3 pts)"), a
                // diferencia de fútbol donde "Gol" es el sustantivo y el tipo es un extra.
                $texto = $minuto . ($tipoLabel !== '' ? $tipoLabel : 'Punto') . ' - ' . $nombreJugador;
            } else {
                // "Jugada" es el caso por defecto en fútbol y no aporta nada al texto.
                $mostrarTipo = $tipoLabel !== '' && $tipoLabel !== 'Jugada';
                $texto = $minuto . 'Gol' . ($mostrarTipo ? " ({$tipoLabel})" : '') . ' - ' . $nombreJugador;
            }
            $asistencia = $jugadoresPorId[(int) ($evento['asistencia_jugador_id'] ?? 0)] ?? null;
            if ($asistencia !== null) {
                $texto .= ' (asistencia de ' . jugador_nombre($asistencia) . ')';
            }
            return $texto;
        case 'amarilla':
            return $minuto . etiqueta_falta_leve($deporte) . ' - ' . $nombreJugador;
        case 'roja':
            $catalogoMotivo = motivos_falta_grave_label($deporte);
            $motivo = $catalogoMotivo[$evento['motivo'] ?? ''] ?? '';
            return $minuto . etiqueta_falta_grave($deporte) . ($motivo !== '' ? " ({$motivo})" : '') . ' - ' . $nombreJugador;
        case 'cambio':
            $entra = $jugadoresPorId[(int) ($evento['jugador_entra_id'] ?? 0)] ?? null;
            return $minuto . 'Cambio - Entra ' . jugador_nombre($entra) . ', sale ' . $nombreJugador;
        default:
            return $minuto . $nombreJugador;
    }
}

/**
 * Ranking de máximos anotadores a partir de los eventos de todos los partidos de la
 * copa/liga. En fútbol cuenta goles (los autogoles no suman al goleador, van a favor
 * del marcador del rival pero no son "su" gol). En basketball suma el valor real de
 * cada anotación (1/2/3 puntos) en vez de solo contar ocurrencias.
 *
 * @return array<int, array{jugador: array, equipo: ?array, goles: int}> Ordenado de más a menos.
 */
function calcular_goleadores(array $eventos, array $jugadores, array $equiposPorId, ?string $deporte = null): array
{
    $basketball = es_basketball($deporte);
    $jugadoresPorId = jugadores_por_id($jugadores);

    $conteo = [];
    foreach ($eventos as $evento) {
        if ($evento['tipo'] !== 'gol') {
            continue;
        }
        if (!$basketball && ($evento['tipo_gol'] ?? '') === 'autogol') {
            continue;
        }
        $jugadorId = (int) ($evento['jugador_id'] ?? 0);
        if (!isset($jugadoresPorId[$jugadorId])) {
            continue;
        }
        $valor = $basketball ? (TIPOS_PUNTO_VALOR[$evento['tipo_gol'] ?? ''] ?? 1) : 1;
        $conteo[$jugadorId] = ($conteo[$jugadorId] ?? 0) + $valor;
    }

    $goleadores = [];
    foreach ($conteo as $jugadorId => $goles) {
        $jugador = $jugadoresPorId[$jugadorId];
        $goleadores[] = [
            'jugador' => $jugador,
            'equipo' => $equiposPorId[(int) $jugador['equipo_id']] ?? null,
            'goles' => $goles,
        ];
    }

    usort($goleadores, fn($a, $b) => $b['goles'] <=> $a['goles']);
    return $goleadores;
}
