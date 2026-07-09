<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/filtro.php';

$torneo = db_leer('torneo');
$organizador = db_leer('organizador');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot: los bots suelen rellenar este campo oculto; los humanos lo dejan vacío
    if (!empty($_POST['sitio_web'])) {
        redirigir_con_mensaje(url('organizador.php'), 'success', '¡Gracias por tu comentario!');
    }

    $ultimoEnvio = $_SESSION['ultimo_comentario'] ?? 0;
    if (time() - $ultimoEnvio < 20) {
        redirigir_con_mensaje(url('organizador.php'), 'error', 'Espera unos segundos antes de enviar otro comentario.');
    }

    $mensaje = trim((string) ($_POST['mensaje'] ?? ''));

    if (mb_strlen($mensaje) < 5) {
        redirigir_con_mensaje(url('organizador.php'), 'error', 'Tu comentario es muy corto. Cuéntanos un poco más.');
    } elseif (mb_strlen($mensaje) > 800) {
        redirigir_con_mensaje(url('organizador.php'), 'error', 'Tu comentario es demasiado largo (máximo 800 caracteres).');
    } elseif (contiene_lenguaje_inapropiado($mensaje)) {
        redirigir_con_mensaje(url('organizador.php'), 'error', 'Tu comentario contiene lenguaje inapropiado. Por favor reformúlalo con respeto.');
    } else {
        $comentarios = db_leer('comentarios');
        $comentarios[] = [
            'id' => db_siguiente_id($comentarios),
            'mensaje' => $mensaje,
            'fecha' => date('Y-m-d H:i'),
            'leido' => false,
        ];
        db_guardar('comentarios', $comentarios);
        $_SESSION['ultimo_comentario'] = time();
        redirigir_con_mensaje(url('organizador.php'), 'success', '¡Gracias! Tu comentario anónimo fue enviado a la organización.');
    }
}

$titulo_pagina = 'Organizador — ' . $torneo['nombre'];
$pagina_activa = 'organizador';
require __DIR__ . '/includes/layout_top.php';
?>

<header class="hero-copa" style="padding-bottom:3.5rem;">
    <div class="container">
        <p class="kicker mb-2"><i class="bi bi-person-badge me-1"></i>Conoce a</p>
        <h1 class="text-white mb-2">La <span class="text-degradado">Organizadora</span></h1>
        <p style="color:rgba(255,255,255,.75);" class="mb-0">La persona detrás de <?= e($torneo['nombre']) ?>.</p>
    </div>
</header>

<section class="seccion pt-5">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-5">
                <div class="card-suave p-4 text-center h-100">
                    <?php if (!empty($organizador['foto'])): ?>
                        <img src="<?= e(url_imagen($organizador['foto'])) ?>" alt="<?= e($organizador['nombre']) ?>" class="rounded-circle mx-auto mb-3" width="120" height="120" style="object-fit:cover;">
                    <?php else: ?>
                        <div class="avatar-organizador mx-auto mb-3" style="width:120px;height:120px;font-size:2.4rem;"><?= e(iniciales_de($organizador['nombre'])) ?></div>
                    <?php endif; ?>
                    <h4 class="mb-1"><?= e($organizador['nombre']) ?></h4>
                    <p class="text-muted mb-3"><?= e($organizador['cargo']) ?></p>
                    <p class="mb-4"><?= nl2br(e($organizador['bio'] ?? '')) ?></p>

                    <div class="d-flex flex-column gap-2">
                        <?php if (!empty($organizador['email'])): ?>
                        <a href="mailto:<?= e($organizador['email']) ?>" class="btn btn-outline-secondary rounded-pill">
                            <i class="bi bi-envelope me-2"></i><?= e($organizador['email']) ?>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($organizador['telefono'])): ?>
                        <a href="tel:<?= e(preg_replace('/\s+/', '', $organizador['telefono'])) ?>" class="btn btn-outline-secondary rounded-pill">
                            <i class="bi bi-telephone me-2"></i><?= e($organizador['telefono']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-suave p-4 h-100">
                    <h5 class="mb-1"><i class="bi bi-chat-heart me-2"></i>Déjale un comentario anónimo</h5>
                    <p class="text-muted small mb-4">No pedimos tu nombre ni tu correo: tu comentario es 100% anónimo. Solo te pedimos mantener el respeto — los mensajes con lenguaje inapropiado no se publican.</p>
                    <form method="post" novalidate>
                        <div class="d-none" aria-hidden="true">
                            <label>No llenar este campo<input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label>
                        </div>
                        <div class="mb-3">
                            <textarea name="mensaje" class="form-control" rows="6" maxlength="800" placeholder="Escribe aquí tu comentario, sugerencia o felicitación..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-degradado rounded-pill px-4"><i class="bi bi-send me-2"></i>Enviar comentario anónimo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
