<?php
declare(strict_types=1);

/**
 * Router para el servidor embebido de PHP ("php -S ... router.php"), para que en
 * desarrollo local las URLs por copa (/slug/...) se comporten igual que en producción
 * (donde Apache hace esta misma reescritura vía apache-vhost.conf).
 */

$uri = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$docRoot = __DIR__;

// Archivo o carpeta real (assets/, admin/, imagen.php, etc.): que lo sirva tal cual
if ($uri !== '/' && (is_file($docRoot . $uri) || is_dir($docRoot . $uri))) {
    return false;
}

$ruta = trim($uri, '/');

if ($ruta === '') {
    return false; // "/" -> index.php normal
}

// /slug  ->  index.php?copa=slug
if (preg_match('#^([a-z0-9-]+)$#', $ruta, $m)) {
    $_GET['copa'] = $m[1];
    require $docRoot . '/index.php';
    return true;
}

// /slug/archivo.php  ->  archivo.php?copa=slug
if (preg_match('#^([a-z0-9-]+)/(.+)$#', $ruta, $m)) {
    $archivo = $docRoot . '/' . $m[2];
    if (is_file($archivo)) {
        $_GET['copa'] = $m[1];
        require $archivo;
        return true;
    }
}

return false;
