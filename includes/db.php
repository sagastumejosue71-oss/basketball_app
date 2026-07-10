<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

const TABLAS_SINGLETON = ['torneo', 'organizador'];

const COLUMNAS_POR_TABLA = [
    'equipos' => ['id', 'nombre', 'ciudad', 'sede', 'entrenador', 'fundacion', 'color_primario', 'color_secundario', 'logo', 'descripcion'],
    'partidos' => ['id', 'jornada', 'equipo_local', 'equipo_visitante', 'fecha', 'hora', 'cancha', 'estado', 'marcador_local', 'marcador_visitante', 'fase'],
    'patrocinadores' => ['id', 'nombre', 'nivel', 'url', 'logo', 'orden'],
    'comentarios' => ['id', 'mensaje', 'fecha', 'leido'],
];

const COLUMNAS_ENTERAS_POR_TABLA = [
    'equipos' => ['id'],
    'partidos' => ['id', 'jornada', 'equipo_local', 'equipo_visitante', 'marcador_local', 'marcador_visitante'],
    'patrocinadores' => ['id', 'orden'],
    'comentarios' => ['id'],
];

/**
 * Conexión PDO a PostgreSQL, reutilizada durante toda la petición.
 * Lee la cadena de conexión de la variable de entorno DATABASE_URL
 * (formato: postgresql://usuario:password@host:puerto/basedatos?sslmode=require)
 */
function db_conexion(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $url = getenv('DATABASE_URL');
    if ($url === false || $url === '') {
        throw new RuntimeException('Falta configurar la variable de entorno DATABASE_URL con la conexión a PostgreSQL.');
    }

    $partes = parse_url($url);
    if ($partes === false || !isset($partes['host'])) {
        throw new RuntimeException('DATABASE_URL no tiene un formato válido.');
    }
    parse_str($partes['query'] ?? '', $opciones);

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        $partes['host'],
        $partes['port'] ?? 5432,
        ltrim($partes['path'] ?? '', '/'),
        $opciones['sslmode'] ?? 'require'
    );

    $pdo = new PDO($dsn, $partes['user'] ?? '', $partes['pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

/**
 * PDO pgsql devuelve todas las columnas como texto; esto restaura los tipos nativos
 * (int, bool, null) que el resto de la aplicación espera, igual que hacía json_decode.
 */
function db_normalizar_fila(string $tabla, array $fila): array
{
    foreach (COLUMNAS_ENTERAS_POR_TABLA[$tabla] ?? [] as $col) {
        if (array_key_exists($col, $fila) && $fila[$col] !== null) {
            $fila[$col] = (int) $fila[$col];
        }
    }

    if ($tabla === 'comentarios' && array_key_exists('leido', $fila)) {
        $fila['leido'] = (bool) (int) $fila['leido'];
    }

    return $fila;
}

/**
 * Enlaza un valor PHP a un parámetro con el tipo PDO adecuado según su tipo nativo.
 */
function db_bind(PDOStatement $stmt, string $marcador, mixed $valor): void
{
    if ($valor === null) {
        $stmt->bindValue($marcador, null, PDO::PARAM_NULL);
    } elseif (is_bool($valor)) {
        $stmt->bindValue($marcador, $valor ? 1 : 0, PDO::PARAM_INT);
    } elseif (is_int($valor)) {
        $stmt->bindValue($marcador, $valor, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($marcador, (string) $valor, PDO::PARAM_STR);
    }
}

function db_leer(string $tabla): array
{
    $pdo = db_conexion();

    if (in_array($tabla, TABLAS_SINGLETON, true)) {
        $stmt = $pdo->query("SELECT * FROM {$tabla} WHERE id = 1");
        $fila = $stmt->fetch();
        return $fila ? db_normalizar_fila($tabla, $fila) : [];
    }

    $stmt = $pdo->query("SELECT * FROM {$tabla} ORDER BY id");
    $filas = $stmt->fetchAll();
    return array_map(fn($fila) => db_normalizar_fila($tabla, $fila), $filas);
}

function db_guardar(string $tabla, array $datos): bool
{
    $pdo = db_conexion();

    if (in_array($tabla, TABLAS_SINGLETON, true)) {
        return db_guardar_singleton($pdo, $tabla, $datos);
    }

    return db_guardar_coleccion($pdo, $tabla, $datos);
}

function db_guardar_singleton(PDO $pdo, string $tabla, array $datos): bool
{
    $datos['id'] = 1;
    $columnas = array_keys($datos);
    $marcadores = array_map(fn($c) => ":{$c}", $columnas);
    $actualizaciones = array_map(fn($c) => "{$c} = EXCLUDED.{$c}", array_filter($columnas, fn($c) => $c !== 'id'));

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (id) DO UPDATE SET %s',
        $tabla,
        implode(', ', $columnas),
        implode(', ', $marcadores),
        implode(', ', $actualizaciones)
    );

    $stmt = $pdo->prepare($sql);
    foreach ($datos as $col => $valor) {
        db_bind($stmt, ":{$col}", $valor);
    }
    return $stmt->execute();
}

function db_guardar_coleccion(PDO $pdo, string $tabla, array $registros): bool
{
    if (!isset(COLUMNAS_POR_TABLA[$tabla])) {
        throw new InvalidArgumentException("Tabla desconocida: {$tabla}");
    }

    $columnas = COLUMNAS_POR_TABLA[$tabla];
    $marcadores = array_map(fn($c) => ":{$c}", $columnas);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $tabla,
        implode(', ', $columnas),
        implode(', ', $marcadores)
    );

    $pdo->beginTransaction();
    try {
        $pdo->exec("DELETE FROM {$tabla}");
        $stmt = $pdo->prepare($sql);
        foreach ($registros as $registro) {
            foreach ($columnas as $col) {
                db_bind($stmt, ":{$col}", $registro[$col] ?? null);
            }
            $stmt->execute();
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

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
