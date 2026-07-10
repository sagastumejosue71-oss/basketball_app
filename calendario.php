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
$playoffsPorFase = partidos_playoffs_por_fase($partidos);
$fasesValidas = array_merge(['grupos'], FASES_PLAYOFF);

$faseSeleccionada = $_GET['fase'] ?? 'grupos';
if (!in_array($faseSeleccionada, $fasesValidas, true)) {
    $faseSeleccionada = 'grupos';
}

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
    if ($jornadaSeleccionada === null && !empty($jornadas)) {
        $jornadaSeleccionada = max(array_keys($jornadas));
    }
}

$titulo_pagina = 'Calendario — ' . $torneo['nombre'];
$pagina_activa = 'calendario';
require __DIR__ . '/includes/layout_top.php';

function tarjeta_partido_publica(array $p, array $equiposPorId): void
{
    $local = $equiposPorId[$p['equipo_local']] ?? null;
    $visit = $equiposPorId[$p['equipo_visitante']] ?? null;
    if (!$local || !$visit) {
        return;
    }
    $jugado = $p['estado'] === 'jugado';
    ?>
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
                        <?= (int) $p['marcador_local'] ?> - <?= (int) $p['marcador_visitante'] ?>
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
    <?php
}
?>

<header class="hero-copa" style="padding-bottom:3.5rem;">
    <div class="container">
        <p class="kicker mb-2"><i class="bi bi-calendar-week me-1"></i>Temporada <?= e($torneo['temporada']) ?></p>
        <h1 class="text-white mb-2">Calendario de <span class="text-degradado">Encuentros</span></h1>
        <p style="color:rgba(255,255,255,.75);" class="mb-0">Fase de grupos y eliminación directa: cuartos, semifinal y la gran final.</p>
    </div>
</header>

<section class="seccion pt-5">
    <div class="container">
        <div class="d-flex flex-wrap gap-2 mb-4 justify-content-center">
            <?php foreach ($fasesValidas as $f): ?>
            <a href="<?= url('calendario.php?fase=' . $f) ?>" class="btn btn-sm rounded-pill px-3 <?= $faseSeleccionada === $f ? 'btn-degradado' : 'btn-outline-secondary' ?>"><?= e(FASES_LABEL[$f]) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($faseSeleccionada === 'grupos'): ?>

            <?php if (empty($jornadas)): ?>
                <p class="text-muted text-center">Aún no hay encuentros de fase de grupos programados.</p>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-2 mb-4 justify-content-center">
                    <?php foreach (array_keys($jornadas) as $num): ?>
                    <a href="<?= url('calendario.php?jornada=' . $num) ?>" class="btn btn-sm rounded-pill px-3 <?= $num === $jornadaSeleccionada ? 'btn-degradado' : 'btn-outline-secondary' ?>">Jornada <?= $num ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="row row-cols-1 row-cols-lg-2 g-3">
                    <?php foreach ($jornadas[$jornadaSeleccionada] as $p): tarjeta_partido_publica($p, $equiposPorId); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <?php if (empty($playoffsPorFase[$faseSeleccionada])): ?>
                <div class="card-suave p-5 text-center mx-auto" style="max-width:480px;">
                    <i class="bi bi-trophy display-5 d-block mb-3" style="color:var(--color-acento);"></i>
                    <h5 class="mb-2"><?= e(FASES_LABEL[$faseSeleccionada]) ?></h5>
                    <p class="text-muted mb-0">Todavía no se ha definido el cuadro de <?= mb_strtolower(e(FASES_LABEL[$faseSeleccionada])) ?>. Vuelve pronto para ver los enfrentamientos.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-lg-2 g-3">
                    <?php foreach ($playoffsPorFase[$faseSeleccionada] as $p): tarjeta_partido_publica($p, $equiposPorId); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
