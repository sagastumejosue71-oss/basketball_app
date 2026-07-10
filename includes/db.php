<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Único singleton que queda: hay un solo organizador para todas las copas.
const TABLAS_SINGLETON = ['organizador'];

const COLUMNAS_POR_TABLA = [
    'equipos' => ['id', 'torneo_id', 'nombre', 'ciudad', 'sede', 'entrenador', 'fundacion', 'color_primario', 'color_secundario', 'logo', 'descripcion'],
    'partidos' => ['id', 'torneo_id', 'jornada', 'equipo_local', 'equipo_visitante', 'fecha', 'hora', 'cancha', 'estado', 'marcador_local', 'marcador_visitante', 'fase'],
    'patrocinadores' => ['id', 'torneo_id', 'nombre', 'nivel', 'url', 'logo', 'orden'],
    'comentarios' => ['id', 'torneo_id', 'mensaje', 'fecha', 'leido'],
];

const COLUMNAS_ENTERAS_POR_TABLA = [
    'equipos' => ['id', 'torneo_id'],
    'partidos' => ['id', 'torneo_id', 'jornada', 'equipo_local', 'equipo_visitante', 'marcador_local', 'marcador_visitante'],
    'patrocinadores' => ['id', 'torneo_id', 'orden'],
    'comentarios' => ['id', 'torneo_id'],
];

