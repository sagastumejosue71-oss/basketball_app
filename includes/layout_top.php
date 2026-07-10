<?php
declare(strict_types=1);
/**
 * Requiere que la página que lo incluye ya haya definido (opcional):
 * $titulo_pagina, $pagina_activa
 * $torneo lo resuelve torneo_actual.php según la URL (/slug/... o sin prefijo = predeterminado).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/torneo_actual.php';

$pagina_activa = $pagina_activa ?? '';
$titulo_pagina = $titulo_pagina ?? ($torneo['nombre'] . ' — ' . $torneo['subtitulo']);
$flash = obtener_flash();

function nav_activa(string $clave, string $activa): string
{
    return $clave === $activa ? 'active' : '';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo_pagina) ?></title>
    <meta name="description" content="<?= e($torneo['descripcion'] ?? '') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
    <?= torneo_variables_css($torneo) ?>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-copa">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url_copa('index.php') ?>">
            <span class="badge-pill-icon"><?= icono_deporte($torneo['deporte'] ?? null, 22) ?></span>
            <span><?= e($torneo['nombre']) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navPrincipal">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link <?= nav_activa('inicio', $pagina_activa) ?>" href="<?= url_copa('index.php') ?>">Inicio</a></li>
                <li class="nav-item"><a class="nav-link <?= nav_activa('tabla', $pagina_activa) ?>" href="<?= url_copa('tabla.php') ?>">Tabla de Posiciones</a></li>
                <li class="nav-item"><a class="nav-link <?= nav_activa('calendario', $pagina_activa) ?>" href="<?= url_copa('calendario.php') ?>">Calendario</a></li>
                <li class="nav-item"><a class="nav-link <?= nav_activa('equipos', $pagina_activa) ?>" href="<?= url_copa('equipos.php') ?>">Equipos</a></li>
                <li class="nav-item"><a class="nav-link <?= nav_activa('patrocinadores', $pagina_activa) ?>" href="<?= url_copa('patrocinadores.php') ?>">Patrocinadores</a></li>
                <li class="nav-item"><a class="nav-link <?= nav_activa('organizador', $pagina_activa) ?>" href="<?= url_copa('organizador.php') ?>">Organizador</a></li>
                <li class="nav-item"><a class="nav-link <?= nav_activa('copas', $pagina_activa) ?>" href="<?= url('torneos.php') ?>" title="Ver todas las copas"><i class="bi bi-grid-3x3-gap"></i></a></li>
                <li class="nav-item ms-lg-2">
                    <button type="button" class="btn btn-outline-luz btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCompartir">
                        <i class="bi bi-share-fill me-1"></i>Compartir
                    </button>
                </li>
                <li class="nav-item ms-lg-2">
                    <?php if (auth_check()): ?>
                        <a class="btn btn-outline-luz btn-sm rounded-pill px-3" href="<?= url('admin/index.php') ?>"><i class="bi bi-speedometer2 me-1"></i>Panel</a>
                    <?php else: ?>
                        <a class="btn btn-degradado btn-sm rounded-pill px-3" href="<?= url('login.php') ?>"><i class="bi bi-person-circle me-1"></i>Acceder</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php if ($flash): ?>
<div class="container position-fixed top-0 start-50 translate-middle-x pt-5 mt-5" style="z-index:2000;max-width:520px;">
    <div class="alert alert-<?= $flash['tipo'] === 'error' ? 'danger' : $flash['tipo'] ?> shadow-lg rounded-4 border-0 alert-dismissible fade show" data-autoclose role="alert">
        <?= e($flash['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
