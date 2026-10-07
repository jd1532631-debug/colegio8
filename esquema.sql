-- ============================================================
--  Colegio8 — Esquema de base de datos (MySQL / MariaDB)
--  Charset: utf8mb4 · Engine: InnoDB
--
--  Este archivo crea TODAS las tablas del sistema, con sus
--  claves foráneas y restricciones. NO contiene datos de
--  ejemplo: es el esquema de producción.
--  (Para probar en desarrollo, ver database/README en datos_prueba_dev.sql)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS colegio8
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE colegio8;

-- ------------------------------------------------------------
--  CUENTAS Y PERSONAS
-- ------------------------------------------------------------

-- Cuentas de postulantes / lectores (familias: tutor responsable).
-- Cada cuenta se vincula 1 a 1 con un alumno postulado (tabla alumnos).
CREATE TABLE usuarios (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario       VARCHAR(60)  NOT NULL,
  email         VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,       -- bcrypt, nunca texto plano
  fecha_alta    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_usuario (usuario),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas del personal de secretaría.
CREATE TABLE secretarias (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario        VARCHAR(60)  NOT NULL,
  nombre_completo VARCHAR(120) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,       -- bcrypt
  fecha_alta     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_secretarias_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas de administración de biblioteca.
CREATE TABLE bibliotecarios (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario        VARCHAR(60)  NOT NULL,
  nombre_completo VARCHAR(120) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,       -- bcrypt
  fecha_alta     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bibliotecarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  INSCRIPCIONES
-- ------------------------------------------------------------

-- Catálogo de cursos (vacantes): año + turno + división.
-- La ocupación NO se guarda acá: se cuenta siempre en el momento cuántos
-- alumnos tienen asignado el curso (alumnos.vacante_id es la única fuente
-- de verdad). Los triggers de más abajo protegen que nunca supere cupo_total.
CREATE TABLE vacantes (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  anio          TINYINT UNSIGNED NOT NULL,
  turno         ENUM('manana','tarde') NOT NULL,
  division      CHAR(1) NOT NULL,
  cupo_total    INT UNSIGNED NOT NULL,
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_vacantes_curso (anio, turno, division),
  CONSTRAINT chk_vacantes_cupo_total CHECK (cupo_total > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de cada alumno postulado. Vinculado 1 a 1 con una cuenta (usuarios).
CREATE TABLE alumnos (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id      INT UNSIGNED NOT NULL,
  nombre          VARCHAR(80)  NOT NULL,
  apellido        VARCHAR(80)  NOT NULL,
  dni             VARCHAR(20)  NULL,
  telefono        VARCHAR(30)  NULL,
  fecha_nacimiento DATE        NULL,
  anio_postulado  TINYINT UNSIGNED NULL,
  vacante_id      INT UNSIGNED NULL,          -- división asignada al ser inscripto
  fecha_alta      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_alumnos_usuario (usuario_id),
  UNIQUE KEY uq_alumnos_dni (dni),
  KEY idx_alumnos_vacante (vacante_id),
  KEY idx_alumnos_nombre (apellido, nombre),
  CONSTRAINT fk_alumnos_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_alumnos_vacante FOREIGN KEY (vacante_id)
    REFERENCES vacantes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tutor/responsable del alumno.
CREATE TABLE tutores (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  alumno_id       INT UNSIGNED NOT NULL,
  nombre          VARCHAR(80)  NOT NULL,
  apellido        VARCHAR(80)  NOT NULL,
  dni             VARCHAR(20)  NOT NULL,
  fecha_nacimiento DATE        NULL,
  telefono        VARCHAR(30)  NOT NULL,
  direccion       VARCHAR(180) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tutores_alumno (alumno_id),
  UNIQUE KEY uq_tutores_dni (dni),
  CONSTRAINT fk_tutores_alumno FOREIGN KEY (alumno_id)
    REFERENCES alumnos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitudes de inscripción (una por alumno). El estado vive acá;
-- ante una corrección y reenvío vuelve a 'recibida'.
CREATE TABLE solicitudes (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  alumno_id       INT UNSIGNED NOT NULL,
  estado          ENUM('recibida','en_revision','aprobada','rechazada') NOT NULL DEFAULT 'recibida',
  motivo_rechazo  VARCHAR(500) NULL,
  fecha_presentacion DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_resolucion DATETIME    NULL,
  secretaria_id   INT UNSIGNED NULL,
  en_revision_hasta DATETIME   NULL,  -- vence el "En revisión" si el secretario deja el expediente
  PRIMARY KEY (id),
  KEY idx_solicitudes_estado (estado),
  KEY idx_solicitudes_alumno (alumno_id),
  CONSTRAINT fk_solicitudes_alumno FOREIGN KEY (alumno_id)
    REFERENCES alumnos (id) ON DELETE CASCADE,
  CONSTRAINT fk_solicitudes_secretaria FOREIGN KEY (secretaria_id)
    REFERENCES secretarias (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auditoría de cambios de estado de cada solicitud (historial).
CREATE TABLE historial_solicitudes (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  solicitud_id  INT UNSIGNED NOT NULL,
  estado        VARCHAR(20)  NOT NULL,
  motivo        VARCHAR(500) NULL,
  secretaria_id INT UNSIGNED NULL,
  fecha         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_historial_solicitud (solicitud_id),
  CONSTRAINT fk_historial_solicitud FOREIGN KEY (solicitud_id)
    REFERENCES solicitudes (id) ON DELETE CASCADE,
  CONSTRAINT fk_historial_secretaria FOREIGN KEY (secretaria_id)
    REFERENCES secretarias (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documentación subida (DNI alumno, DNI tutor, otros).
-- solicitud_id es NULL mientras el postulante redacta su solicitud
-- (borrador_token identifica esos archivos temporales).
CREATE TABLE documentos (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  solicitud_id    INT UNSIGNED NULL,
  borrador_token  VARCHAR(64)  NULL,
  tipo            ENUM('dni_alumno_frente','dni_alumno_dorso','dni_tutor_frente','dni_tutor_dorso','otro') NOT NULL,
  nombre_original VARCHAR(255) NOT NULL,
  ruta            VARCHAR(255) NOT NULL,
  estado          ENUM('recibido','validado','reemplazado') NOT NULL DEFAULT 'recibido',
  subido_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_documentos_solicitud (solicitud_id),
  KEY idx_documentos_borrador (borrador_token),
  CONSTRAINT fk_documentos_solicitud FOREIGN KEY (solicitud_id)
    REFERENCES solicitudes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lista de espera de alumnos para un curso lleno (con posición numérica).
CREATE TABLE lista_espera (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  alumno_id     INT UNSIGNED NOT NULL,
  vacante_id    INT UNSIGNED NOT NULL,
  posicion      INT UNSIGNED NOT NULL,
  fecha_ingreso DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_espera_alumno (alumno_id),
  UNIQUE KEY uq_espera_posicion (vacante_id, posicion),
  KEY idx_espera_vacante (vacante_id),
  CONSTRAINT fk_espera_alumno FOREIGN KEY (alumno_id)
    REFERENCES alumnos (id) ON DELETE CASCADE,
  CONSTRAINT fk_espera_vacante FOREIGN KEY (vacante_id)
    REFERENCES vacantes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración institucional (clave/valor). Incluye el código de
-- seguridad de secretaría. No se edita desde una pantalla del sistema.
CREATE TABLE configuracion (
  clave VARCHAR(60)  NOT NULL,
  valor VARCHAR(255) NOT NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion (clave, valor) VALUES
  ('nombre_institucion', 'Colegio8'),
  ('codigo_secretaria',  'COLEGIO8-2026'),          -- código institucional fijo (ver README)
  ('sitio_oficial',      'https://www.escuelasargentinas.com/colegio-secundario-provincial-n08-la-rioja-460080900'), -- sitio que abre el logo (editable)
  ('limite_archivo_mb',  '5'),                      -- tamaño máximo por archivo subido
  ('dias_prestamo',      '15'),                     -- duración estándar de un préstamo
  ('alta_inicial_secretaria',    '1'),              -- 1 = permitir alta inicial de secretaría (si no hay cuentas)
  ('alta_inicial_bibliotecario', '1'),              -- 1 = permitir alta inicial de bibliotecario (si no hay cuentas)
  ('telefono_contacto',  '(0380) 4569213'),          -- teléfono institucional (footer de los paneles)
  ('email_contacto',     'colegioprovincial8@hotmail.com'),
  ('facebook_url',       'https://www.facebook.com/p/Colegio-Provincial-N8-100063614375091/?locale=es_LA'),
  ('instagram_url',      'https://www.instagram.com/colegioprovincialn8/'),
  ('youtube_url',        'https://www.youtube.com/@radiojoven87.9colegion8');

-- Sesiones persistentes ("Recordar mi cuenta") de los roles públicos.
-- Se guarda solo un hash del token: si la tabla se filtra, los tokens no
-- sirven. selector + token_hash = credencial; al usarse se rota el token.
CREATE TABLE recordar_sesiones (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rol           VARCHAR(20)  NOT NULL,              -- 'postulante' | 'lector'
  rol_id        INT UNSIGNED NOT NULL,              -- id de la cuenta (usuarios / biblioteca_profesores)
  selector      VARCHAR(24)  NOT NULL,
  token_hash    VARCHAR(64)  NOT NULL,              -- sha256 hex del token real
  fecha_creacion DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_recordar_selector (selector),
  KEY idx_recordar_rol (rol, rol_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  MÓDULO DE BIBLIOTECA
-- ------------------------------------------------------------

-- Catálogo de libros.
CREATE TABLE biblioteca_libros (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo                VARCHAR(160) NOT NULL,
  autor                 VARCHAR(120) NOT NULL,
  editorial             VARCHAR(120) NULL,
  genero                VARCHAR(60)  NULL,
  isbn                  VARCHAR(30)  NULL,
  codigo_interno        VARCHAR(30)  NOT NULL,
  portada               VARCHAR(255) NULL,          -- ruta de imagen en servidor
  sinopsis              TEXT         NULL,
  ejemplares_total      INT UNSIGNED NOT NULL,
  ejemplares_disponibles INT UNSIGNED NOT NULL,
  activo                TINYINT(1)   NOT NULL DEFAULT 1,
  fecha_alta            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_libros_codigo (codigo_interno),
  UNIQUE KEY uq_libros_isbn (isbn),
  KEY idx_libros_titulo (titulo),
  KEY idx_libros_autor (autor),
  KEY idx_libros_genero (genero),
  CONSTRAINT chk_libros_disp CHECK (ejemplares_disponibles >= 0 AND ejemplares_disponibles <= ejemplares_total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profesores del módulo de biblioteca. No tienen cuenta en `usuarios`;
-- sus credenciales de lector viven en esta misma tabla.
CREATE TABLE biblioteca_profesores (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(80)  NOT NULL,
  apellido      VARCHAR(80)  NOT NULL,
  dni           VARCHAR(20)  NOT NULL,
  email         VARCHAR(120) NULL,
  telefono      VARCHAR(30)  NULL,
  usuario       VARCHAR(60)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,              -- bcrypt
  activo        TINYINT(1)   NOT NULL DEFAULT 1,    -- acceso como lector
  fecha_alta    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_profesores_dni (dni),
  UNIQUE KEY uq_profesores_usuario (usuario),
  UNIQUE KEY uq_profesores_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Préstamos. La persona es alumno (del módulo de inscripciones)
-- o profesor (de biblioteca_profesores). Se respeta con el CHECK.
CREATE TABLE biblioteca_prestamos (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo_persona     ENUM('alumno','profesor') NOT NULL,
  alumno_id        INT UNSIGNED NULL,
  profesor_id      INT UNSIGNED NULL,
  libro_id         INT UNSIGNED NOT NULL,
  fecha_prestamo   DATE NOT NULL,
  fecha_limite     DATE NOT NULL,
  fecha_devolucion DATE NULL,
  estado           ENUM('activo','devuelto','vencido','cancelado') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (id),
  KEY idx_prestamos_estado (estado),
  KEY idx_prestamos_libro (libro_id),
  KEY idx_prestamos_alumno (alumno_id),
  KEY idx_prestamos_profesor (profesor_id),
  CONSTRAINT chk_prestamos_persona CHECK (
    (tipo_persona = 'alumno'   AND alumno_id   IS NOT NULL AND profesor_id IS NULL) OR
    (tipo_persona = 'profesor' AND profesor_id IS NOT NULL AND alumno_id   IS NULL)
  ),
  CONSTRAINT fk_prestamos_alumno FOREIGN KEY (alumno_id)
    REFERENCES alumnos (id) ON DELETE RESTRICT,
  CONSTRAINT fk_prestamos_profesor FOREIGN KEY (profesor_id)
    REFERENCES biblioteca_profesores (id) ON DELETE RESTRICT,
  CONSTRAINT fk_prestamos_libro FOREIGN KEY (libro_id)
    REFERENCES biblioteca_libros (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedidos/peticiones de libros hechos por lectores.
CREATE TABLE biblioteca_peticiones (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo_persona    ENUM('alumno','profesor') NOT NULL,
  alumno_id       INT UNSIGNED NULL,
  profesor_id     INT UNSIGNED NULL,
  libro_id        INT UNSIGNED NOT NULL,
  estado          ENUM('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
  motivo          VARCHAR(500) NULL,              -- motivo/nota de la solicitud
  motivo_rechazo  VARCHAR(500) NULL,
  prestamo_id     INT UNSIGNED NULL,               -- préstamo generado al aceptar
  fecha_peticion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_peticiones_estado (estado),
  KEY idx_peticiones_libro (libro_id),
  KEY idx_peticiones_alumno (alumno_id),
  KEY idx_peticiones_profesor (profesor_id),
  CONSTRAINT chk_peticiones_persona CHECK (
    (tipo_persona = 'alumno'   AND alumno_id   IS NOT NULL AND profesor_id IS NULL) OR
    (tipo_persona = 'profesor' AND profesor_id IS NOT NULL AND alumno_id   IS NULL)
  ),
  CONSTRAINT fk_peticiones_alumno FOREIGN KEY (alumno_id)
    REFERENCES alumnos (id) ON DELETE RESTRICT,
  CONSTRAINT fk_peticiones_profesor FOREIGN KEY (profesor_id)
    REFERENCES biblioteca_profesores (id) ON DELETE RESTRICT,
  CONSTRAINT fk_peticiones_libro FOREIGN KEY (libro_id)
    REFERENCES biblioteca_libros (id) ON DELETE RESTRICT,
  CONSTRAINT fk_peticiones_prestamo FOREIGN KEY (prestamo_id)
    REFERENCES biblioteca_prestamos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favoritos ("me gusta") de un lector sobre un libro.
CREATE TABLE biblioteca_favoritos (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo_persona  ENUM('alumno','profesor') NOT NULL,
  alumno_id     INT UNSIGNED NULL,
  profesor_id   INT UNSIGNED NULL,
  libro_id      INT UNSIGNED NOT NULL,
  fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_favoritos_libro (libro_id),
  KEY idx_favoritos_alumno (alumno_id),
  KEY idx_favoritos_profesor (profesor_id),
  CONSTRAINT chk_favoritos_persona CHECK (
    (tipo_persona = 'alumno'   AND alumno_id   IS NOT NULL AND profesor_id IS NULL) OR
    (tipo_persona = 'profesor' AND profesor_id IS NOT NULL AND alumno_id   IS NULL)
  ),
  CONSTRAINT fk_favoritos_libro FOREIGN KEY (libro_id)
    REFERENCES biblioteca_libros (id) ON DELETE CASCADE,
  CONSTRAINT fk_favoritos_alumno FOREIGN KEY (alumno_id)
    REFERENCES alumnos (id) ON DELETE CASCADE,
  CONSTRAINT fk_favoritos_profesor FOREIGN KEY (profesor_id)
    REFERENCES biblioteca_profesores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de incidentes (amonestaciones) asociadas a un alumno.
CREATE TABLE biblioteca_amonestaciones (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  alumno_id       INT UNSIGNED NOT NULL,
  tipo            ENUM('tardanza','mal_estado','perdida','otro') NOT NULL,
  descripcion     TEXT NOT NULL,
  fecha           DATE NOT NULL,
  bibliotecario_id INT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY idx_amonestaciones_alumno (alumno_id),
  CONSTRAINT fk_amonestaciones_alumno FOREIGN KEY (alumno_id)
    REFERENCES alumnos (id) ON DELETE CASCADE,
  CONSTRAINT fk_amonestaciones_biblio FOREIGN KEY (bibliotecario_id)
    REFERENCES bibliotecarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de avisos de morosidad por préstamo vencido.
CREATE TABLE biblioteca_avisos (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  prestamo_id     INT UNSIGNED NOT NULL,
  bibliotecario_id INT UNSIGNED NULL,
  fecha_aviso     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  medio           ENUM('telefono','email','presencial','otro') NOT NULL DEFAULT 'otro',
  notas           VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_avisos_prestamo (prestamo_id),
  CONSTRAINT fk_avisos_prestamo FOREIGN KEY (prestamo_id)
    REFERENCES biblioteca_prestamos (id) ON DELETE CASCADE,
  CONSTRAINT fk_avisos_bibliotecario FOREIGN KEY (bibliotecario_id)
    REFERENCES bibliotecarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  CUPO REAL DE CADA CURSO (integridad de datos)
--  La ocupación se obtiene contando alumnos con alumnos.vacante_id = curso.
--  La app ya respeta el tope al asignar/mover/liberar; estos triggers son la
--  red de seguridad a nivel base para que ningún camino pueda dejar un curso
--  con MÁS alumnos que su cupo_total (ni insertando ni cambiando de curso).
-- ----------------------------------------------------------------------------
DELIMITER $$
CREATE TRIGGER trg_alumnos_cupo_insert BEFORE INSERT ON alumnos
FOR EACH ROW
BEGIN
  IF NEW.vacante_id IS NOT NULL THEN
    IF (SELECT cupo_total FROM vacantes WHERE id = NEW.vacante_id)
       < (SELECT COUNT(*) FROM alumnos WHERE vacante_id = NEW.vacante_id) + 1 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El curso superaría su cupo máximo de alumnos.';
    END IF;
  END IF;
END$$

CREATE TRIGGER trg_alumnos_cupo_update AFTER UPDATE ON alumnos
FOR EACH ROW
BEGIN
  IF NEW.vacante_id IS NOT NULL AND NOT (NEW.vacante_id <=> OLD.vacante_id) THEN
    IF (SELECT cupo_total FROM vacantes WHERE id = NEW.vacante_id)
       < (SELECT COUNT(*) FROM alumnos WHERE vacante_id = NEW.vacante_id) THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El curso superaría su cupo máximo de alumnos.';
    END IF;
  END IF;
END$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;