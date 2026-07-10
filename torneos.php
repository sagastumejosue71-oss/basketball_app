<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/torneo_actual.php';

$todasLasCopas = torneos_listar(true);

$titulo_pagina = 'Todas las copas';
$pagina_activa = 'copas';
require __DIR__ . '/includes/layout_top.php';
?>

<header class="hero-copa" style="padding-bottom:3.5rem;">
    <div class="container">
        <p class="kicker mb-2"><i class="bi bi-grid-3x3-gap me-1"></i>Explora</p>
        <h1 class="text-white mb-2">Todas las <span class="text-degradado">Copas</span></h1>
        <p style="color:rgba(255,255,255,.75);" class="mb-0">Cada copa tiene su propia tabla, calendario, equipos y patrocinadores.</p>
    </div>
</header>

<section class="seccion pt-5">
    <div class="container">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
            <?php foreach ($todasLasCopas as $t): ?>
            <div class="col">
                <a href="<?= url($t['es_predeterminado'] ? '' : $t['slug'] . '/') ?>" class="text-decoration-none text-dark">
                    <div class="card-suave p-4 h-100 text-center">
                        <div class="mx-auto mb-3">
                            <?php if (!empty($t['logo'])): ?>
                                <img src="<?= e(url_imagen($t['logo'])) ?>" alt="<?= e($t['nombre']) ?>" width="72" height="72" class="rounded-circle" style="object-fit:cover;">
                            <?php else: ?>
                                <span class="badge-pill-icon mx-auto" style="width:72px;height:72px;font-size:1.8rem;"><?= icono_deporte($t['deporte'], 32) ?></span>
                            <?php endif; ?>
                        </div>
                        <h5 class="mb-1"><?= e($t['nombre']) ?></h5>
                        <p class="text-muted small mb-2"><?= e($t['subtitulo']) ?></p>
                        <span class="tier-pill oro"><?= $t['deporte'] === 'futbol' ? 'Fútbol' : 'Basketball' ?></span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($todasLasCopas)): ?>
            <p class="text-muted text-center">Todavía no hay copas activas.</p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
