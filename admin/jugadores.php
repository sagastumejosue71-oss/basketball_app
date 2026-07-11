<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

auth_requerir();
$torneo = admin_requerir_torneo_activo();

$equipoId = (int) ($_GET['equipo_id'] ?? $_POST['equipo_id'] ?? 0);
$equipos = db_leer('equipos', $torneo['id']);
$equipo = db_buscar_por_id($equipos, $equipoId);
if ($equipo === null) {
    http_response_code(404);
    exit('Equipo no encontrado.');
}

$jugadoresTodos = db_leer('jugadores', $torneo['id']);
$jugadores = array_values(array_filter($jugadoresTodos, fn($j) => (int) $j['equipo_id'] === $equipoId));

$accion = $_GET['accion'] ?? 'lista';
$idEditar = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$jugadorEditar = $idEditar ? db_buscar_por_id($jugadores, $idEditar) : null;

$urlLista = url('admin/jugadores.php?equipo_id=' . $equipoId);
$etJugador = forma_genero($torneo['genero'] ?? null, 'Jugador', 'Jugadora');
$etJugadores = forma_genero($torneo['genero'] ?? null, 'Jugadores', 'Jugadoras');
$etActivo = forma_genero($torneo['genero'] ?? null, 'Activo', 'Activa');
$etInactivo = forma_genero($torneo['genero'] ?? null, 'Inactivo', 'Inactiva');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    if (($_POST['accion'] ?? '') === 'eliminar') {
        $id = (int) $_POST['id'];

        $eventos = db_leer('partido_eventos', $torneo['id']);
        $referenciado = false;
        foreach ($eventos as $ev) {
            if ((int) ($ev['jugador_id'] ?? 0) === $id || (int) ($ev['jugador_entra_id'] ?? 0) === $id || (int) ($ev['asistencia_jugador_id'] ?? 0) === $id) {
                $referenciado = true;
                break;
            }
        }

        if ($referenciado) {
            $etJugadorRef = forma_genero($torneo['genero'] ?? null, 'jugador', 'jugadora');
            redirigir_con_mensaje($urlLista, 'error', "Este {$etJugadorRef} ya aparece en la ficha de algún partido y no se puede eliminar. Puedes desactivarlo en su lugar.");
        }

        $jugadoresTodos = array_values(array_filter($jugadoresTodos, fn($j) => $j['id'] !== $id));
        db_guardar('jugadores', $jugadoresTodos, $torneo['id']);
        redirigir_con_mensaje($urlLista, 'success', forma_genero($torneo['genero'] ?? null, 'Jugador eliminado.', 'Jugadora eliminada.'));
    }

    if (($_POST['accion'] ?? '') === 'guardar') {
        $id = (int) ($_POST['id'] ?? 0);
        $dorsal = trim((string) $_POST['dorsal']);
        $nombre = trim((string) $_POST['nombre']);
        $activo = isset($_POST['activo']);

        $etJugadorMin = mb_strtolower($etJugador);

        if ($nombre === '') {
            redirigir_con_mensaje($urlLista, 'error', "El nombre del {$etJugadorMin} es obligatorio.");
        }
        if ($dorsal === '') {
            redirigir_con_mensaje($urlLista, 'error', 'El dorsal es obligatorio.');
        }

        foreach ($jugadores as $j) {
            if ($j['dorsal'] === $dorsal && $j['id'] !== $id) {
                redirigir_con_mensaje($urlLista, 'error', "Ya existe un {$etJugadorMin} con el dorsal \"{$dorsal}\" en este equipo.");
            }
        }

        $datos = [
            'equipo_id' => $equipoId,
            'dorsal' => $dorsal,
            'nombre' => $nombre,
            'activo' => $activo,
        ];

        if ($id > 0) {
            foreach ($jugadoresTodos as &$j) {
                if ($j['id'] === $id) {
                    $j = array_merge($j, $datos, ['id' => $id]);
                }
            }
            unset($j);
            $mensaje = forma_genero($torneo['genero'] ?? null, 'Jugador actualizado correctamente.', 'Jugadora actualizada correctamente.');
        } else {
            $datos['id'] = db_siguiente_id_global('jugadores');
            $jugadoresTodos[] = $datos;
            $mensaje = forma_genero($torneo['genero'] ?? null, 'Jugador agregado correctamente.', 'Jugadora agregada correctamente.');
        }

        db_guardar('jugadores', $jugadoresTodos, $torneo['id']);
        redirigir_con_mensaje($urlLista, 'success', $mensaje);
    }
}

