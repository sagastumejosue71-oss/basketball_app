<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/tabla.php';
require_once __DIR__ . '/includes/torneo_actual.php';

$equipos = db_leer('equipos', $torneo['id']);
$partidos = db_leer('partidos', $torneo['id']);
$tabla = calcular_tabla($equipos, $partidos, $torneo);

$explicacionPuntos = $torneo['permite_empates']
    ? "PTS = {$torneo['puntos_victoria']} por victoria + {$torneo['puntos_empate']} por empate + {$torneo['puntos_derrota']} por derrota"
    : "PTS = {$torneo['puntos_victoria']} por victoria + {$torneo['puntos_derrota']} por derrota jugada";

$titulo_pagina = 'Tabla de Posiciones — ' . $torneo['nombre'];
$pagina_activa = 'tabla';
require __DIR__ . '/includes/layout_top.php';
?>

<header class="hero-copa" style="padding-bottom:3.5rem;">
    <div class="container">
        <p class="kicker mb-2"><i class="bi bi-trophy me-1"></i>Temporada <?= e($torneo['temporada']) ?></p>
        <h1 class="text-white mb-2">Tabla de <span class="text-degradado">Posiciones</span></h1>
        <p style="color:rgba(255,255,255,.75);max-width:560px;" class="mb-0">Clasificación general de <?= e($torneo['nombre']) ?>. Los primeros 4 lugares avanzan a la fase de Playoffs.</p>
    </div>
</header>

<section class="seccion pt-5">
    <div class="container">
        <div class="table-responsive">
            <table class="table tabla-posiciones align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Equipo</th>
                        <th class="text-center">PJ</th>
                        <th class="text-center">PG</th>
                        <?php if ($torneo['permite_empates']): ?><th class="text-center">PE</th><?php endif; ?>
                        <th class="text-center">PP</th>
                        <th class="text-center">%G</th>
                        <th class="text-center">PF</th>
                        <th class="text-center">PC</th>
                        <th class="text-center">DIF</th>
                        <th class="text-center">PTS</th>
                        <th>Racha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tabla as $fila): ?>
                    <tr class="fila-clicable <?= $fila['posicion'] <= 4 ? 'zona-playoff' : '' ?>" data-href="<?= e(url_copa('equipo.php?id=' . $fila['equipo']['id'])) ?>">
                        <td data-label="#">
                            <span class="pos-num <?= $fila['posicion'] === 1 ? 'oro' : ($fila['posicion'] === 2 ? 'plata' : ($fila['posicion'] === 3 ? 'bronce' : '')) ?>"><?= $fila['posicion'] ?></span>
                        </td>
                        <td class="td-equipo">
                            <a href="<?= url_copa('equipo.php?id=' . $fila['equipo']['id']) ?>" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                                <?= logo_equipo($fila['equipo'], 38) ?>
                                <div>
                                    <div class="fw-semibold"><?= e($fila['equipo']['nombre']) ?></div>
                                    <div class="small text-muted"><?= e($fila['equipo']['ciudad']) ?></div>
                                </div>
                            </a>
                        </td>
                        <td class="text-center" data-label="PJ"><?= $fila['pj'] ?></td>
                        <td class="text-center" data-label="PG"><?= $fila['pg'] ?></td>
                        <?php if ($torneo['permite_empates']): ?><td class="text-center" data-label="PE"><?= $fila['pe'] ?></td><?php endif; ?>
                        <td class="text-center" data-label="PP"><?= $fila['pp'] ?></td>
                        <td class="text-center" data-label="%G"><?= $fila['porcentaje'] ?>%</td>
                        <td class="text-center" data-label="PF"><?= $fila['pf'] ?></td>
                        <td class="text-center" data-label="PC"><?= $fila['pc'] ?></td>
                        <td class="text-center fw-semibold <?= $fila['dif'] >= 0 ? 'text-success' : 'text-danger' ?>" data-label="DIF"><?= $fila['dif'] >= 0 ? '+' : '' ?><?= $fila['dif'] ?></td>
                        <td class="text-center fw-bold" data-label="PTS"><?= $fila['pts'] ?></td>
                        <td data-label="Racha">
                            <?php if (empty($fila['racha'])): ?>
                                <span class="small text-muted">—</span>
                            <?php else: ?>
                                <?php foreach ($fila['racha'] as $r): ?>
                                    <?php $claseRacha = $r === 'G' ? 'g' : ($r === 'E' ? 'e' : 'p'); ?>
                                    <?php $tituloRacha = $r === 'G' ? 'Ganado' : ($r === 'E' ? 'Empatado' : 'Perdido'); ?>
                                    <span class="racha-punto <?= $claseRacha ?>" title="<?= $tituloRacha ?>"></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap gap-4 mt-3">
            <p class="small text-muted mb-0"><span class="d-inline-block" style="width:10px;height:10px;background:var(--color-acento);border-radius:2px;"></span> Zona de Playoffs (Top 4)</p>
            <p class="small text-muted mb-0"><?= e($explicacionPuntos) ?></p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
