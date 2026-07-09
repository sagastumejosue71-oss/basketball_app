<?php
declare(strict_types=1);

/**
 * Procesa la subida opcional de una imagen (logo/escudo/foto).
 * Devuelve la ruta relativa pública (para guardar en JSON) o null si no se subió nada válido.
 */
function manejar_subida_imagen(string $campo, string $subcarpeta): ?string
{
    if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $permitidos = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];

    $tipoDetectado = mime_content_type($_FILES[$campo]['tmp_name']);
    if (!isset($permitidos[$tipoDetectado])) {
        return null;
    }

    if ($_FILES[$campo]['size'] > 3 * 1024 * 1024) {
        return null;
    }

    $extension = $permitidos[$tipoDetectado];
    $nombreArchivo = bin2hex(random_bytes(8)) . '.' . $extension;
    $carpetaDestino = BASE_DIR . '/assets/img/' . $subcarpeta . '/';
    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
    }

    $rutaDestino = $carpetaDestino . $nombreArchivo;
    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $rutaDestino)) {
        return null;
    }

    return 'assets/img/' . $subcarpeta . '/' . $nombreArchivo;
}

/**
 * Elimina un archivo de imagen previamente subido (al reemplazar o borrar un registro).
 */
function eliminar_imagen(?string $rutaRelativa): void
{
    if (empty($rutaRelativa)) {
        return;
    }
    $rutaAbsoluta = BASE_DIR . '/' . ltrim($rutaRelativa, '/');
    if (is_file($rutaAbsoluta)) {
        @unlink($rutaAbsoluta);
    }
}
