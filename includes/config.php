<?php
declare(strict_types=1);

date_default_timezone_set('America/Guatemala');

define('BASE_DIR', dirname(__DIR__));
define('DATA_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);

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
