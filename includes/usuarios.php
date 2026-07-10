<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const COLUMNAS_USUARIO = ['usuario', 'email', 'nombre', 'cargo', 'telefono', 'foto', 'bio'];

function usuarios_normalizar(array $fila): array
{
    $fila['id'] = (int) $fila['id'];
    return $fila;
}

function usuarios_obtener_por_id(int $id): ?array
{
    $pdo = db_conexion();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $fila = $stmt->fetch();
    return $fila ? usuarios_normalizar($fila) : null;
}

function usuarios_obtener_por_usuario(string $usuario): ?array
{
    $pdo = db_conexion();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = :usuario');
    $stmt->bindValue(':usuario', $usuario, PDO::PARAM_STR);
    $stmt->execute();
    $fila = $stmt->fetch();
    return $fila ? usuarios_normalizar($fila) : null;
}

function usuarios_obtener_por_email(string $email): ?array
{
    $pdo = db_conexion();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $fila = $stmt->fetch();
    return $fila ? usuarios_normalizar($fila) : null;
}

/**
 * Crea una cuenta nueva. $datos: usuario, email, password_hash, nombre (el resto de
 * COLUMNAS_USUARIO es opcional). Devuelve el id ya creado.
 */
function usuarios_crear(array $datos): int
{
    $pdo = db_conexion();
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (usuario, email, password_hash, nombre, cargo, telefono, foto, bio)
         VALUES (:usuario, :email, :password_hash, :nombre, :cargo, :telefono, :foto, :bio) RETURNING id'
    );
    $stmt->bindValue(':usuario', $datos['usuario'], PDO::PARAM_STR);
    $stmt->bindValue(':email', $datos['email'], PDO::PARAM_STR);
    $stmt->bindValue(':password_hash', $datos['password_hash'], PDO::PARAM_STR);
    $stmt->bindValue(':nombre', $datos['nombre'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':cargo', $datos['cargo'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':telefono', $datos['telefono'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':foto', $datos['foto'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':bio', $datos['bio'] ?? '', PDO::PARAM_STR);
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

/**
 * Actualiza el perfil (y opcionalmente la contraseña) del usuario ya identificado por
 * $datos['id']. Nunca toca 'usuario' (el nombre de acceso no se puede cambiar aquí).
 */
function usuarios_guardar(array $datos): bool
{
    $pdo = db_conexion();
    $columnas = ['nombre', 'cargo', 'email', 'telefono', 'foto', 'bio'];
    if (!empty($datos['password_hash'])) {
        $columnas[] = 'password_hash';
    }
    $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", $columnas));
    $stmt = $pdo->prepare("UPDATE usuarios SET {$sets} WHERE id = :id");
    foreach ($columnas as $c) {
        db_bind($stmt, ":{$c}", $datos[$c] ?? '');
    }
    $stmt->bindValue(':id', (int) $datos['id'], PDO::PARAM_INT);
    return $stmt->execute();
}

/**
 * Dueño real de una copa (organizador.php y patrocinadores.php lo usan para mostrar
 * el contacto correcto en vez de un organizador global único).
 */
function torneo_organizador(array $torneo): ?array
{
    if (empty($torneo['usuario_id'])) {
        return null;
    }
    return usuarios_obtener_por_id((int) $torneo['usuario_id']);
}
