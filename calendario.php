<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/tabla.php';

$torneo = db_leer('torneo');
$equipos = db_leer('equipos');
$partidos = db_leer('partidos');
$equiposPorId = [];
foreach ($equipos as $eq) {
    $equiposPorId[$eq['id']] = $eq;
}

$jornadas = partidos_por_jornada($partidos);
$jornadaSeleccionada = isset($_GET['jornada']) ? (int) $_GET['jornada'] : 0;
if ($jornadaSeleccionada === 0 || !isset($jornadas[$jornadaSeleccionada])) {
    // Por defecto muestra la primera jornada con partidos pendientes, si no, la última
    $jornadaSeleccionada = null;
    foreach ($jornadas as $num => $lista) {
        $tienePendientes = count(array_filter($lista, fn($p) => $p['estado'] === 'programado'));
        if ($tienePendientes > 0) {
            $jornadaSeleccionada = $num;
            break;
        }
    }
    if ($jornadaSeleccionada === null) {
        $jornadaSeleccionada = max(array_keys($jornadas));
    }
}

$titulo_pagina = 'Calendario — ' . $torneo['nombre'];
$pagina_activa = 'calendario';
require __DIR__ . '/includes/layout_top.php';
?>

<header class="hero-copa" style="padding-bottom:3.5rem;">
    <div class="container">
        <p class="kicker mb-2"><i class="bi bi-calendar-week me-1"></i>Temporada <?= e($torneo['temporada']) ?></p>
        <h1 class="text-white mb-2">Calendario de <span class="text-degradado">Encuentros</span></h1>
        <p style="color:rgba(255,255,255,.75);" class="mb-0">Todos los partidos de la temporada regular, jornada por jornada.</p>
    </div>
</header>

<section class="seccion pt-5">
    <div class="container">
        <div class="d-flex flex-wrap gap-2 mb-4 justify-content-center">
            <?php foreach (array_keys($jornadas) as $num): ?>
            <a href="<?= url('calendario.php?jornada=' . $num) ?>" class="btn btn-sm rounded-pill px-3 <?= $num === $jornadaSeleccionada ? 'btn-degradado' : 'btn-outline-secondary' ?>">Jornada <?= $num ?></a>
            <?php endforeach; ?>
        </div>

        <div class="row row-cols-1 row-cols-lg-2 g-3">
            <?php foreach ($jornadas[$jornadaSeleccionada] as $p): $local = $equiposPorId[$p['equipo_local']]; $visit = $equiposPorId[$p['equipo_visitante']]; $jugado = $p['estado'] === 'jugado'; ?>
            <div class="col">
                <div class="partido-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?= formatear_fecha_larga($p['fecha']) ?> · <?= e($p['hora']) ?></span>
                        <?php if ($jugado): ?>
                            <span class="badge badge-estado-jugado rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i>Finalizado</span>
                        <?php else: ?>
                            <span class="badge badge-estado-programado rounded-pill px-3 py-2"><i class="bi bi-clock me-1"></i>Programado</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <a href="<?= url('equipo.php?id=' . $local['id']) ?>" class="equipo-col text-decoration-none text-dark">
                            <?= logo_equipo($local, 56) ?>
                            <span class="nombre <?= $jugado && $p['marcador_local'] > $p['marcador_visitante'] ? 'text-success' : '' ?>"><?= e($local['nombre']) ?></span>
                        </a>
                        <div class="marcador text-center">
                            <?php if ($jugado): ?>
                                <?= $p['marcador_local'] ?> - <?= $p['marcador_visitante'] ?>
                            <?php else: ?>
                                <span class="text-muted fs-5">VS</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= url('equipo.php?id=' . $visit['id']) ?>" class="equipo-col text-decoration-none text-dark">
                            <?= logo_equipo($visit, 56) ?>
                            <span class="nombre <?= $jugado && $p['marcador_visitante'] > $p['marcador_local'] ? 'text-success' : '' ?>"><?= e($visit['nombre']) ?></span>
                        </a>
                    </div>
                    <p class="text-center small text-muted mt-2 mb-0"><i class="bi bi-geo-alt me-1"></i><?= e($p['cancha']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
