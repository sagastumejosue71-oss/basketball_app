<?php
declare(strict_types=1);

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function iniciales_de(string $nombre): string
{
    $palabras = preg_split('/\s+/', trim($nombre));
    $palabras = array_filter($palabras, fn($p) => mb_strlen($p) > 0);
    $palabras = array_values($palabras);
    if (count($palabras) === 0) {
        return '?';
    }
    if (count($palabras) === 1) {
        return mb_strtoupper(mb_substr($palabras[0], 0, 2));
    }
    return mb_strtoupper(mb_substr($palabras[0], 0, 1) . mb_substr($palabras[count($palabras) - 1], 0, 1));
}

/**
 * Genera un escudo circular en SVG a partir de las iniciales y colores del equipo.
 * Se usa como respaldo cuando el equipo no tiene un logo cargado.
 */
function escudo_svg(string $nombre, string $color1 = '#7b2ff7', string $color2 = '#ff6b35', int $size = 96): string
{
    $iniciales = e(iniciales_de($nombre));
    $gradId = 'g' . substr(md5($nombre . $color1), 0, 8);
    $c1 = e($color1);
    $c2 = e($color2);
    $fontSize = (int) round($size * 0.36);

    return <<<SVG
<svg viewBox="0 0 100 100" width="{$size}" height="{$size}" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{$iniciales}">
    <defs>
        <linearGradient id="{$gradId}" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="{$c1}" />
            <stop offset="100%" stop-color="{$c2}" />
        </linearGradient>
    </defs>
    <circle cx="50" cy="50" r="48" fill="url(#{$gradId})" stroke="rgba(255,255,255,.55)" stroke-width="2" />
    <text x="50" y="50" text-anchor="middle" dominant-baseline="central" font-family="Poppins, Arial, sans-serif" font-weight="700" font-size="{$fontSize}" fill="#ffffff">{$iniciales}</text>
</svg>
SVG;
}

/**
 * Devuelve el HTML (img o svg inline) para el logo de un equipo, usando el escudo generado si no hay logo propio.
 */
function logo_equipo(array $equipo, int $size = 96, string $clase = ''): string
{
    if (!empty($equipo['logo'])) {
        $src = e(url($equipo['logo']));
        $alt = e($equipo['nombre'] ?? '');
        return "<img src=\"{$src}\" alt=\"{$alt}\" width=\"{$size}\" height=\"{$size}\" class=\"{$clase}\" style=\"object-fit:cover;border-radius:50%;\">";
    }
    $c1 = $equipo['color_primario'] ?? '#7b2ff7';
    $c2 = $equipo['color_secundario'] ?? '#ff6b35';
    return "<span class=\"{$clase}\">" . escudo_svg($equipo['nombre'] ?? '?', $c1, $c2, $size) . '</span>';
}

/**
 * Insignia (wordmark) de patrocinador cuando no hay logo cargado.
 */
function badge_patrocinador(array $patrocinador): string
{
    if (!empty($patrocinador['logo'])) {
        $src = e(url($patrocinador['logo']));
        $alt = e($patrocinador['nombre'] ?? '');
        return "<img src=\"{$src}\" alt=\"{$alt}\" class=\"sponsor-logo-img\" loading=\"lazy\">";
    }
    $nombre = e($patrocinador['nombre'] ?? '');
    return "<span class=\"sponsor-wordmark\">{$nombre}</span>";
}

function formatear_fecha_larga(string $fecha): string
{
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $ts = strtotime($fecha);
    if ($ts === false) {
        return e($fecha);
    }
    $dia = $dias[(int) date('w', $ts)];
    $numero = date('d', $ts);
    $mes = $meses[(int) date('n', $ts)];
    return "{$dia} {$numero} {$mes}";
}

function formatear_fecha_corta(string $fecha): string
{
    $ts = strtotime($fecha);
    if ($ts === false) {
        return e($fecha);
    }
    $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return date('d', $ts) . ' ' . $meses[(int) date('n', $ts)];
}

function nivel_patrocinio_label(string $nivel): string
{
    return match ($nivel) {
        'oficial' => 'Patrocinador Oficial',
        'oro' => 'Patrocinador Oro',
        'plata' => 'Patrocinador Plata',
        default => ucfirst($nivel),
    };
}

function icono_balon(int $size = 24): string
{
    return <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="12" cy="12" r="10.5" stroke="currentColor" stroke-width="1.6"/>
    <path d="M2 12h20M12 1.5v21M4.5 4.5c3 3 3 12 0 15M19.5 4.5c-3 3-3 12 0 15" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
</svg>
SVG;
}

function redirigir_con_mensaje(string $ruta, string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    header('Location: ' . $ruta);
    exit;
}

function obtener_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
