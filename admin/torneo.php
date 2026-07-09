<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload.php';

auth_requerir();

$torneo = db_leer('torneo');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logoSubido = manejar_subida_imagen('logo', 'torneo');

    $datos = [
        'nombre' => trim((string) $_POST['nombre']),
        'subtitulo' => trim((string) $_POST['subtitulo']),
        'temporada' => trim((string) $_POST['temporada']),
        'descripcion' => trim((string) $_POST['descripcion']),
        'sede_principal' => trim((string) $_POST['sede_principal']),
        'color_primario' => (string) $_POST['color_primario'],
        'color_secundario' => (string) $_POST['color_secundario'],
        'color_acento' => (string) $_POST['color_acento'],
        'fecha_inicio' => (string) $_POST['fecha_inicio'],
        'fecha_fin' => (string) $_POST['fecha_fin'],
        'formato' => trim((string) $_POST['formato']),
        'instagram' => trim((string) $_POST['instagram']),
        'hero_frase' => trim((string) $_POST['hero_frase']),
        'logo' => $logoSubido ?: ($torneo['logo'] ?? ''),
    ];

    if ($datos['nombre'] === '') {
        redirigir_con_mensaje(url('admin/torneo.php'), 'error', 'El nombre del torneo es obligatorio.');
    }

    db_guardar('torneo', $datos);
    redirigir_con_mensaje(url('admin/torneo.php'), 'success', 'Configuración del torneo actualizada.');
}

$seccion_activa = 'torneo';
$titulo_pagina = 'Configuración del Torneo';
require __DIR__ . '/includes/admin_layout_top.php';
?>

<h3 class="mb-4">Configuración del Torneo</h3>

<form method="post" enctype="multipart/form-data" class="card-suave p-4" style="max-width:820px;">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Nombre del torneo</label>
            <input type="text" name="nombre" class="form-control" value="<?= e($torneo['nombre']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Subtítulo</label>
            <input type="text" name="subtitulo" class="form-control" value="<?= e($torneo['subtitulo']) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Temporada</label>
            <input type="text" name="temporada" class="form-control" value="<?= e($torneo['temporada']) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Fecha de inicio</label>
            <input type="date" name="fecha_inicio" class="form-control" value="<?= e($torneo['fecha_inicio']) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Fecha de fin</label>
            <input type="date" name="fecha_fin" class="form-control" value="<?= e($torneo['fecha_fin']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Sede principal</label>
            <input type="text" name="sede_principal" class="form-control" value="<?= e($torneo['sede_principal']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Formato</label>
            <input type="text" name="formato" class="form-control" value="<?= e($torneo['formato'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Frase del hero (portada)</label>
            <input type="text" name="hero_frase" class="form-control" value="<?= e($torneo['hero_frase'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="3"><?= e($torneo['descripcion']) ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Instagram (opcional)</label>
            <input type="url" name="instagram" class="form-control" value="<?= e($torneo['instagram'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Color primario</label>
            <input type="color" name="color_primario" class="form-control form-control-color w-100" value="<?= e($torneo['color_primario'] ?? '#7b2ff7') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Color secundario</label>
            <input type="color" name="color_secundario" class="form-control form-control-color w-100" value="<?= e($torneo['color_secundario'] ?? '#ff6b35') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Color acento</label>
            <input type="color" name="color_acento" class="form-control form-control-color w-100" value="<?= e($torneo['color_acento'] ?? '#ffc93c') ?>">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Logo del torneo (opcional)</label>
            <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
            <div class="form-text">Si no subes uno, se usará el ícono de balón generado automáticamente.</div>
        </div>
    </div>

    <button type="submit" class="btn btn-degradado rounded-pill px-4 mt-4">Guardar cambios</button>
</form>

<?php require __DIR__ . '/includes/admin_layout_bottom.php'; ?>
