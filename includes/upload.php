<?php
declare(strict_types=1);

/**
 * Procesa la subida opcional de una imagen (logo/escudo/foto) y la guarda en la base de datos
 * (no en disco: el hosting gratuito no garantiza almacenamiento persistente en el sistema de archivos).
 * Devuelve el id de la imagen guardada (para guardar en la columna logo/foto) o null si no se subió nada válido.
 */
function manejar_subida_imagen(string $campo, string $subcarpeta = ''): ?string
{
    if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $permitidos = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];

    $tipoDetectado = mime_content_type($_FILES[$campo]['tmp_name']);
    if (!in_array($tipoDetectado, $permitidos, true)) {
        return null;
    }

    if ($_FILES[$campo]['size'] > 3 * 1024 * 1024) {
        return null;
    }

    $datosImagen = file_get_contents($_FILES[$campo]['tmp_name']);
    if ($datosImagen === false) {
        return null;
    }

    $pdo = db_conexion();
    $stmt = $pdo->prepare('INSERT INTO imagenes (mime, datos) VALUES (:mime, :datos) RETURNING id');
    $stmt->bindValue(':mime', $tipoDetectado, PDO::PARAM_STR);
    $stmt->bindValue(':datos', $datosImagen, PDO::PARAM_LOB);
    $stmt->execute();

    $id = $stmt->fetchColumn();
    return $id !== false ? (string) $id : null;
}

/**
 * Elimina una imagen previamente subida (al reemplazar o borrar un registro).
 * $referencia es el id guardado en la columna logo/foto (ver manejar_subida_imagen).
 */
function eliminar_imagen(?string $referencia): void
{
    if (empty($referencia) || !ctype_digit($referencia)) {
        return;
    }
    $pdo = db_conexion();
    $stmt = $pdo->prepare('DELETE FROM imagenes WHERE id = :id');
    $stmt->bindValue(':id', (int) $referencia, PDO::PARAM_INT);
    $stmt->execute();
}
