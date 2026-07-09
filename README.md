# Copa Estrellas — Liga Femenina de Basketball

Plataforma de gestión y sitio público para un campeonato femenino de basketball: tabla de posiciones, calendario de encuentros, perfiles de equipo, patrocinadores, perfil del organizador con comentarios anónimos, y un panel de administración completo.

No usa base de datos: todos los datos se guardan en archivos JSON dentro de `data/`, con bloqueo de archivo para evitar corrupción por escrituras simultáneas.

## Requisitos

- PHP **8.1 o superior** (usa `match`, `str_starts_with`, tipado estricto)
- Extensión `fileinfo` habilitada (para validar imágenes subidas) — viene activada por defecto en la mayoría de hostings
- Que el proceso de PHP pueda **escribir archivos** en `data/` y `assets/img/` (importante: hostings "serverless" como Vercel, Netlify o el plan gratuito de Render/Railway **no sirven**, porque su disco es efímero y se borrarían los datos)

## Correr en local

```
php -S localhost:8000
```

Y abre `http://localhost:8000`.

## Acceso al panel del organizador

- URL: `/login.php`
- Usuario: `admin`
- Contraseña: `Estrellas2026`

⚠️ **Cambia esta contraseña desde "Mi Perfil" antes de publicar el sitio.**

## Estructura del proyecto

```
data/                   Archivos JSON (equipos, partidos, patrocinadores, torneo, organizador, comentarios)
includes/                Lógica compartida (auth, acceso a datos, cálculo de tabla, helpers, filtro de groserías)
assets/                  CSS, JS e imágenes subidas
admin/                   Panel del organizador (protegido por sesión)
*.php (raíz)             Páginas públicas del sitio
```

## Desplegar en un hosting gratuito de PHP (tipo InfinityFree)

1. Crea la cuenta y el sitio en tu proveedor de hosting gratuito.
2. En el panel de control, selecciona **PHP 8.1 o superior** como versión de PHP del sitio (por defecto suelen traer una versión vieja).
3. Sube **todo el contenido** de este proyecto a la carpeta raíz web del hosting (normalmente `htdocs` o `public_html`) vía FTP o el administrador de archivos. `index.php` debe quedar directamente dentro de esa carpeta, no en una subcarpeta.
4. Verifica permisos de escritura en `data/` y `assets/img/` (y sus subcarpetas `equipos/`, `patrocinadores/`, `torneo/`, `organizador/`). Si subir un logo o guardar un resultado falla, prueba poniendo esas carpetas en permisos `755`; si el hosting lo exige, `777`.
5. Entra a `tu-sitio.com/login.php` y **cambia la contraseña por defecto** de inmediato.
6. Prueba crear un equipo o capturar un resultado para confirmar que la escritura a disco funciona en ese hosting.

### Importante sobre actualizaciones futuras

Este proyecto no se despliega automáticamente desde GitHub — subir cambios a GitHub no actualiza el sitio en línea por sí solo. Cuando quieras subir una actualización de código (nuevas funciones, corrección de bugs) al hosting:

- **Haz un respaldo de la carpeta `data/` y `assets/img/` del servidor antes de sobrescribir archivos**, para no perder los equipos, partidos y comentarios reales que el organizador ya haya cargado.
- Sube solo los archivos de código que cambiaron, o si subes todo, restaura después la carpeta `data/` desde tu respaldo.
