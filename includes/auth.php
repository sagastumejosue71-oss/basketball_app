<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const LOGIN_MAX_INTENTOS = 5;
const LOGIN_VENTANA_SEGUNDOS = 60;

function auth_check(): bool
{
    return !empty($_SESSION['organizador_autenticado']);
}

/**
 * IP real del visitante detrás del proxy de Render.
 *
 * X-Forwarded-For es una lista que cada proxy en el camino va completando:
 * "ip_dicha_por_el_cliente, ip_vista_por_el_siguiente_proxy, ...". El primer valor
 * lo puede inventar cualquiera con curl -H, así que NO sirve para bloquear fuerza
 * bruta. El único valor confiable es el ÚLTIMO: el que Render agregó al recibir
 * la conexión real, que el cliente no puede falsificar.
 */
function obtener_ip_cliente(): string
{
    $reenviada = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($reenviada !== '') {
        $partes = array_map('trim', explode(',', $reenviada));
        $ip = end($partes);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * true si esta IP ya alcanzó el máximo de intentos de login permitidos en la última ventana de tiempo.
 */
function auth_ip_bloqueada(string $ip): bool
{
    $pdo = db_conexion();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM intentos_login WHERE ip = :ip AND intentado_en > now() - make_interval(secs => :segundos)'
    );
    $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stmt->bindValue(':segundos', LOGIN_VENTANA_SEGUNDOS, PDO::PARAM_INT);
    $stmt->execute();
    return (int) $stmt->fetchColumn() >= LOGIN_MAX_INTENTOS;
}

/**
 * Registra un intento de login (exitoso o no) para efectos del límite de intentos,
 * y aprovecha para limpiar registros viejos.
 */
function auth_registrar_intento(string $ip): void
{
    $pdo = db_conexion();
    $stmt = $pdo->prepare('INSERT INTO intentos_login (ip) VALUES (:ip)');
    $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stmt->execute();

    $pdo->exec("DELETE FROM intentos_login WHERE intentado_en < now() - interval '1 hour'");
}

function auth_intentar_login(string $usuario, string $password): bool
{
    $organizador = db_leer('organizador');
    if (empty($organizador)) {
        return false;
    }

    $usuarioValido = hash_equals((string) $organizador['usuario'], $usuario);
    $passwordValido = password_verify($password, (string) $organizador['password_hash']);

    if ($usuarioValido && $passwordValido) {
        session_regenerate_id(true);
        $_SESSION['organizador_autenticado'] = true;
        $_SESSION['organizador_usuario'] = $organizador['usuario'];
        return true;
    }

    return false;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_requerir(): void
{
    if (!auth_check()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

/**
 * Token anti-CSRF para formularios del panel: evita que un sitio externo pueda enviar
 * acciones (crear/editar/eliminar) en nombre del organizador ya autenticado.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validar(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Token de seguridad inválido o expirado. Recarga la página e intenta de nuevo.');
    }
}
