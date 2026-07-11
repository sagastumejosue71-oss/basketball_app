<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/usuarios.php';
require_once __DIR__ . '/../includes/helpers.php';

auth_requerir();
$usuarioSesion = usuarios_obtener_por_id((int) $_SESSION['usuario_id']);
if (!es_superadmin($usuarioSesion)) {
    http_response_code(403);
    exit('No tienes permiso para ver esta página.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    if (($_POST['accion'] ?? '') === 'agregar') {
        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirigir_con_mensaje(url('admin/usuarios_autorizados.php'), 'error', 'Ingresa un correo válido.');
        }
        correos_autorizados_agregar($email);
        redirigir_con_mensaje(url('admin/usuarios_autorizados.php'), 'success', 'Correo agregado a la lista.');
    }

    if (($_POST['accion'] ?? '') === 'eliminar') {
        correos_autorizados_eliminar((int) $_POST['id']);
        redirigir_con_mensaje(url('admin/usuarios_autorizados.php'), 'success', 'Correo quitado de la lista.');
    }
}

$correos = correos_autorizados_listar();

$seccion_activa = 'usuarios_autorizados';
$titulo_pagina = 'Correos autorizados';
require __DIR__ . '/includes/admin_layout_top.php';
?>

<div class="mb-4">
    <h3 class="mb-1">Correos autorizados</h3>
    <p class="text-muted small mb-0">El registro público está cerrado. Solo los correos de esta lista pueden crear una cuenta nueva con "Continuar con Google". Las cuentas que ya existían no se ven afectadas.</p>
</div>

<form method="post" class="card-suave p-3 mb-4 d-flex flex-row gap-2" style="max-width:520px;">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="accion" value="agregar">
    <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com" required>
    <button type="submit" class="btn btn-degradado rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
</form>

<?php if (empty($correos)): ?>
    <p class="text-muted">Todavía no has agregado ningún correo a la lista.</p>
<?php else: ?>
<div class="table-responsive card-suave p-0" style="max-width:520px;">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Correo</th>
                <th style="width:60px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($correos as $c): ?>
            <tr>
                <td><?= e($c['email']) ?></td>
                <td class="text-end">
                    <form method="post" data-confirm="¿Quitar a <?= e($c['email']) ?> de la lista?">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/admin_layout_bottom.php'; ?>