$seccion_activa = 'equipos';
$titulo_pagina = $etJugadores . ' · ' . $equipo['nombre'];
require __DIR__ . '/includes/admin_layout_top.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= url('admin/equipos.php') ?>" class="btn btn-sm btn-outline-secondary rounded-circle"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h3 class="mb-0"><?= e($etJugadores) ?></h3>
        <div class="small text-muted"><?= e($equipo['nombre']) ?></div>
    </div>
</div>

<?php if ($accion === 'nuevo' || $accion === 'editar'): ?>
    <form method="post" class="card-suave p-4" style="max-width:480px;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="equipo_id" value="<?= $equipoId ?>">
        <input type="hidden" name="id" value="<?= $jugadorEditar['id'] ?? 0 ?>">

        <div class="row g-3">
            <div class="col-4">
                <label class="form-label small fw-semibold">Dorsal</label>
                <input type="text" name="dorsal" class="form-control" value="<?= e($jugadorEditar['dorsal'] ?? '') ?>" required maxlength="4">
            </div>
            <div class="col-8">
                <label class="form-label small fw-semibold">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= e($jugadorEditar['nombre'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="checkActivo" name="activo" <?= ($jugadorEditar === null || !empty($jugadorEditar['activo'])) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="checkActivo"><?= e($etActivo) ?> (aparece disponible al cargar eventos de partido)</label>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-degradado rounded-pill px-4">Guardar <?= e(mb_strtolower($etJugador)) ?></button>
            <a href="<?= $urlLista ?>" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
        </div>
    </form>

<?php else: ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Plantilla (<?= count($jugadores) ?>)</h5>
        <a href="<?= $urlLista ?>&accion=nuevo" class="btn btn-degradado rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i><?= e(forma_genero($torneo['genero'] ?? null, 'Nuevo', 'Nueva')) ?> <?= e(mb_strtolower($etJugador)) ?></a>
    </div>

    <?php if (empty($jugadores)): ?>
        <p class="text-muted">Todavía no hay <?= e(mb_strtolower($etJugadores)) ?> <?= e(forma_genero($torneo['genero'] ?? null, 'cargados', 'cargadas')) ?> para este equipo.</p>
    <?php else: ?>
    <div class="table-responsive card-suave p-0">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:80px;">Dorsal</th>
                    <th>Nombre</th>
                    <th style="width:100px;">Estado</th>
                    <th style="width:110px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php $ordenados = $jugadores; usort($ordenados, fn($a, $b) => $a['dorsal'] <=> $b['dorsal']); ?>
                <?php foreach ($ordenados as $j): ?>
                <tr>
                    <td class="fw-bold">#<?= e($j['dorsal']) ?></td>
                    <td><?= e($j['nombre']) ?></td>
                    <td><?= $j['activo'] ? '<span class="badge rounded-pill text-bg-success-subtle text-success-emphasis small">' . e($etActivo) . '</span>' : '<span class="badge rounded-pill text-bg-secondary small">' . e($etInactivo) . '</span>' ?></td>
                    <td class="text-end">
                        <a href="<?= $urlLista ?>&accion=editar&id=<?= $j['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form method="post" class="d-inline" data-confirm="¿Eliminar a <?= e($j['nombre']) ?>?">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="equipo_id" value="<?= $equipoId ?>">
                            <input type="hidden" name="id" value="<?= $j['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/includes/admin_layout_bottom.php'; ?>
