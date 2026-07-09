<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/tabla.php';

$torneo = db_leer('torneo');
$equipos = db_leer('equipos');
$partidos = db_leer('partidos');
$patrocinadores = db_leer('patrocinadores');

$tabla = calcular_tabla($equipos, $partidos);
$top5 = array_slice($tabla, 0, 5);
$proximos = proximos_partidos($partidos, 3);
$resultados = ultimos_resultados($partidos, 3);
$totalJugados = count(array_filter($partidos, fn($p) => $p['estado'] === 'jugado'));
$totalProgramados = count(array_filter($partidos, fn($p) => $p['estado'] === 'programado'));
$jornadaActual = max(array_column($partidos, 'jornada'));

$equiposPorId = [];
foreach ($equipos as $eq) {
    $equiposPorId[$eq['id']] = $eq;
}

$patrocOficiales = array_values(array_filter($patrocinadores, fn($p) => $p['nivel'] === 'oficial'));
$patrocOro = array_values(array_filter($patrocinadores, fn($p) => $p['nivel'] === 'oro'));
$patrocPlata = array_values(array_filter($patrocinadores, fn($p) => $p['nivel'] === 'plata'));

$titulo_pagina = $torneo['nombre'] . ' — ' . $torneo['subtitulo'];
$pagina_activa = 'inicio';
require __DIR__ . '/includes/layout_top.php';
?>

<!-- HERO -->
<header class="hero-copa">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-7">
                <p class="kicker mb-3"><i class="bi bi-stars me-1"></i>Temporada <?= e($torneo['temporada']) ?></p>
                <h1 class="text-white mb-3"><?= e($torneo['nombre']) ?> <span class="text-degradado d-block d-sm-inline"><?= e($torneo['subtitulo']) ?></span></h1>
                <p class="fs-5 mb-4" style="color:rgba(255,255,255,.8);max-width:560px;"><?= e($torneo['hero_frase']) ?>. <?= e($torneo['descripcion']) ?></p>
                <div class="d-flex flex-wrap gap-3 mb-5">
                    <a href="<?= url('tabla.php') ?>" class="btn btn-degradado btn-lg rounded-pill px-4">Ver tabla de posiciones</a>
                    <a href="<?= url('calendario.php') ?>" class="btn btn-outline-luz btn-lg rounded-pill px-4">Calendario completo</a>
                </div>
                <div class="row row-cols-2 row-cols-sm-4 g-3">
                    <div class="col"><div class="hero-stat"><div class="valor"><?= count($equipos) ?></div><div class="etiqueta">Equipos</div></div></div>
                    <div class="col"><div class="hero-stat"><div class="valor"><?= count($partidos) ?></div><div class="etiqueta">Partidos</div></div></div>
                    <div class="col"><div class="hero-stat"><div class="valor"><?= $totalJugados ?></div><div class="etiqueta">Jugados</div></div></div>
                    <div class="col"><div class="hero-stat"><div class="valor"><?= $jornadaActual ?></div><div class="etiqueta">Jornadas</div></div></div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-basquet"><div class="balon"></div></div>
            </div>
        </div>
    </div>
</header>

