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

// Render (y la mayoría de hostings con proxy) terminan el HTTPS antes de llegar a PHP;
// hay que revisar X-Forwarded-Proto además de $_SERVER['HTTPS'] para detectarlo correctamente.
$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $esHttps,
    ]);
    session_start();
}

// Cabeceras de seguridad básicas para todas las respuestas del sitio
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;");
    if ($esHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Evita filtrar detalles internos (rutas, credenciales de conexión, stack traces) a los visitantes
ini_set('display_errors', '0');
set_exception_handler(function (Throwable $e): void {
    error_log($e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo 'Ocurrió un error inesperado. Por favor intenta de nuevo más tarde.';
    exit;
});

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

/**
 * Igual que url(), pero antepone el slug de la copa actual (variable global $torneo)
 * cuando no es la copa predeterminada, para que los links del sitio se queden dentro
 * de la misma copa (/slug/tabla.php en vez de /tabla.php).
 */
function url_copa(string $path = ''): string
{
    global $torneo;
    $prefijo = (!empty($torneo) && empty($torneo['es_predeterminado']) && !empty($torneo['slug']))
        ? '/' . $torneo['slug']
        : '';
    return BASE_URL . $prefijo . '/' . ltrim($path, '/');
}
