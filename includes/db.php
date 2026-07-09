<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Lee un archivo JSON de /data con bloqueo compartido.
 */
function db_leer(string $nombre): array
{
    $path = DATA_DIR . $nombre . '.json';
    if (!file_exists($path)) {
        return [];
    }
    $fp = fopen($path, 'r');
    if (!$fp) {
        return [];
    }
    flock($fp, LOCK_SH);
    $contenido = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $datos = json_decode($contenido, true);
    return is_array($datos) ? $datos : [];
}

/**
 * Escribe un archivo JSON en /data con bloqueo exclusivo (evita corrupción por escrituras simultáneas).
 */
function db_guardar(string $nombre, array $datos): bool
{
    $path = DATA_DIR . $nombre . '.json';
    $fp = fopen($path, 'c+');
    if (!$fp) {
        return false;
    }
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function db_siguiente_id(array $registros): int
{
    $max = 0;
    foreach ($registros as $r) {
        if (isset($r['id']) && $r['id'] > $max) {
            $max = (int) $r['id'];
        }
    }
    return $max + 1;
}

function db_buscar_por_id(array $registros, int $id): ?array
{
    foreach ($registros as $r) {
        if ((int) ($r['id'] ?? 0) === $id) {
            return $r;
        }
    }
    return null;
}