<!-- TABLA PREVIEW -->
<section class="seccion" id="tabla">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 seccion-titulo">
            <div>
                <p class="eyebrow mb-1">Clasificación</p>
                <h2 class="mb-0">Tabla de posiciones</h2>
            </div>
            <a href="<?= url('tabla.php') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mt-3 mt-sm-0">Ver tabla completa <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="table-responsive">
            <table class="table tabla-posiciones align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Equipo</th>
                        <th class="text-center">PJ</th>
                        <th class="text-center">PG</th>
                        <th class="text-center">PP</th>
                        <th class="text-center">DIF</th>
                        <th class="text-center">PTS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top5 as $fila): ?>
                    <tr class="<?= $fila['posicion'] <= 4 ? 'zona-playoff' : '' ?>">
                        <td>
                            <span class="pos-num <?= $fila['posicion'] === 1 ? 'oro' : ($fila['posicion'] === 2 ? 'plata' : ($fila['posicion'] === 3 ? 'bronce' : '')) ?>"><?= $fila['posicion'] ?></span>
                        </td>
                        <td>
                            <a href="<?= url('equipo.php?id=' . $fila['equipo']['id']) ?>" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                                <?= logo_equipo($fila['equipo'], 34) ?>
                                <span class="fw-semibold"><?= e($fila['equipo']['nombre']) ?></span>
                            </a>
                        </td>
                        <td class="text-center"><?= $fila['pj'] ?></td>
                        <td class="text-center"><?= $fila['pg'] ?></td>
                        <td class="text-center"><?= $fila['pp'] ?></td>
                        <td class="text-center fw-semibold <?= $fila['dif'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fila['dif'] >= 0 ? '+' : '' ?><?= $fila['dif'] ?></td>
                        <td class="text-center fw-bold"><?= $fila['pts'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="small text-muted mt-3 mb-0"><span class="d-inline-block" style="width:10px;height:10px;background:var(--color-acento);border-radius:2px;"></span> Zona de Playoffs (Top 4)</p>
    </div>
</section>

<!-- PARTIDOS -->
<section class="seccion bg-white bg-opacity-50" style="background:#f4f0fb;">
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-6">
                <div class="seccion-titulo mb-4">
                    <p class="eyebrow mb-1">Agenda</p>
                    <h2 class="mb-0">Próximos partidos</h2>
                </div>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($proximos as $p): $local = $equiposPorId[$p['equipo_local']]; $visit = $equiposPorId[$p['equipo_visitante']]; ?>
                    <div class="partido-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge-jornada">Jornada <?= $p['jornada'] ?></span>
                            <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?= formatear_fecha_larga($p['fecha']) ?> · <?= e($p['hora']) ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="equipo-col"><?= logo_equipo($local, 52) ?><span class="nombre"><?= e($local['nombre']) ?></span></div>
                            <div class="fw-bold text-muted">VS</div>
                            <div class="equipo-col"><?= logo_equipo($visit, 52) ?><span class="nombre"><?= e($visit['nombre']) ?></span></div>
                        </div>
                        <p class="text-center small text-muted mt-2 mb-0"><i class="bi bi-geo-alt me-1"></i><?= e($p['cancha']) ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($proximos)): ?>
                        <p class="text-muted">No hay partidos programados por el momento.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="seccion-titulo mb-4">
                    <p class="eyebrow mb-1">Resultados</p>
                    <h2 class="mb-0">Últimos marcadores</h2>
                </div>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($resultados as $p): $local = $equiposPorId[$p['equipo_local']]; $visit = $equiposPorId[$p['equipo_visitante']]; $ganoLocal = $p['marcador_local'] > $p['marcador_visitante']; ?>
                    <div class="partido-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge-jornada">Jornada <?= $p['jornada'] ?></span>
                            <span class="badge badge-estado-jugado rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i>Finalizado</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="equipo-col">
                                <?= logo_equipo($local, 52) ?>
                                <span class="nombre <?= $ganoLocal ? 'text-success' : '' ?>"><?= e($local['nombre']) ?></span>
                            </div>
                            <div class="marcador"><?= $p['marcador_local'] ?> - <?= $p['marcador_visitante'] ?></div>
                            <div class="equipo-col">
                                <?= logo_equipo($visit, 52) ?>
                                <span class="nombre <?= !$ganoLocal ? 'text-success' : '' ?>"><?= e($visit['nombre']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- EQUIPOS -->
<section class="seccion" id="equipos">
    <div class="container">
        <div class="seccion-titulo mb-4">
            <p class="eyebrow mb-1">La liga</p>
            <h2 class="mb-0">Equipos de la temporada</h2>
        </div>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach ($equipos as $eq): ?>
            <div class="col">
                <a href="<?= url('equipo.php?id=' . $eq['id']) ?>" class="equipo-tile">
                    <?= logo_equipo($eq, 68) ?>
                    <div class="nombre"><?= e($eq['nombre']) ?></div>
                    <div class="ciudad"><?= e($eq['ciudad']) ?></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PATROCINADORES -->
<?php require __DIR__ . '/includes/seccion_patrocinadores.php'; ?>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
