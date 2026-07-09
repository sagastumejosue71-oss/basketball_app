-- Esquema PostgreSQL para Copa Estrellas — Liga Femenina de Basketball
-- Los IDs son asignados por la aplicación (no autoincrementales), igual que en la versión JSON.

CREATE TABLE IF NOT EXISTS equipos (
    id INTEGER PRIMARY KEY,
    nombre TEXT NOT NULL,
    ciudad TEXT NOT NULL DEFAULT '',
    sede TEXT NOT NULL DEFAULT '',
    entrenador TEXT NOT NULL DEFAULT '',
    fundacion TEXT NOT NULL DEFAULT '',
    color_primario TEXT NOT NULL DEFAULT '#7b2ff7',
    color_secundario TEXT NOT NULL DEFAULT '#ff6b35',
    logo TEXT NOT NULL DEFAULT '',
    descripcion TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS partidos (
    id INTEGER PRIMARY KEY,
    jornada INTEGER NOT NULL,
    equipo_local INTEGER NOT NULL,
    equipo_visitante INTEGER NOT NULL,
    fecha TEXT NOT NULL,
    hora TEXT NOT NULL,
    cancha TEXT NOT NULL DEFAULT '',
    estado TEXT NOT NULL DEFAULT 'programado',
    marcador_local INTEGER,
    marcador_visitante INTEGER
);

CREATE TABLE IF NOT EXISTS patrocinadores (
    id INTEGER PRIMARY KEY,
    nombre TEXT NOT NULL,
    nivel TEXT NOT NULL DEFAULT 'plata',
    url TEXT NOT NULL DEFAULT '',
    logo TEXT NOT NULL DEFAULT '',
    orden INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS comentarios (
    id INTEGER PRIMARY KEY,
    mensaje TEXT NOT NULL,
    fecha TEXT NOT NULL,
    leido INTEGER NOT NULL DEFAULT 0
);

-- Tablas "singleton": siempre tienen exactamente una fila (id = 1)
CREATE TABLE IF NOT EXISTS torneo (
    id INTEGER PRIMARY KEY DEFAULT 1,
    nombre TEXT NOT NULL DEFAULT '',
    subtitulo TEXT NOT NULL DEFAULT '',
    temporada TEXT NOT NULL DEFAULT '',
    descripcion TEXT NOT NULL DEFAULT '',
    sede_principal TEXT NOT NULL DEFAULT '',
    logo TEXT NOT NULL DEFAULT '',
    color_primario TEXT NOT NULL DEFAULT '#8b2fd9',
    color_secundario TEXT NOT NULL DEFAULT '#ff6b35',
    color_acento TEXT NOT NULL DEFAULT '#ffc93c',
    fecha_inicio TEXT NOT NULL DEFAULT '',
    fecha_fin TEXT NOT NULL DEFAULT '',
    formato TEXT NOT NULL DEFAULT '',
    instagram TEXT NOT NULL DEFAULT '',
    hero_frase TEXT NOT NULL DEFAULT '',
    CONSTRAINT torneo_singleton CHECK (id = 1)
);

-- Almacena las imágenes subidas (escudos, logos, foto de perfil) como datos binarios.
-- Se usa en vez de archivos en disco porque el plan gratuito de Render no tiene disco persistente:
-- cualquier archivo escrito en assets/img/ se perdería en el próximo reinicio o despliegue.
CREATE TABLE IF NOT EXISTS imagenes (
    id SERIAL PRIMARY KEY,
    mime TEXT NOT NULL,
    datos BYTEA NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS organizador (
    id INTEGER PRIMARY KEY DEFAULT 1,
    usuario TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    nombre TEXT NOT NULL DEFAULT '',
    cargo TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL DEFAULT '',
    telefono TEXT NOT NULL DEFAULT '',
    foto TEXT NOT NULL DEFAULT '',
    bio TEXT NOT NULL DEFAULT '',
    CONSTRAINT organizador_singleton CHECK (id = 1)
);
