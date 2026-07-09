<?php
declare(strict_types=1);

date_default_timezone_set('America/Guatemala');

define('BASE_DIR', dirname(__DIR__));
define('DATA_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);

// Carga variables de entorno desde .env en local (en Render, DATABASE_URL ya viene como variable de entorno real)
$archivoEnv = BASE_DIR . DIRECTORY_SEPARATOR . '.env';
if (getenv('DATABASE_URL') === false && file_exists($archivoEnv)) {
    foreach (file($archivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        putenv(trim($clave) . '=' . trim($valor));
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ruta base para generar enlaces correctamente sin importar la subcarpeta desde la que se sirva el sitio
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Si estamos dentro de /admin, la raíz pública es un nivel arriba
    $raiz = preg_replace('#/admin$#', '', $scriptDir);
    define('BASE_URL', rtrim($raiz, '/'));
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}
