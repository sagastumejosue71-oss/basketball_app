<?php
declare(strict_types=1);
// Incluido desde index.php cuando se accede a la raíz del sitio sin ninguna copa
// (config.php, db.php y helpers.php ya están cargados por index.php).

$torneo = null;

$titulo_pagina = 'Crea tu propia copa';
$pagina_activa = 'inicio';
require __DIR__ . '/includes/layout_top.php';
?>

<header class="hero-copa">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-7">
                <p class="kicker mb-3"><i class="bi bi-stars me-1"></i>Torneos y ligas, a tu manera</p>
                <h1 class="text-white mb-3">Crea tu <span class="text-degradado">propia copa</span></h1>
                <p class="fs-5 mb-4" style="color:rgba(255,255,255,.8);max-width:560px;">Basketball, fútbol o el deporte que organices: equipos, calendario, tabla de posiciones y patrocinadores, con tu propia URL, tu QR y un código corto para compartirla en segundos.</p>
                <div class="d-flex flex-wrap gap-3 mb-5">
                    <a href="<?= url('registro.php') ?>" class="btn btn-degradado btn-lg rounded-pill px-4"><i class="bi bi-plus-circle me-2"></i>Crear tu torneo</a>
                    <a href="<?= url('torneos.php') ?>" class="btn btn-outline-luz btn-lg rounded-pill px-4">Ver todas las copas</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card-suave p-4">
                    <h5 class="mb-1"><i class="bi bi-key-fill me-2"></i>¿Ya tienes un código?</h5>
                    <p class="text-muted small mb-3">Entra directo a esa copa sin buscar el enlace.</p>
                    <form method="get" action="<?= url('codigo.php') ?>" class="d-flex gap-2">
                        <input type="text" name="c" class="form-control text-center text-uppercase fw-bold" style="letter-spacing:.2em;" maxlength="6" placeholder="ABC123" autocomplete="off" required>
                        <button type="submit" class="btn btn-degradado px-4">Ir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="seccion">
    <div class="container">
        <div class="seccion-titulo mb-4 text-center">
            <p class="eyebrow mb-1">Cómo funciona</p>
            <h2 class="mb-0">Tres pasos y ya estás jugando</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-suave p-4 h-100 text-center">
                    <span class="badge-pill-icon mx-auto mb-3" style="width:56px;height:56px;font-size:1.4rem;">1</span>
                    <h5 class="mb-2">Crea tu cuenta</h5>
                    <p class="text-muted small mb-0">Regístrate gratis y arma tu copa: nombre, deporte, colores y fases de eliminación directa.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-suave p-4 h-100 text-center">
                    <span class="badge-pill-icon mx-auto mb-3" style="width:56px;height:56px;font-size:1.4rem;">2</span>
                    <h5 class="mb-2">Carga equipos y encuentros</h5>
                    <p class="text-muted small mb-0">Agrega equipos, programa partidos y registra resultados desde tu panel.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-suave p-4 h-100 text-center">
                    <span class="badge-pill-icon mx-auto mb-3" style="width:56px;height:56px;font-size:1.4rem;">3</span>
                    <h5 class="mb-2">Comparte</h5>
                    <p class="text-muted small mb-0">Tu copa tiene su propia URL, un código corto y un QR para que cualquiera la encuentre.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="seccion pt-0">
    <div class="container">
        <div class="card-suave p-4 p-md-5 text-center">
            <h4 class="mb-2">¿Listo para organizar tu torneo?</h4>
            <p class="text-muted mb-3">Crear tu cuenta toma menos de un minuto.</p>
            <a href="<?= url('registro.php') ?>" class="btn btn-degradado btn-lg rounded-pill px-4">Crear tu torneo gratis</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
