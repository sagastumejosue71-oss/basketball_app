<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/liga.php';

auth_requerir();
$torneo = admin_requerir_torneo_activo();

$partidoId = (int) ($_GET['partido_id'] ?? $_POST['partido_id'] ?? 0);
$partidos = db_leer('partidos', $torneo['id']);
$partido = db_buscar_por_id($partidos, $partidoId);
if ($partido === null) {
    http_response_code(404);
    exit('Encuentro no encontrado.');
}

$equipos = db_leer('equipos', $torneo['id']);
$equiposPorId = [];
foreach ($equipos as $eq) { $equiposPorId[$eq['id']] = $eq; }
$equipoLocal = $equiposPorId[(int) $partido['equipo_local']] ?? null;
$equipoVisitante = $equiposPorId[(int) $partido['equipo_visitante']] ?? null;
$equiposDelPartido = array_filter([(int) $partido['equipo_local'], (int) $partido['equipo_visitante']]);

$jugadoresTodos = db_leer('jugadores', $torneo['id']);
$jugadoresPorEquipo = jugadores_por_equipo($jugadoresTodos);
$jugadoresPorId = jugadores_por_id($jugadoresTodos);
$etJugador = forma_genero($torneo['genero'] ?? null, 'Jugador', 'Jugadora');
$deporte = $torneo['deporte'] ?? null;
$basketball = es_basketball($deporte);

$urlLista = url('admin/partido_eventos.php?partido_id=' . $partidoId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'eliminar_evento') {
        $id = (int) $_POST['id'];
        $eventos = db_leer_eventos_partido($torneo['id'], $partidoId);
        $eventos = array_values(array_filter($eventos, fn($ev) => (int) $ev['id'] !== $id));
        db_guardar_eventos_partido($torneo['id'], $partidoId, $eventos);
        redirigir_con_mensaje($urlLista, 'success', 'Evento eliminado.');
    }

    if (in_array($accion, ['agregar_gol', 'agregar_tarjeta', 'agregar_cambio'], true)) {
        $equipoId = (int) ($_POST['equipo_id'] ?? 0);
        if (!in_array($equipoId, $equiposDelPartido, true)) {
            redirigir_con_mensaje($urlLista, 'error', 'Selecciona un equipo válido para este encuentro.');
        }
        $rosterEquipo = array_column($jugadoresPorEquipo[$equipoId] ?? [], 'id');
        $minuto = ($_POST['minuto'] ?? '') !== '' ? (int) $_POST['minuto'] : null;

        $evento = [
            'equipo_id' => $equipoId,
            'jugador_id' => null,
            'jugador_entra_id' => null,
            'minuto' => $minuto,
            'tipo_gol' => null,
            'asistencia_jugador_id' => null,
            'motivo' => null,
        ];

        if ($accion === 'agregar_gol') {
            $jugadorId = (int) ($_POST['jugador_id'] ?? 0);
            if (!in_array($jugadorId, $rosterEquipo, true)) {
                redirigir_con_mensaje($urlLista, 'error', forma_genero($torneo['genero'] ?? null, 'Selecciona un jugador válido de ese equipo.', 'Selecciona una jugadora válida de ese equipo.'));
            }
            $catalogoTipoGol = tipos_anotacion_catalogo($deporte);
            $tipoGolDefault = $catalogoTipoGol[0];
            $tipoGol = (string) ($_POST['tipo_gol'] ?? $tipoGolDefault);
            $asistenciaId = (int) ($_POST['asistencia_jugador_id'] ?? 0);

            $evento['tipo'] = 'gol';
            $evento['jugador_id'] = $jugadorId;
            $evento['tipo_gol'] = in_array($tipoGol, $catalogoTipoGol, true) ? $tipoGol : $tipoGolDefault;
            $evento['asistencia_jugador_id'] = in_array($asistenciaId, $rosterEquipo, true) ? $asistenciaId : null;
        }

        if ($accion === 'agregar_tarjeta') {
            $jugadorId = (int) ($_POST['jugador_id'] ?? 0);
            if (!in_array($jugadorId, $rosterEquipo, true)) {
                redirigir_con_mensaje($urlLista, 'error', forma_genero($torneo['genero'] ?? null, 'Selecciona un jugador válido de ese equipo.', 'Selecciona una jugadora válida de ese equipo.'));
            }
            $color = (string) ($_POST['color'] ?? 'amarilla') === 'roja' ? 'roja' : 'amarilla';
            $catalogoMotivo = motivos_falta_grave_catalogo($deporte);
            $motivoDefault = $catalogoMotivo[0];
            $motivo = (string) ($_POST['motivo'] ?? $motivoDefault);

            $evento['tipo'] = $color;
            $evento['jugador_id'] = $jugadorId;
            $evento['motivo'] = $color === 'roja' ? (in_array($motivo, $catalogoMotivo, true) ? $motivo : $motivoDefault) : null;
        }

        if ($accion === 'agregar_cambio') {
            $jugadorSaleId = (int) ($_POST['jugador_id'] ?? 0);
            $jugadorEntraId = (int) ($_POST['jugador_entra_id'] ?? 0);
            if (!in_array($jugadorSaleId, $rosterEquipo, true) || !in_array($jugadorEntraId, $rosterEquipo, true)) {
                redirigir_con_mensaje($urlLista, 'error', forma_genero($torneo['genero'] ?? null, 'Selecciona jugadores válidos de ese equipo.', 'Selecciona jugadoras válidas de ese equipo.'));
            }
            if ($jugadorSaleId === $jugadorEntraId) {
                redirigir_con_mensaje($urlLista, 'error', forma_genero($torneo['genero'] ?? null, 'El jugador que entra y el que sale no pueden ser el mismo.', 'La jugadora que entra y la que sale no pueden ser la misma.'));
            }

            $evento['tipo'] = 'cambio';
            $evento['jugador_id'] = $jugadorSaleId;
            $evento['jugador_entra_id'] = $jugadorEntraId;
        }

        $eventos = db_leer_eventos_partido($torneo['id'], $partidoId);
        $evento['id'] = db_siguiente_id_global('partido_eventos');
        $eventos[] = $evento;
        db_guardar_eventos_partido($torneo['id'], $partidoId, $eventos);
        redirigir_con_mensaje($urlLista, 'success', 'Evento agregado.');
    }
}

