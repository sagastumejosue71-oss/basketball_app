<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/usuarios.php';
require_once __DIR__ . '/includes/helpers.php';

if (auth_check()) {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $ip = obtener_ip_cliente();

    if (registro_ip_bloqueada($ip)) {
        $errores[] = 'Demasiados intentos. Espera unos minutos antes de volver a intentar.';
    } else {
        registro_registrar_intento($ip);

        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmar = (string) ($_POST['password_confirmar'] ?? '');

        if (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $usuario)) {
            $errores[] = 'El usuario debe tener 3-30 caracteres (letras, números, "_" o ".").';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Ingresa un correo válido.';
        }
        if ($nombre === '') {
            $errores[] = 'Ingresa tu nombre.';
        }
        if (mb_strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $confirmar) {
            $errores[] = 'Las contraseñas no coinciden.';
        }
        if (empty($errores) && usuarios_obtener_por_usuario($usuario) !== null) {
            $errores[] = 'Ese usuario ya está en uso.';
        }
        if (empty($errores) && usuarios_obtener_por_email($email) !== null) {
            $errores[] = 'Ese correo ya está registrado.';
        }

        if (empty($errores)) {
            $id = usuarios_crear([
                'usuario' => $usuario,
                'email' => $email,
                'nombre' => $nombre,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $nuevoUsuario = usuarios_obtener_por_id($id);
            auth_iniciar_sesion_usuario($nuevoUsuario);
            redirigir_con_mensaje(url('admin/torneos.php?accion=nuevo'), 'success', '¡Bienvenido! Crea tu primera copa para empezar.');
        }
    }
}

$titulo_pagina = 'Crear tu cuenta';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo_pagina) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card" style="max-width:460px;">
        <div class="text-center mb-4">
            <span class="badge-pill-icon mx-auto mb-3" style="width:56px;height:56px;font-size:1.6rem;"><?= icono_deporte(null, 28) ?></span>
            <h3 class="mb-1">Crea tu cuenta</h3>
            <p class="text-muted small mb-0">Organiza tu propio torneo o liga en minutos.</p>
        </div>

        <?php if (!empty($errores)): ?>
        <div class="alert alert-danger rounded-3 py-2 small">
            <ul class="mb-0 ps-3">
                <?php foreach ($errores as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Nombre completo</label>
                <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? '') ?>" autofocus required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Usuario</label>
                <input type="text" name="usuario" class="form-control" value="<?= e($_POST['usuario'] ?? '') ?>" placeholder="Solo letras, números, '_' o '.'" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Correo electrónico</label>
                <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Contraseña</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Confirmar contraseña</label>
                <input type="password" name="password_confirmar" class="form-control" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-degradado btn-lg w-100 rounded-pill">Crear mi cuenta</button>
        </form>
        <?php if (GOOGLE_CLIENT_ID !== ''): ?>
        <div class="d-flex align-items-center gap-2 my-3">
            <hr class="flex-grow-1"><span class="small text-muted">o</span><hr class="flex-grow-1">
        </div>
        <a href="<?= url('google_iniciar.php') ?>" class="btn btn-outline-secondary btn-lg w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-google"></i>Continuar con Google
        </a>
        <?php endif; ?>
        <div class="text-center mt-4 d-flex flex-column gap-1">
            <a href="<?= url('login.php') ?>" class="small text-muted text-decoration-none">¿Ya tienes cuenta? Inicia sesión</a>
            <a href="<?= url('index.php') ?>" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Volver al sitio público</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
