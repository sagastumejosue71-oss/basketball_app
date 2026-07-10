# Copa Estrellas — Liga Femenina de Basketball

Plataforma de gestión y sitio público para un campeonato femenino de basketball: tabla de posiciones, calendario de encuentros, perfiles de equipo, patrocinadores, perfil del organizador con comentarios anónimos, y un panel de administración completo.

Los datos (equipos, partidos, patrocinadores, comentarios, torneo, organizador) se guardan en una base de datos **PostgreSQL**. Las imágenes subidas (escudos, logos, foto de perfil) se guardan como datos binarios dentro de la misma base de datos — así todo persiste correctamente incluso en hostings sin disco persistente como Render.

## Requisitos

- PHP **8.1 o superior** (usa `match`, `str_starts_with`, tipado estricto)
- Extensión `pdo_pgsql` habilitada
- Una base de datos PostgreSQL accesible por internet (ver sección Neon abajo)

## Correr en local

1. Copia `.env.example` a `.env` y pon tu connection string real de PostgreSQL:
   ```
   DATABASE_URL=postgresql://usuario:password@host.neon.tech/basedatos?sslmode=require
   ```
2. Crea las tablas y (opcionalmente) migra los datos de ejemplo:
   ```
   php scripts/migrar_json_a_postgres.php
   ```
3. Levanta el servidor:
   ```
   php -S localhost:8000 router.php
   ```

## Acceso al panel del organizador

- URL: `/login.php`
- Usuario: `admin`
- Contraseña: `Estrellas2026`

⚠️ **Cambia esta contraseña desde "Mi Perfil" antes de publicar el sitio.**

## Estructura del proyecto

```
schema.sql               Esquema de la base de datos PostgreSQL
scripts/                 Script de migración (JSON antiguo → PostgreSQL)
includes/                Lógica compartida (auth, acceso a datos, cálculo de tabla, helpers, filtro de groserías)
assets/                  CSS y JS (las imágenes ya no se guardan aquí, van en la base de datos)
admin/                   Panel del organizador (protegido por sesión)
imagen.php               Sirve las imágenes guardadas en la base de datos
*.php (raíz)              Páginas públicas del sitio
```

## Base de datos gratuita: Neon

1. Crea una cuenta gratis en [neon.tech](https://neon.tech) y un proyecto nuevo.
2. Copia el **Connection string** que te dan (empieza con `postgresql://...`) — lo vas a necesitar tanto en local (`.env`) como en Render.
3. El plan gratuito de Neon no tiene fecha de expiración fija (a diferencia de la Postgres gratuita de Render, que se borra a los 90 días); solo se "duerme" tras un rato sin uso y despierta sola con la siguiente visita.

## Desplegar en Render (gratis)

Este proyecto incluye un `Dockerfile` porque Render no ejecuta PHP de forma nativa.

1. En [render.com](https://render.com), crea una cuenta (puedes usar tu GitHub) y conecta el repositorio `basketball_app`.
2. **New > Web Service**, selecciona el repo, y Render detectará el `Dockerfile` automáticamente (Runtime: Docker).
3. Plan: **Free**.
4. En "Environment", agrega la variable:
   - `DATABASE_URL` = tu connection string de Neon
5. Deploy. La primera vez que despliegues, entra una sola vez por SSH/Shell de Render (o corre el script en local apuntando a la misma base) para ejecutar:
   ```
   php scripts/migrar_json_a_postgres.php
   ```
   Esto crea las tablas. Si ya lo corriste en local contra la misma base de datos de Neon, no hace falta repetirlo.
6. Entra a `https://tu-servicio.onrender.com/login.php` y **cambia la contraseña por defecto**.

### Nota sobre el plan gratuito de Render

El servicio se "duerme" tras ~15 minutos sin visitas y tarda unos segundos en despertar con la siguiente visita — normal en el plan gratuito, no es un error.