const COLUMNAS_TORNEO = [
    'slug', 'nombre', 'subtitulo', 'temporada', 'descripcion', 'sede_principal', 'logo',
    'color_primario', 'color_secundario', 'color_acento', 'fecha_inicio', 'fecha_fin', 'formato',
    'instagram', 'hero_frase', 'deporte', 'num_equipos', 'fases_playoff', 'permite_empates',
    'puntos_victoria', 'puntos_empate', 'puntos_derrota', 'es_predeterminado', 'activo',
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

function db_leer(string $tabla, ?int $torneoId = null): array
{
    $pdo = db_conexion();

    if (in_array($tabla, TABLAS_SINGLETON, true)) {
        $stmt = $pdo->query("SELECT * FROM {$tabla} WHERE id = 1");
        $fila = $stmt->fetch();
        return $fila ? db_normalizar_fila($tabla, $fila) : [];
    }

    if ($torneoId === null) {
        throw new InvalidArgumentException("db_leer('{$tabla}') necesita un torneo_id.");
    }

    $stmt = $pdo->prepare("SELECT * FROM {$tabla} WHERE torneo_id = :torneo_id ORDER BY id");
    $stmt->bindValue(':torneo_id', $torneoId, PDO::PARAM_INT);
    $stmt->execute();
    $filas = $stmt->fetchAll();
    return array_map(fn($fila) => db_normalizar_fila($tabla, $fila), $filas);
}

function db_guardar(string $tabla, array $datos, ?int $torneoId = null): bool
{
    $pdo = db_conexion();

    if (in_array($tabla, TABLAS_SINGLETON, true)) {
        return db_guardar_singleton($pdo, $tabla, $datos);
    }

    if ($torneoId === null) {
        throw new InvalidArgumentException("db_guardar('{$tabla}') necesita un torneo_id.");
    }

    return db_guardar_coleccion($pdo, $tabla, $datos, $torneoId);
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

function db_guardar_coleccion(PDO $pdo, string $tabla, array $registros, int $torneoId): bool
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
        $stmtBorrar = $pdo->prepare("DELETE FROM {$tabla} WHERE torneo_id = :torneo_id");
        $stmtBorrar->bindValue(':torneo_id', $torneoId, PDO::PARAM_INT);
        $stmtBorrar->execute();

        $stmt = $pdo->prepare($sql);
        foreach ($registros as $registro) {
            foreach ($columnas as $col) {
                // torneo_id siempre viene del parámetro, no del array, para que ningún llamado
                // pueda "escaparse" a otra copa por accidente.
                $valor = $col === 'torneo_id' ? $torneoId : ($registro[$col] ?? null);
                db_bind($stmt, ":{$col}", $valor);
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

/**
 * El id es una PK global compartida por todas las copas (aunque cada copa solo
 * vea su propio subconjunto de filas vía torneo_id), así que el siguiente id
 * debe calcularse contra TODA la tabla, no solo los registros de la copa actual.
 */
function db_siguiente_id_global(string $tabla): int
{
    if (!isset(COLUMNAS_POR_TABLA[$tabla])) {
        throw new InvalidArgumentException("Tabla desconocida: {$tabla}");
    }
    $pdo = db_conexion();
    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM {$tabla}");
    return (int) $stmt->fetchColumn();
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

// ---------------------------------------------------------------------------
// Tabla `torneos` (las copas en sí): no sigue el patrón genérico de arriba
// porque tiene id SERIAL propio y no está "dentro" de ningún torneo.
// ---------------------------------------------------------------------------

function db_parsear_array_pg(?string $valor): array
{
    if ($valor === null) {
        return [];
    }
    $valor = trim($valor, '{}');
    return $valor === '' ? [] : explode(',', $valor);
}

function db_normalizar_torneo(array $fila): array
{
    foreach (['id', 'num_equipos', 'puntos_victoria', 'puntos_empate', 'puntos_derrota'] as $col) {
        if (array_key_exists($col, $fila) && $fila[$col] !== null) {
            $fila[$col] = (int) $fila[$col];
        }
    }
    foreach (['permite_empates', 'es_predeterminado', 'activo'] as $col) {
        if (array_key_exists($col, $fila)) {
            $fila[$col] = (bool) (
                is_string($fila[$col]) ? ($fila[$col] === 't' || $fila[$col] === '1') : $fila[$col]
            );
        }
    }
    if (array_key_exists('fases_playoff', $fila)) {
        $fila['fases_playoff'] = db_parsear_array_pg($fila['fases_playoff']);
    }
    return $fila;
}

function torneos_listar(bool $soloActivos = true): array
{
    $pdo = db_conexion();
    $sql = 'SELECT * FROM torneos' . ($soloActivos ? ' WHERE activo = true' : '') . ' ORDER BY es_predeterminado DESC, creado_en ASC';
    $filas = $pdo->query($sql)->fetchAll();
    return array_map('db_normalizar_torneo', $filas);
}

function torneos_obtener_por_slug(string $slug): ?array
{
    $pdo = db_conexion();
    $stmt = $pdo->prepare('SELECT * FROM torneos WHERE slug = :slug AND activo = true');
    $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
    $stmt->execute();
    $fila = $stmt->fetch();
    return $fila ? db_normalizar_torneo($fila) : null;
}

function torneos_obtener_predeterminado(): ?array
{
    $pdo = db_conexion();
    $fila = $pdo->query('SELECT * FROM torneos WHERE es_predeterminado = true LIMIT 1')->fetch();
    return $fila ? db_normalizar_torneo($fila) : null;
}

function torneos_obtener_por_id(int $id): ?array
{
    $pdo = db_conexion();
    $stmt = $pdo->prepare('SELECT * FROM torneos WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $fila = $stmt->fetch();
    return $fila ? db_normalizar_torneo($fila) : null;
}

/**
 * Crea o actualiza una copa (según traiga o no 'id'). Devuelve el id.
 * Si se marca es_predeterminado, se le quita esa marca a cualquier otra copa
 * (solo una puede responder en las URLs sin prefijo).
 */
function torneos_guardar(array $datos): int
{
    $pdo = db_conexion();

    $valores = [];
    foreach (COLUMNAS_TORNEO as $c) {
        $v = $datos[$c] ?? null;
        if ($c === 'fases_playoff') {
            $v = '{' . implode(',', (array) $v) . '}';
        }
        $valores[$c] = $v;
    }

    $pdo->beginTransaction();
    try {
        if (!empty($datos['es_predeterminado'])) {
            $pdo->exec('UPDATE torneos SET es_predeterminado = false');
        }

        if (!empty($datos['id'])) {
            $id = (int) $datos['id'];
            $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", COLUMNAS_TORNEO));
            $stmt = $pdo->prepare("UPDATE torneos SET {$sets} WHERE id = :id");
            foreach ($valores as $c => $v) {
                db_bind($stmt, ":{$c}", $v);
            }
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $cols = implode(', ', COLUMNAS_TORNEO);
            $marcadores = implode(', ', array_map(fn($c) => ":{$c}", COLUMNAS_TORNEO));
            $stmt = $pdo->prepare("INSERT INTO torneos ({$cols}) VALUES ({$marcadores}) RETURNING id");
            foreach ($valores as $c => $v) {
                db_bind($stmt, ":{$c}", $v);
            }
            $stmt->execute();
            $id = (int) $stmt->fetchColumn();
        }

        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Elimina una copa y (por ON DELETE CASCADE) todos sus equipos, partidos,
 * patrocinadores y comentarios. No se permite borrar la copa predeterminada.
 */
function torneos_eliminar(int $id): void
{
    $torneo = torneos_obtener_por_id($id);
    if ($torneo && $torneo['es_predeterminado']) {
        throw new RuntimeException('No se puede eliminar la copa predeterminada.');
    }
    $pdo = db_conexion();
    $stmt = $pdo->prepare('DELETE FROM torneos WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
}