$eventos = db_leer_eventos_partido($torneo['id'], $partidoId);
usort($eventos, fn($a, $b) => ($a['minuto'] ?? 999) <=> ($b['minuto'] ?? 999));

$seccion_activa = 'partidos';
$titulo_pagina = 'Ficha del partido';
require __DIR__ . '/includes/admin_layout_top.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= url('admin/partidos.php') ?>" class="btn btn-sm btn-outline-secondary rounded-circle"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h3 class="mb-0">Ficha del partido</h3>
        <div class="small text-muted"><?= $equipoLocal ? e($equipoLocal['nombre']) : '?' ?> vs <?= $equipoVisitante ? e($equipoVisitante['nombre']) : '?' ?> · <?= e(formatear_fecha_larga($partido['fecha'])) ?></div>
    </div>
    <a href="<?= e(url_copa('partido.php?id=' . $partidoId . '&imprimir=1')) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Descargar PDF</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card-suave p-4 mb-3">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="bi bi-dribbble me-1"></i>Agregar <?= e(mb_strtolower(etiqueta_anotacion($deporte))) ?></h6>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="agregar_gol">
                <input type="hidden" name="partido_id" value="<?= $partidoId ?>">
                <div class="col-12">
                    <select name="equipo_id" class="form-select form-select-sm" required>
                        <option value="">Equipo...</option>
                        <?php if ($equipoLocal): ?><option value="<?= $equipoLocal['id'] ?>"><?= e($equipoLocal['nombre']) ?></option><?php endif; ?>
                        <?php if ($equipoVisitante): ?><option value="<?= $equipoVisitante['id'] ?>"><?= e($equipoVisitante['nombre']) ?></option><?php endif; ?>
                    </select>
                </div>
                <div class="col-8">
                    <select name="jugador_id" class="form-select form-select-sm" data-filtra-jugador required>
                        <option value=""><?= e($etJugador) ?> que anota...</option>
                        <?php foreach ($equiposDelPartido as $eid): foreach ($jugadoresPorEquipo[$eid] ?? [] as $j): ?>
                        <option value="<?= $j['id'] ?>" data-equipo="<?= $eid ?>">#<?= e($j['dorsal']) ?> <?= e($j['nombre']) ?></option>
                        <?php endforeach; endforeach; ?>
                    </select>
                </div>
                <div class="col-4">
                    <input type="number" min="0" name="minuto" class="form-control form-control-sm" placeholder="Min.">
                </div>
                <div class="col-8">
                    <select name="tipo_gol" class="form-select form-select-sm">
                        <?php foreach (tipos_anotacion_catalogo($deporte) as $tg): ?>
                        <option value="<?= e($tg) ?>"><?= e(tipos_anotacion_label($deporte)[$tg]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <select name="asistencia_jugador_id" class="form-select form-select-sm" data-filtra-jugador>
                        <option value="">Sin asistencia</option>
                        <?php foreach ($equiposDelPartido as $eid): foreach ($jugadoresPorEquipo[$eid] ?? [] as $j): ?>
                        <option value="<?= $j['id'] ?>" data-equipo="<?= $eid ?>">#<?= e($j['dorsal']) ?> <?= e($j['nombre']) ?></option>
                        <?php endforeach; endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-degradado rounded-pill px-3 w-100">Agregar <?= e(mb_strtolower(etiqueta_anotacion($deporte))) ?></button>
                </div>
            </form>
        </div>

        <div class="card-suave p-4 mb-3">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="bi bi-square-fill text-warning"></i><i class="bi bi-square-fill text-danger me-1"></i>Agregar <?= $basketball ? 'falta' : 'tarjeta' ?> (<?= e(mb_strtolower(etiqueta_falta_leve($deporte))) ?> o <?= e(mb_strtolower(etiqueta_falta_grave($deporte))) ?>)</h6>
            <?php if ($basketball): ?>
            <p class="small text-muted mb-2">Al llegar a <?= LIMITE_FALTAS_EXPULSION ?> faltas personales en el partido, el jugador queda expulsado automáticamente (regla FIBA).</p>
            <?php endif; ?>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="agregar_tarjeta">
                <input type="hidden" name="partido_id" value="<?= $partidoId ?>">
                <div class="col-12">
                    <select name="equipo_id" class="form-select form-select-sm" required>
                        <option value="">Equipo...</option>
                        <?php if ($equipoLocal): ?><option value="<?= $equipoLocal['id'] ?>"><?= e($equipoLocal['nombre']) ?></option><?php endif; ?>
                        <?php if ($equipoVisitante): ?><option value="<?= $equipoVisitante['id'] ?>"><?= e($equipoVisitante['nombre']) ?></option><?php endif; ?>
                    </select>
                </div>
                <div class="col-8">
                    <select name="jugador_id" class="form-select form-select-sm" data-filtra-jugador required>
                        <option value=""><?= e($etJugador) ?>...</option>
                        <?php foreach ($equiposDelPartido as $eid): foreach ($jugadoresPorEquipo[$eid] ?? [] as $j): ?>
                        <option value="<?= $j['id'] ?>" data-equipo="<?= $eid ?>">#<?= e($j['dorsal']) ?> <?= e($j['nombre']) ?></option>
                        <?php endforeach; endforeach; ?>
                    </select>
                </div>
                <div class="col-4">
                    <input type="number" min="0" name="minuto" class="form-control form-control-sm" placeholder="Min.">
                </div>
                <div class="col-6">
                    <select name="color" class="form-select form-select-sm">
                        <option value="amarilla"><?= e(etiqueta_falta_leve($deporte)) ?></option>
                        <option value="roja"><?= e(etiqueta_falta_grave($deporte)) ?></option>
                    </select>
                </div>
                <div class="col-6">
                    <select name="motivo" class="form-select form-select-sm">
                        <?php foreach (motivos_falta_grave_catalogo($deporte) as $mr): ?>
                        <option value="<?= e($mr) ?>"><?= e(motivos_falta_grave_label($deporte)[$mr]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Solo aplica si es <?= e(mb_strtolower(etiqueta_falta_grave($deporte))) ?>.</div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-degradado rounded-pill px-3 w-100">Agregar <?= $basketball ? 'falta' : 'tarjeta' ?></button>
                </div>
            </form>
        </div>

        <div class="card-suave p-4">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="bi bi-arrow-left-right me-1"></i>Agregar cambio</h6>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="agregar_cambio">
                <input type="hidden" name="partido_id" value="<?= $partidoId ?>">
                <div class="col-12">
                    <select name="equipo_id" class="form-select form-select-sm" required>
                        <option value="">Equipo...</option>
                        <?php if ($equipoLocal): ?><option value="<?= $equipoLocal['id'] ?>"><?= e($equipoLocal['nombre']) ?></option><?php endif; ?>
                        <?php if ($equipoVisitante): ?><option value="<?= $equipoVisitante['id'] ?>"><?= e($equipoVisitante['nombre']) ?></option><?php endif; ?>
                    </select>
                </div>
                <div class="col-6">
                    <select name="jugador_id" class="form-select form-select-sm" data-filtra-jugador required>
                        <option value="">Sale...</option>
                        <?php foreach ($equiposDelPartido as $eid): foreach ($jugadoresPorEquipo[$eid] ?? [] as $j): ?>
                        <option value="<?= $j['id'] ?>" data-equipo="<?= $eid ?>">#<?= e($j['dorsal']) ?> <?= e($j['nombre']) ?></option>
                        <?php endforeach; endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <select name="jugador_entra_id" class="form-select form-select-sm" data-filtra-jugador required>
                        <option value="">Entra...</option>
                        <?php foreach ($equiposDelPartido as $eid): foreach ($jugadoresPorEquipo[$eid] ?? [] as $j): ?>
                        <option value="<?= $j['id'] ?>" data-equipo="<?= $eid ?>">#<?= e($j['dorsal']) ?> <?= e($j['nombre']) ?></option>
                        <?php endforeach; endforeach; ?>
                    </select>
                </div>
                <div class="col-4">
                    <input type="number" min="0" name="minuto" class="form-control form-control-sm" placeholder="Min.">
                </div>
                <div class="col-8 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-degradado rounded-pill px-3 w-100">Agregar cambio</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <?php if ($basketball): ?>
        <?php $faltasPorJugador = faltas_por_jugador($eventos); ?>
        <?php $expulsados = array_filter($faltasPorJugador, fn($n) => $n >= LIMITE_FALTAS_EXPULSION); ?>
        <?php if (!empty($expulsados)): ?>
        <div class="card-suave p-3 mb-3 border border-danger-subtle">
            <h6 class="text-uppercase small fw-bold text-danger mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Expulsados por faltas</h6>
            <ul class="list-unstyled mb-0 small">
                <?php foreach ($expulsados as $jid => $n): $j = $jugadoresPorId[$jid] ?? null; ?>
                <li><?= e(jugador_nombre($j)) ?> — <?= $n ?> faltas personales</li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <div class="card-suave p-4">
            <h6 class="text-uppercase small fw-bold text-muted mb-3">Eventos cargados (<?= count($eventos) ?>)</h6>
            <?php if (empty($eventos)): ?>
                <p class="text-muted small mb-0">Todavía no hay <?= $basketball ? 'puntos, faltas' : 'goles, tarjetas' ?> ni cambios cargados en este partido.</p>
            <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php $iconosEvento = ['gol' => $basketball ? '🏀' : '⚽', 'amarilla' => '🟨', 'roja' => '🟥', 'cambio' => '🔄']; ?>
                <?php foreach ($eventos as $ev): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span class="small"><?= $iconosEvento[$ev['tipo']] ?? '' ?> <?= e(evento_descripcion($ev, $jugadoresPorId, $deporte)) ?> <span class="text-muted">— <?= e($equiposPorId[$ev['equipo_id']]['nombre'] ?? '') ?></span></span>
                    <form method="post" data-confirm="¿Eliminar este evento?">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="accion" value="eliminar_evento">
                        <input type="hidden" name="partido_id" value="<?= $partidoId ?>">
                        <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x-lg"></i></button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/admin_layout_bottom.php'; ?>
