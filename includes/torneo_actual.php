<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Resuelve qué copa corresponde a esta petición.
 * - Si la URL trae ?copa=slug (via la reescritura de /slug/...), se busca esa copa.
 * - Si no, se usa la copa marcada como predeterminada (Copa Estrellas), para que las
 *   URLs sin prefijo sigan funcionando exactamente igual que antes de existir varias copas.
 *
 * Deja la copa resuelta en $torneo (mismo nombre de variable que ya usaban todas las
 * plantillas existentes), para no tener que renombrar cada referencia a $torneo['...'].
 */
$slugSolicitado = $_GET['copa'] ?? null;
$torneo = $slugSolicitado !== null
    ? torneos_obtener_por_slug($slugSolicitado)
    : torneos_obtener_predeterminado();

if ($torneo === null) {
    http_response_code(404);
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Copa no encontrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#120a24;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;">
    <div class="p-4">
        <div style="font-size:3rem;">🏆</div>
        <h1 class="mb-3">Copa no encontrada</h1>
        <p class="mb-4" style="color:rgba(255,255,255,.7);">Esta copa no existe o ya no está activa.</p>
        <a href="/" class="btn btn-light rounded-pill px-4">Ir al inicio</a>
    </div>
</body>
</html>
    <?php
    exit;
}
