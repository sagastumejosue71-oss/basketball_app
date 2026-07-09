<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function auth_check(): bool
{
    return !empty($_SESSION['organizador_autenticado']);
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
