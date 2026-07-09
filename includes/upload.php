<?php
declare(strict_types=1);

const SUBIDA_MAX_BYTES = 10 * 1024 * 1024;

/**
 * Procesa la subida opcional de una imagen (logo/escudo/foto) y la guarda en la base de datos
 * (no en disco: el hosting gratuito no garantiza almacenamiento persistente en el sistema de archivos).
 * Devuelve el id de la imagen guardada (para guardar en la columna logo/foto) o null si no se subió nada.
 *
 * @throws RuntimeException con un mensaje entendible por el usuario si se subió un archivo pero es inválido
 *         (para no fallar en silencio, como pasaba antes con fotos de cámara de celular que superaban el límite).
 */
function manejar_subida_imagen(string $campo, string $subcarpeta = ''): ?string
{
    if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$campo]['error'] === UPLOAD_ERR_INI_SIZE || $_FILES[$campo]['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('La imagen es demasiado grande. El máximo permitido es 10MB.');
    }
    if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen. Intenta de nuevo.');
    }

    if ($_FILES[$campo]['size'] > SUBIDA_MAX_BYTES) {
        throw new RuntimeException('La imagen es demasiado grande. El máximo permitido es 10MB.');
    }

    // SVG queda excluido a propósito: puede llevar <script> embebido y ejecutarse
    // si el navegador lo abre directo (riesgo de XSS almacenado).
    $permitidos = ['image/png', 'image/jpeg', 'image/webp'];

    $tipoDetectado = mime_content_type($_FILES[$campo]['tmp_name']);
    if (!in_array($tipoDetectado, $permitidos, true)) {
        throw new RuntimeException('Formato de imagen no permitido. Usa PNG, JPG o WEBP.');
    }

    $datosImagen = file_get_contents($_FILES[$campo]['tmp_name']);
    if ($datosImagen === false) {
        throw new RuntimeException('No se pudo leer la imagen subida. Intenta de nuevo.');
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
