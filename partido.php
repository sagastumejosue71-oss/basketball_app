<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/tabla.php';
require_once __DIR__ . '/includes/liga.php';
require_once __DIR__ . '/includes/torneo_actual.php';

$id = (int) ($_GET['id'] ?? 0);
$partidos = db_leer('partidos', $torneo['id']);
$partido = db_buscar_por_id($partidos, $id);

$equipos = db_leer('equipos', $torneo['id']);
$equiposPorId = [];
foreach ($equipos as $eq) {
    $equiposPorId[$eq['id']] = $eq;
}

$local = $partido ? ($equiposPorId[$partido['equipo_local']] ?? null) : null;
$visit = $partido ? ($equiposPorId[$partido['equipo_visitante']] ?? null) : null;

// La ficha de partido solo existe en modo liga; en modo copa (o partido/equipo inválido) es un 404.
if (($torneo['modo'] ?? 'copa') !== 'liga' || !$partido || !$local || !$visit) {
    http_response_code(404);
    $titulo_pagina = 'Partido no encontrado';
    require __DIR__ . '/includes/layout_top.php';
    echo '<div class="container seccion text-center"><h1>Partido no encontrado</h1><a href="' . url_copa('calendario.php') . '" class="btn btn-degradado rounded-pill mt-3">Volver al calendario</a></div>';
    require __DIR__ . '/includes/layout_bottom.php';
    exit;
}

$jugado = $partido['estado'] === 'jugado';

$jugadoresTodos = db_leer('jugadores', $torneo['id']);
$jugadoresPorId = jugadores_por_id($jugadoresTodos);

$eventos = db_leer_eventos_partido($torneo['id'], $id);
$goles = array_values(array_filter($eventos, fn($e) => $e['tipo'] === 'gol'));
$amarillas = array_values(array_filter($eventos, fn($e) => $e['tipo'] === 'amarilla'));
$rojas = array_values(array_filter($eventos, fn($e) => $e['tipo'] === 'roja'));
$cambios = array_values(array_filter($eventos, fn($e) => $e['tipo'] === 'cambio'));

$titulo_pagina = $local['nombre'] . ' vs ' . $visit['nombre'] . ' — ' . $torneo['nombre'];
$pagina_activa = 'calendario';
require __DIR__ . '/includes/layout_top.php';
?>

<header class="hero-copa" style="padding-bottom:3rem;">
    <div class="container">
        <p class="kicker mb-2"><i class="bi bi-calendar3 me-1"></i><?= e(formatear_fecha_larga($partido['fecha'])) ?> · <?= e($partido['hora']) ?></p>
        <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap text-center">
            <a href="<?= url_copa('equipo.php?id=' . $local['id']) ?>" class="d-flex flex-column align-items-center gap-2 text-decoration-none text-white" style="width:40%;">
                <?= logo_equipo($local, 72) ?>
                <span class="fw-bold"><?= e($local['nombre']) ?></span>
            </a>
            <div class="fs-1 fw-bold text-white">
                <?php if ($jugado): ?>
                    <?= (int) $partido['marcador_local'] ?> - <?= (int) $partido['marcador_visitante'] ?>
                <?php else: ?>
                    VS
                <?php endif; ?>
            </div>
            <a href="<?= url_copa('equipo.php?id=' . $visit['id']) ?>" class="d-flex flex-column align-items-center gap-2 text-decoration-none text-white" style="width:40%;">
                <?= logo_equipo($visit, 72) ?>
                <span class="fw-bold"><?= e($visit['nombre']) ?></span>
            </a>
        </div>
        <p class="text-center mt-3 mb-0" style="color:rgba(255,255,255,.75);">
            <i class="bi bi-geo-alt me-1"></i><?= e($partido['cancha']) ?>
            <?php if (!empty($partido['arbitro'])): ?> · <i class="bi bi-person-badge me-1"></i>Árbitro: <?= e($partido['arbitro']) ?><?php endif; ?>
        </p>
        <div class="text-center mt-3">
            <button type="button" class="btn btn-outline-luz btn-sm rounded-pill px-3 btn-imprimir-pdf"><i class="bi bi-download me-1"></i>Descargar PDF</button>
        </div>
    </div>
</header>

<section class="seccion pt-4">
    <div class="container" style="max-width:760px;">
        <?php if (!$jugado): ?>
            <div class="card-suave p-4 text-center text-muted">
                <i class="bi bi-clock-history fs-3 d-block mb-2 opacity-50"></i>
                Este partido todavía no se ha jugado.
            </div>
        <?php elseif (empty($eventos)): ?>
            <div class="card-suave p-4 text-center text-muted">
                <i class="bi bi-clipboard-data fs-3 d-block mb-2 opacity-50"></i>
                Todavía no se ha cargado la ficha de este partido.
            </div>
        <?php else: ?>

            <?php if (!empty($goles)): ?>
            <div class="card-suave p-4 mb-3">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">⚽ Goles</h6>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($goles as $ev): ?>
                    <li class="mb-2 small"><span class="fw-semibold"><?= e($equiposPorId[$ev['equipo_id']]['nombre'] ?? '') ?>:</span> <?= e(evento_descripcion($ev, $jugadoresPorId)) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($amarillas)): ?>
            <div class="card-suave p-4 mb-3">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">🟨 Tarjetas amarillas</h6>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($amarillas as $ev): ?>
                    <li class="mb-2 small"><span class="fw-semibold"><?= e($equiposPorId[$ev['equipo_id']]['nombre'] ?? '') ?>:</span> <?= e(evento_descripcion($ev, $jugadoresPorId)) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($rojas)): ?>
            <div class="card-suave p-4 mb-3">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">🟥 Tarjetas rojas</h6>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($rojas as $ev): ?>
                    <li class="mb-2 small"><span class="fw-semibold"><?= e($equiposPorId[$ev['equipo_id']]['nombre'] ?? '') ?>:</span> <?= e(evento_descripcion($ev, $jugadoresPorId)) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($cambios)): ?>
            <div class="card-suave p-4 mb-3">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">🔄 Cambios</h6>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($cambios as $ev): ?>
                    <li class="mb-2 small"><span class="fw-semibold"><?= e($equiposPorId[$ev['equipo_id']]['nombre'] ?? '') ?>:</span> <?= e(evento_descripcion($ev, $jugadoresPorId)) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

        <?php endif; ?>

        <?php if (!empty($partido['observaciones'])): ?>
        <div class="card-suave p-4">
            <h6 class="text-uppercase small fw-bold text-muted mb-2">Observaciones</h6>
            <p class="mb-0 small"><?= nl2br(e($partido['observaciones'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
