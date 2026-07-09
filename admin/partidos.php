<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/tabla.php';

auth_requerir();

$equipos = db_leer('equipos');
$partidos = db_leer('partidos');
$equiposPorId = [];
foreach ($equipos as $eq) { $equiposPorId[$eq['id']] = $eq; }

$accion = $_GET['accion'] ?? 'lista';
$idEditar = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$partidoEditar = $idEditar ? db_buscar_por_id($partidos, $idEditar) : null;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    if (($_POST['accion'] ?? '') === 'eliminar') {
        $id = (int) $_POST['id'];
        $partidos = array_values(array_filter($partidos, fn($p) => $p['id'] !== $id));
        db_guardar('partidos', $partidos);
        redirigir_con_mensaje(url('admin/partidos.php'), 'success', 'Encuentro eliminado correctamente.');
    }

    if (($_POST['accion'] ?? '') === 'guardar') {
        $id = (int) ($_POST['id'] ?? 0);
        $local = (int) $_POST['equipo_local'];
        $visitante = (int) $_POST['equipo_visitante'];
        $estado = (string) $_POST['estado'];

        if ($local === $visitante) {
            $errores[] = 'El equipo local y el visitante no pueden ser el mismo.';
        }
        if (!isset($equiposPorId[$local]) || !isset($equiposPorId[$visitante])) {
            $errores[] = 'Selecciona equipos válidos.';
        }

        $marcadorLocal = $_POST['marcador_local'] !== '' ? (int) $_POST['marcador_local'] : null;
        $marcadorVisitante = $_POST['marcador_visitante'] !== '' ? (int) $_POST['marcador_visitante'] : null;

        if ($estado === 'jugado') {
            if ($marcadorLocal === null || $marcadorVisitante === null) {
                $errores[] = 'Debes capturar el marcador de ambos equipos para marcar el encuentro como jugado.';
            } elseif ($marcadorLocal === $marcadorVisitante) {
                $errores[] = 'En basketball no existen empates: los marcadores no pueden ser iguales.';
            }
        }

        if (empty($errores)) {
            $datos = [
                'jornada' => (int) $_POST['jornada'],
                'equipo_local' => $local,
                'equipo_visitante' => $visitante,
                'fecha' => (string) $_POST['fecha'],
                'hora' => (string) $_POST['hora'],
                'cancha' => trim((string) $_POST['cancha']),
                'estado' => $estado,
                'marcador_local' => $estado === 'jugado' ? $marcadorLocal : null,
                'marcador_visitante' => $estado === 'jugado' ? $marcadorVisitante : null,
            ];

            if ($id > 0) {
                foreach ($partidos as &$p) {
                    if ($p['id'] === $id) {
                        $p = array_merge($p, $datos, ['id' => $id]);
                    }
                }
                unset($p);
                $mensaje = 'Encuentro actualizado correctamente.';
            } else {
                $datos['id'] = db_siguiente_id($partidos);
                $partidos[] = $datos;
                $mensaje = 'Encuentro programado correctamente.';
            }

            db_guardar('partidos', $partidos);
            redirigir_con_mensaje(url('admin/partidos.php'), 'success', $mensaje);
        } else {
            $partidoEditar = array_merge($_POST, ['id' => $id]);
            $accion = $id > 0 ? 'editar' : 'nuevo';
        }
    }
}

$jornadas = partidos_por_jornada($partidos);
$siguienteJornada = empty($jornadas) ? 1 : max(array_keys($jornadas));

$seccion_activa = 'partidos';
$titulo_pagina = 'Encuentros';
require __DIR__ . '/includes/admin_layout_top.php';
?>

