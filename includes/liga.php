<?php
declare(strict_types=1);

// Catálogos del modo liga: usados en los formularios de admin/partido_eventos.php
// y para traducir cada evento a texto legible en la ficha pública del partido.
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
 * Describe un evento del partido en una línea legible ("34' Gol de penal - #10 Juan Pérez"),
 * reutilizado tanto en admin/partido_eventos.php como en la ficha pública partido.php.
 */
function evento_descripcion(array $evento, array $jugadoresPorId): string
{
    $minuto = $evento['minuto'] !== null ? $evento['minuto'] . "' " : '';
    $jugador = $jugadoresPorId[(int) ($evento['jugador_id'] ?? 0)] ?? null;
    $nombreJugador = jugador_nombre($jugador);

    switch ($evento['tipo']) {
        case 'gol':
            $tipoGol = TIPOS_GOL_LABEL[$evento['tipo_gol'] ?? ''] ?? '';
            $texto = $minuto . 'Gol' . ($tipoGol !== '' && $tipoGol !== 'Jugada' ? " ({$tipoGol})" : '') . ' - ' . $nombreJugador;
            $asistencia = $jugadoresPorId[(int) ($evento['asistencia_jugador_id'] ?? 0)] ?? null;
            if ($asistencia !== null) {
                $texto .= ' (asistencia de ' . jugador_nombre($asistencia) . ')';
            }
            return $texto;
        case 'amarilla':
            return $minuto . 'Tarjeta amarilla - ' . $nombreJugador;
        case 'roja':
            $motivo = MOTIVOS_ROJA_LABEL[$evento['motivo'] ?? ''] ?? '';
            return $minuto . 'Tarjeta roja' . ($motivo !== '' ? " ({$motivo})" : '') . ' - ' . $nombreJugador;
        case 'cambio':
            $entra = $jugadoresPorId[(int) ($evento['jugador_entra_id'] ?? 0)] ?? null;
            return $minuto . 'Cambio - Entra ' . jugador_nombre($entra) . ', sale ' . $nombreJugador;
        default:
            return $minuto . $nombreJugador;
    }
}