<?php if ($accion === 'nuevo' || $accion === 'editar'): ?>
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="<?= url('admin/partidos.php') ?>" class="btn btn-sm btn-outline-secondary rounded-circle"><i class="bi bi-arrow-left"></i></a>
        <h3 class="mb-0"><?= $accion === 'editar' ? 'Editar encuentro' : 'Programar encuentro' ?></h3>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger rounded-3">
        <ul class="mb-0 small">
            <?php foreach ($errores as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="post" class="card-suave p-4" style="max-width:760px;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= $partidoEditar['id'] ?? 0 ?>">

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Jornada</label>
                <input type="number" min="1" name="jornada" class="form-control" value="<?= e((string) ($partidoEditar['jornada'] ?? $siguienteJornada)) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="<?= e($partidoEditar['fecha'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Hora</label>
                <input type="time" name="hora" class="form-control" value="<?= e($partidoEditar['hora'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold">Equipo local</label>
                <select name="equipo_local" class="form-select" required>
                    <option value="">Selecciona...</option>
                    <?php foreach ($equipos as $eq): ?>
                    <option value="<?= $eq['id'] ?>" <?= (int) ($partidoEditar['equipo_local'] ?? 0) === $eq['id'] ? 'selected' : '' ?>><?= e($eq['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Equipo visitante</label>
                <select name="equipo_visitante" class="form-select" required>
                    <option value="">Selecciona...</option>
                    <?php foreach ($equipos as $eq): ?>
                    <option value="<?= $eq['id'] ?>" <?= (int) ($partidoEditar['equipo_visitante'] ?? 0) === $eq['id'] ? 'selected' : '' ?>><?= e($eq['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-semibold">Cancha / Sede</label>
                <input type="text" name="cancha" class="form-control" value="<?= e($partidoEditar['cancha'] ?? '') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-semibold">Estado</label>
                <select name="estado" class="form-select" id="selectEstado">
                    <option value="programado" <?= ($partidoEditar['estado'] ?? 'programado') === 'programado' ? 'selected' : '' ?>>Programado</option>
                    <option value="jugado" <?= ($partidoEditar['estado'] ?? '') === 'jugado' ? 'selected' : '' ?>>Jugado (capturar marcador)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Marcador local</label>
                <input type="number" min="0" name="marcador_local" class="form-control" value="<?= e((string) ($partidoEditar['marcador_local'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Marcador visitante</label>
                <input type="number" min="0" name="marcador_visitante" class="form-control" value="<?= e((string) ($partidoEditar['marcador_visitante'] ?? '')) ?>">
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-degradado rounded-pill px-4">Guardar encuentro</button>
            <a href="<?= url('admin/partidos.php') ?>" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
        </div>
    </form>

<?php else: ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Encuentros (<?= count($partidos) ?>)</h3>
        <a href="<?= url('admin/partidos.php?accion=nuevo') ?>" class="btn btn-degradado rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i>Programar encuentro</a>
    </div>

    <?php foreach ($jornadas as $numJornada => $lista): ?>
    <h6 class="text-muted text-uppercase small fw-bold mb-2 mt-4">Jornada <?= $numJornada ?></h6>
    <div class="row row-cols-1 row-cols-lg-2 g-3 mb-2">
        <?php foreach ($lista as $p): $local = $equiposPorId[$p['equipo_local']] ?? null; $visit = $equiposPorId[$p['equipo_visitante']] ?? null; if (!$local || !$visit) continue; $jugado = $p['estado'] === 'jugado'; ?>
        <div class="col">
            <div class="card-suave p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted"><?= formatear_fecha_larga($p['fecha']) ?> · <?= e($p['hora']) ?></span>
                    <?php if ($jugado): ?>
                        <span class="badge badge-estado-jugado rounded-pill px-2 py-1 small">Finalizado</span>
                    <?php else: ?>
                        <span class="badge badge-estado-programado rounded-pill px-2 py-1 small">Programado</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="equipo-col"><?= logo_equipo($local, 40) ?><span class="nombre"><?= e($local['nombre']) ?></span></div>
                    <div class="marcador fs-5"><?= $jugado ? $p['marcador_local'] . ' - ' . $p['marcador_visitante'] : 'VS' ?></div>
                    <div class="equipo-col"><?= logo_equipo($visit, 40) ?><span class="nombre"><?= e($visit['nombre']) ?></span></div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($p['cancha']) ?></span>
                    <div class="d-flex gap-1">
                        <a href="<?= url('admin/partidos.php?accion=editar&id=' . $p['id']) ?>" class="btn btn-sm btn-outline-secondary"><?= $jugado ? '<i class="bi bi-pencil"></i>' : '<i class="bi bi-clipboard-check"></i> Capturar' ?></a>
                        <form method="post" data-confirm="¿Eliminar este encuentro?">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($partidos)): ?>
        <p class="text-muted">Aún no hay encuentros programados.</p>
    <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/includes/admin_layout_bottom.php'; ?>
