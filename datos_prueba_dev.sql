-- ============================================================================
--  Colegio8 — DATOS DE PRUEBA — SOLO PARA DESARROLLO / PRUEBAS
--  ============================================================================
--  ⚠️  ADVERTENCIA: este archivo SIEMPRE deja contraseñas conocidas y datos
--  falsos. NO importarlo en la base real de una institución en producción.
--
--  CÓMO USARLO (en ese orden, nunca al revés):
--    1) Importar PRIMERO el esquema principal:  database\esquema.sql
--    2) Importar LUEGO este archivo (una sola vez): database\datos_prueba_dev.sql
--       mysql -u root < database\esquema.sql
--       mysql -u root < database\datos_prueba_dev.sql
--
--  Este archivo NO redefine tablas ni crea estructura: solo INSERT.
--  Pensado para correr una sola vez sobre una base recién creada con
--  esquema.sql. Si querés volver a importarlo, recreá la base desde cero:
--       mysql -u root -e "DROP DATABASE IF EXISTS colegio8;"
--       mysql -u root < database\esquema.sql
--       mysql -u root < database\datos_prueba_dev.sql
--
--  CONTENIDO:
--    · 1 cuenta de secretaría, 1 de bibliotecario y 2 profesores.
--    · 13 familias/alumnos (cada una con tutor y solicitud en distintos
--      estados: aprobada, en_revision, rechazada y recibida), 12 cursos con
--      cupos realistas (uno lleno con lista de espera) e historial de pasos.
--    · 15 libros, préstamos activos/vencidos/devueltos, pedidos
--      pendientes/aceptados/rechazados, favoritos, amonestaciones y avisos.
--
--  CONTRASEÑAS (guardadas hasheadas con bcrypt en la base; acá y en el
--  README quedan en texto plano SOLO para que quien importe pueda entrar):
--    secretaria    -> Secretaria2026      bibliotecario -> Biblioteca2026
--    carlos_gomez  -> ProfeCarlos2026     ana_perez     -> ProfeAna2026
--    garcia        -> Sofia2026           lopez         -> Mateo2026
--    perez         -> Camila2026          fernandez     -> Maria2026
--    gutierrez     -> Lucas2026           rodriguez     -> Emma2026
--    diaz          -> Valentino2026       acosta        -> Juana2026
--    romero        -> Thiago2026          sosa          -> Mia2026
--    huertas       -> Bruno2026           cabrera       -> Isabella2026
--    silva         -> Franco2026
-- ============================================================================

SET NAMES utf8mb4;
USE colegio8;

-- ----------------------------------------------------------------------------
--  CUENTAS DE PERSONAL
-- ----------------------------------------------------------------------------

INSERT INTO secretarias (usuario, nombre_completo, password_hash, fecha_alta) VALUES
  ('secretaria', 'Graciela Verónica Ruiz', '$2y$10$AuazBwrFIMKMUuMgyuq8vuZOd14qfq9csbgPd.jdl3ici1lcJh92G', '2026-01-05 09:00:00');
-- ↑ contraseña: Secretaria2026

INSERT INTO bibliotecarios (usuario, nombre_completo, password_hash, fecha_alta) VALUES
  ('bibliotecario', 'Héctor Raúl Mendoza', '$2y$10$jnjeKJWn2EQSypODGo/DeuEBE3NVMvxr36IEhDca073vUGJ2Rb/t.', '2026-01-05 09:00:00');
-- ↑ contraseña: Biblioteca2026

INSERT INTO biblioteca_profesores (nombre, apellido, dni, email, telefono, usuario, password_hash, fecha_alta) VALUES
  ('Carlos',  'Gómez',  '20154378', 'carlos.gomez@colegio8.edu.ar',    '11-4455-0001', 'carlos_gomez', '$2y$10$GThIdvEfumYCu0tvqdMeVu68sP0FblljjS1VsXrRt7ggc9oKOwgbm', '2026-01-15 09:30:00'),
  ('Ana',     'Pérez',  '21789012', 'ana.perez@colegio8.edu.ar',       '11-4455-0002', 'ana_perez',    '$2y$10$4at3Wy4ZmhBw9f8axnrknuSq0GDHysLNCjLGLkVZGR5N1JgPwIZAW', '2026-01-15 09:30:00');
-- ↑ contraseñas: carlos_gomez -> ProfeCarlos2026 · ana_perez -> ProfeAna2026

-- ----------------------------------------------------------------------------
--  CURSOS / VACANTES (cupos realistas; 1ºA se llena más abajo con 35 alumnos)
--  NOTA: la ocupación NO se almacena: se cuenta en el momento cuántos alumnos
--  tienen asignado cada curso (alumnos.vacante_id). Por eso no hay cupo_ocupado.
-- ----------------------------------------------------------------------------

INSERT INTO vacantes (anio, turno, division, cupo_total) VALUES
  (1, 'manana', 'A', 35),   -- se llena con 35 alumnos reales generados abajo
  (1, 'manana', 'B', 35),   -- incluye a Sofía García (cuenta de prueba principal)
  (1, 'tarde',  'C', 35),   -- vacío
  (2, 'manana', 'A', 35),   -- incluye a Lucas Gutiérrez
  (2, 'tarde',  'B', 35),
  (3, 'manana', 'A', 35),   -- vacío
  (3, 'tarde',  'B', 35),
  (4, 'manana', 'A', 35),
  (4, 'tarde',  'B', 35),   -- incluye a Emma Rodríguez
  (5, 'manana', 'A', 35),
  (5, 'tarde',  'B', 35),
  (6, 'manana', 'A', 35);   -- incluye a Valentino Díaz

-- ----------------------------------------------------------------------------
--  CUENTAS DE FAMILIAS (usuarios) + ALUMNOS + TUTORES + SOLICITUDES
-- ----------------------------------------------------------------------------

INSERT INTO usuarios (usuario, email, password_hash, fecha_alta) VALUES
  ('garcia',     'sofia.garcia@correo.prueba',        '$2y$10$muVmVr6uM8MI8XM11pyn0u5qD2kYE//H7D75dWgdvqoxPjw6Zec6i', '2026-02-20 10:00:00'),
  ('lopez',      'mateo.lopez@correo.prueba',         '$2y$10$g/.0nQ4N.0HcnQmRSRht5O77/J8wxfAXVFND8P861mhfLVpTdakjK', '2026-08-28 11:12:00'),
  ('perez',      'camila.perez@correo.prueba',        '$2y$10$Q2N2AXziFhcGGFAFvoUW3.qgqd2Sthc.y6qSA3KBnSPLGajBYe9Vm', '2026-08-25 16:40:00'),
  ('fernandez',  'maria.fernandez@correo.prueba',     '$2y$10$Xl0zQFNccfr1ky0Qpjt3xexD/KMjb3GAp9OYZn9yKhk/7koukkzc.', '2026-08-22 09:55:00'),
  ('gutierrez',  'lucas.gutierrez@correo.prueba',     '$2y$10$z9ViPtaDNKdooVT9HVwhO.GxiLDjNfvxem2KE5JuHBsMMPWs6vpu.', '2026-02-18 12:30:00'),
  ('rodriguez',  'emma.rodriguez@correo.prueba',      '$2y$10$Ojdmja6K30LgtclQXTx.Q.OatDU4ER0MSjn3tNhCdu6oPEIGJU6/S', '2026-02-15 08:20:00'),
  ('diaz',       'valentino.diaz@correo.prueba',      '$2y$10$nDUKONn2r9mXgCuWRp8V8eHwfM3plq/vK/3Yz6H9SgNhEgUclpfvu', '2026-02-10 14:05:00'),
  ('acosta',     'juana.acosta@correo.prueba',        '$2y$10$WRv0TXG61gGDvvFawJv2Vu0IN8wnJZXZGUREfv73x4FeNExIjfxpG', '2026-08-28 09:30:00'),
  ('romero',     'thiago.romero@correo.prueba',       '$2y$10$JjL0NrC9h5Mdi0biQ0yZq.LlT.uxDPbNWhUrEchy1aM/CvUK0XiWG', '2026-08-26 17:15:00'),
  ('sosa',       'mia.sosa@correo.prueba',            '$2y$10$B9SbnWuLKyhVQFvi7sv.RO3R9lbwgy6KYXBnxZT4L3LWy5WbdFl2u', '2026-08-24 13:45:00'),
  ('huertas',    'bruno.huertas@correo.prueba',       '$2y$10$/3QQfJimf3fspC35HUUVL.Nwfm0qEriEQ.JD/NHFvr5t49vJqqKv.', '2026-08-27 10:10:00'),
  ('cabrera',    'isabella.cabrera@correo.prueba',    '$2y$10$12mETzeA5yroyZlUyQA5me0UuORjo2ry4LsIFwXbLPkMTwkRAI9vK', '2026-08-27 15:50:00'),
  ('silva',      'franco.silva@correo.prueba',        '$2y$10$gON09fRzSSmAIYS5rnUuJucpuN.oT0Rf9sKs6fFc8hIulXthOdPFm', '2026-08-28 12:25:00');
-- contraseñas (texto plano SOLO para desarrollo): ver tabla al inicio del archivo.

INSERT INTO alumnos (usuario_id, nombre, apellido, dni, telefono, fecha_nacimiento, anio_postulado, vacante_id) VALUES
  (1,  'Sofía',    'García',     '35987124', '11-4555-1123', '2013-05-14', 1,
     (SELECT id FROM vacantes WHERE anio=1 AND turno='manana' AND division='B')),   -- aprobada, 1ºB
  (2,  'Mateo',    'López',      '40232567', '11-4555-2234', '2014-02-20', 1, NULL), -- recibida
  (3,  'Camila',   'Pérez',      '38112945', '11-4555-3345', '2013-11-02', 1, NULL), -- en revisión
  (4,  'María',    'Fernández',  '39551874', '11-4555-4456', '2013-08-30', 1, NULL), -- rechazada
  (5,  'Lucas',    'Gutiérrez',  '37001452', '11-4555-5567', '2012-09-17', 2,
     (SELECT id FROM vacantes WHERE anio=2 AND turno='manana' AND division='A')),   -- aprobada, 2ºA
  (6,  'Emma',     'Rodríguez',  '36022871', '11-4555-6678', '2011-07-06', 4,
     (SELECT id FROM vacantes WHERE anio=4 AND turno='tarde' AND division='B')),    -- aprobada, 4ºB
  (7,  'Valentino','Díaz',       '35648920', '11-4555-7789', '2010-04-12', 6,
     (SELECT id FROM vacantes WHERE anio=6 AND turno='manana' AND division='A')),   -- aprobada, 6ºA
  (8,  'Juana',    'Acosta',     '40881763', '11-4555-8890', '2014-12-01', 1, NULL), -- recibida
  (9,  'Thiago',   'Romero',     '41235684', '11-4555-9901', '2014-03-25', 1, NULL), -- en revisión
  (10, 'Mía',      'Sosa',       '37661408', '11-4555-1010', '2012-10-09', 2, NULL), -- rechazada
  (11, 'Bruno',    'Huertas',    '41109832', '11-4555-1111', '2014-06-18', 1, NULL), -- lista de espera 1ºA
  (12, 'Isabella', 'Cabrera',    '40552367', '11-4555-1212', '2014-01-11', 1, NULL), -- lista de espera 1ºA
  (13, 'Franco',   'Silva',      '41872915', '11-4555-1313', '2014-08-22', 1, NULL); -- lista de espera 1ºA

INSERT INTO tutores (alumno_id, nombre, apellido, dni, fecha_nacimiento, telefono, direccion) VALUES
  (1,  'Claudia',  'Rossi',     '27455678', '1980-03-22', '11-4555-1123', 'Av. Rivadavia 1234, CABA'),
  (2,  'Jorge',    'López',     '25110983', '1978-11-05', '11-4555-2234', 'Calle Corrientes 567, CABA'),
  (3,  'Patricia', 'Pérez',     '29345671', '1985-07-14', '11-4555-3345', 'Av. San Martín 890, CABA'),
  (4,  'Roberto',  'Fernández', '22736590', '1975-01-30', '11-4555-4456', 'Calle Florida 234, CABA'),
  (5,  'Marcela',  'Gutiérrez', '30124578', '1987-09-09', '11-4555-5567', 'Av. Cabildo 4567, CABA'),
  (6,  'Andrés',   'Rodríguez', '25987643', '1982-05-18', '11-4555-6678', 'Calle Boedo 789, CABA'),
  (7,  'Silvia',   'Díaz',      '30221547', '1988-12-02', '11-4555-7789', 'Av. del Libertador 3456, CABA'),
  (8,  'Gabriel',  'Acosta',    '28123456', '1984-04-25', '11-4555-8890', 'Calle Santa Fe 678, CABA'),
  (9,  'Natalia',  'Romero',    '33098712', '1990-08-16', '11-4555-9901', 'Av. Belgrano 123, CABA'),
  (10, 'Luciano',  'Sosa',      '24781236', '1976-06-11', '11-4555-1010', 'Calle Montes de Oca 456, CABA'),
  (11, 'Carla',    'Huertas',   '31556789', '1989-10-28', '11-4555-1111', 'Av. Rivadavia 7890, CABA'),
  (12, 'Martín',   'Cabrera',   '27451290', '1981-02-07', '11-4555-1212', 'Calle Larrea 321, CABA'),
  (13, 'Vanesa',   'Silva',     '30874112', '1986-03-19', '11-4555-1313', 'Av. Juan B. Justo 654, CABA');

INSERT INTO solicitudes (alumno_id, estado, motivo_rechazo, fecha_presentacion, fecha_resolucion, secretaria_id) VALUES
  (1,  'aprobada',    NULL,                                       DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 1),
  (2,  'recibida',    NULL,                                       DATE_SUB(NOW(), INTERVAL 1 DAY),  NULL, NULL),
  (3,  'en_revision', NULL,                                       DATE_SUB(NOW(), INTERVAL 5 DAY),  NULL, 1),
  (4,  'rechazada',   'La documentación presentada no corresponde al alumno postulado.',
                                                                    DATE_SUB(NOW(), INTERVAL 9 DAY),  DATE_SUB(NOW(), INTERVAL 3 DAY),  1),
  (5,  'aprobada',    NULL,                                       DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY),  1),
  (6,  'aprobada',    NULL,                                       DATE_SUB(NOW(), INTERVAL 16 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY),  1),
  (7,  'aprobada',    NULL,                                       DATE_SUB(NOW(), INTERVAL 21 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY),  1),
  (8,  'recibida',    NULL,                                       DATE_SUB(NOW(), INTERVAL 1 DAY),  NULL, NULL),
  (9,  'en_revision', NULL,                                       DATE_SUB(NOW(), INTERVAL 4 DAY),  NULL, 1),
  (10, 'rechazada',   'El DNI del tutor no coincide con el de la documentación adjunta.',
                                                                    DATE_SUB(NOW(), INTERVAL 6 DAY),  DATE_SUB(NOW(), INTERVAL 2 DAY),  1),
  (11, 'aprobada',    NULL,                                       DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY),  1),
  (12, 'aprobada',    NULL,                                       DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY),  1),
  (13, 'aprobada',    NULL,                                       DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY),  1);

-- Auditoría de pasos (puebla el panel "Historial" de secretaría).
INSERT INTO historial_solicitudes (solicitud_id, estado, motivo, secretaria_id, fecha) VALUES
  (1,  'recibida',    NULL, NULL, DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (1,  'en_revision', NULL, 1,    DATE_SUB(NOW(), INTERVAL 11 DAY)),
  (1,  'aprobada',    NULL, 1,    DATE_SUB(NOW(), INTERVAL 10 DAY)),
  (3,  'recibida',    NULL, NULL, DATE_SUB(NOW(), INTERVAL 6 DAY)),
  (3,  'en_revision', NULL, 1,    DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (4,  'recibida',    NULL, NULL, DATE_SUB(NOW(), INTERVAL 9 DAY)),
  (4,  'rechazada',   'La documentación presentada no corresponde al alumno postulado.',
                                             1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (5,  'recibida',    NULL, NULL, DATE_SUB(NOW(), INTERVAL 13 DAY)),
  (5,  'aprobada',    NULL, 1,    DATE_SUB(NOW(), INTERVAL 6 DAY)),
  (6,  'recibida',    NULL, NULL, DATE_SUB(NOW(), INTERVAL 16 DAY)),
  (6,  'aprobada',    NULL, 1,    DATE_SUB(NOW(), INTERVAL 8 DAY)),
  (7,  'recibida',    NULL, NULL, DATE_SUB(NOW(), INTERVAL 21 DAY)),
  (7,  'aprobada',    NULL, 1,    DATE_SUB(NOW(), INTERVAL 9 DAY)),
  (9,  'recibida',    NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (9,  'en_revision', NULL, 1,    DATE_SUB(NOW(), INTERVAL 4 DAY)),
  (10, 'recibida',    NULL, NULL, DATE_SUB(NOW(), INTERVAL 6 DAY)),
  (10, 'rechazada',   'El DNI del tutor no coincide con el de la documentación adjunta.',
                                             1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Documentación de Sofía García (solicitud 1, aprobada): las 4 fotos del DNI.
INSERT INTO documentos (solicitud_id, borrador_token, tipo, nombre_original, ruta, estado, subido_en) VALUES
  (1, NULL, 'dni_alumno_frente', 'dni_alumno_frente.png', 'uploads/inscripciones/20260212-00aa00dni-alumno-frente.png', 'validado', DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (1, NULL, 'dni_alumno_dorso',  'dni_alumno_dorso.png',  'uploads/inscripciones/20260212-00bb00dni-alumno-dorso.png',  'validado', DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (1, NULL, 'dni_tutor_frente',  'dni_tutor_frente.png',  'uploads/inscripciones/20260212-00cc00dni-tutor-frente.png',  'validado', DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (1, NULL, 'dni_tutor_dorso',   'dni_tutor_dorso.png',   'uploads/inscripciones/20260212-00dd00dni-tutor-dorso.png',   'validado', DATE_SUB(NOW(), INTERVAL 12 DAY));

-- Lista de espera del curso 1ºA (lleno).
INSERT INTO lista_espera (alumno_id, vacante_id, posicion, fecha_ingreso) VALUES
  (11, (SELECT id FROM vacantes WHERE anio=1 AND turno='manana' AND division='A'), 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (12, (SELECT id FROM vacantes WHERE anio=1 AND turno='manana' AND division='A'), 2, DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (13, (SELECT id FROM vacantes WHERE anio=1 AND turno='manana' AND division='A'), 3, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ----------------------------------------------------------------------------
--  CUPO REAL DE 1ºA (curso "lleno" con lista de espera): 35 alumnos REALES
--  asignados a ese curso. La ocupación mostrada sale de contar estos alumnos;
--  por eso el detalle del curso muestra exactamente 35, nunca un número fijo.
--  Cada uno tiene cuenta (usuario), tutor y solicitud aprobada de respaldo.
--  Contraseña de estas cuentas generadas: Sofia2026 (solo desarrollo).
-- ----------------------------------------------------------------------------

INSERT INTO usuarios (usuario, email, password_hash, fecha_alta)
SELECT CONCAT('f1a', LPAD(t.i, 2, '0')),
       CONCAT('f1a', LPAD(t.i, 2, '0'), '@correo.prueba'),
       '$2y$10$muVmVr6uM8MI8XM11pyn0u5qD2kYE//H7D75dWgdvqoxPjw6Zec6i',  -- Sofia2026
       DATE_SUB(NOW(), INTERVAL 60 DAY)
FROM (WITH RECURSIVE n1a AS (SELECT 1 AS i UNION ALL SELECT i + 1 FROM n1a WHERE i < 35) SELECT i FROM n1a) t;

INSERT INTO alumnos (usuario_id, nombre, apellido, dni, telefono, fecha_nacimiento, anio_postulado, vacante_id)
SELECT u.id,
       ELT(t.i, 'Agustina','Benjamín','Camila','Dante','Emilia','Felipe','Guadalupe','Hugo','Irene','Julián',
               'Lara','Mateo','Nadia','Olga','Pablo','Quimey','Rocío','Santiago','Teresa','Ulises',
               'Valentina','Walter','Ximena','Yanina','Zoe','Bruno','Cecilia','Diego','Elena','Franco',
               'Gabriela','Hernán','Inés','Joaquín','Karina'),
       ELT(t.i, 'Álvarez','Benítez','Castro','Domínguez','Escobar','Ferreyra','Giménez','Heredia','Ibarra','Juárez',
               'Kessler','Luna','Mansilla','Navarro','Ojeda','Páez','Quiroga','Roldán','Salazar','Torres',
               'Ulloa','Vázquez','Wagner','Yáñez','Zanetti','Acuña','Bermúdez','Cáceres','Estrada','Figueroa',
               'González','Herranz','Iglesias','Jiménez','Jara'),
       CONCAT('53', LPAD(t.i + 1000, 4, '0')),
       CONCAT('11-4', LPAD(t.i + 100, 3, '0'), '-', LPAD(t.i, 4, '0')),
       DATE_SUB(NOW(), INTERVAL (12 + t.i) * 365 DAY),
       1,
       (SELECT id FROM vacantes WHERE anio=1 AND turno='manana' AND division='A')
FROM (WITH RECURSIVE n1a AS (SELECT 1 AS i UNION ALL SELECT i + 1 FROM n1a WHERE i < 35) SELECT i FROM n1a) t
JOIN usuarios u ON u.usuario = CONCAT('f1a', LPAD(t.i, 2, '0'));

INSERT INTO tutores (alumno_id, nombre, apellido, dni, fecha_nacimiento, telefono, direccion)
SELECT a.id,
       ELT(t.i, 'Marcela','Jorge','Patricia','Roberto','Claudia','Andrés','Silvia','Gabriel','Natalia','Luciano',
               'Carla','Martín','Vanesa','Diego','Romina','Ezequiel','Florencia','Guillermo','Héctor','Ivana',
               'José','Karina','Leandro','Mónica','Nicolás','Olga','Pablo','Queta','Ramiro','Susana',
               'Tomás','Ursula','Valeria','Walter','Yolanda'),
       'Familiar 1ºA',
       CONCAT('52', LPAD(t.i + 1000, 4, '0')),
       DATE_SUB(NOW(), INTERVAL 9000 DAY),
       a.telefono,
       CONCAT('Dirección de ejemplo ', LPAD(t.i, 2, '0'))
FROM (WITH RECURSIVE n1a AS (SELECT 1 AS i UNION ALL SELECT i + 1 FROM n1a WHERE i < 35) SELECT i FROM n1a) t
JOIN alumnos a ON a.dni = CONCAT('53', LPAD(t.i + 1000, 4, '0'));

INSERT INTO solicitudes (alumno_id, estado, fecha_presentacion, fecha_resolucion, secretaria_id)
SELECT a.id, 'aprobada',
       DATE_SUB(NOW(), INTERVAL (60 - t.i) DAY),
       DATE_SUB(NOW(), INTERVAL (50 - t.i) DAY),
       1
FROM (WITH RECURSIVE n1a AS (SELECT 1 AS i UNION ALL SELECT i + 1 FROM n1a WHERE i < 35) SELECT i FROM n1a) t
JOIN alumnos a ON a.dni = CONCAT('53', LPAD(t.i + 1000, 4, '0'));

-- ----------------------------------------------------------------------------
--  CATÁLOGO DE LIBROS (15 títulos variados)
--  ejemplares_disponibles es coherente con los préstamos de abajo.
-- ----------------------------------------------------------------------------

INSERT INTO biblioteca_libros (titulo, autor, editorial, genero, isbn, codigo_interno, sinopsis, ejemplares_total, ejemplares_disponibles) VALUES
  ('Cien años de soledad',               'Gabriel García Márquez',      'Sudamericana', 'Novela',           '9788437604947', 'B0001', 'La saga de la familia Buendía en el mítico Macondo.', 3, 2),
  ('El Principito',                      'Antoine de Saint-Exupéry',    'Salamandra',   'Fábula',           '9789878000259', 'B0002', 'Un aviador varado conoce a un pequeño príncipe.', 4, 4),
  ('Rayuela',                            'Julio Cortázar',              'Alfaguara',    'Novela',           '9789870416159', 'B0003', 'La historia puede leerse de principio a fin o a saltos.', 3, 3),
  ('Martín Fierro',                      'José Hernández',              'Colihue',      'Poema',            '9789505810596', 'B0004', 'El gaucho Martín Fierro y su canto de la vida en la pampa.', 4, 3),
  ('Harry Potter y la piedra filosofal', 'J. K. Rowling',               'Salamandra',   'Fantasía',         '9789878000242', 'B0005', 'Un niño descubre que es un mago en el colegio Hogwarts.', 6, 6),
  ('El señor de los anillos',            'J. R. R. Tolkien',            'Minotauro',    'Fantasía',         '9789505472299', 'B0006', 'La guerra por el Anillo Único en la Tierra Media.', 3, 3),
  ('Ficciones',                          'Jorge Luis Borges',           'Emecé',        'Cuento',           '9789500426929', 'B0007', 'Cuentos laberínticos de bibliotecas, espejos y tigres.', 3, 3),
  ('La casa de los espíritus',           'Isabel Allende',              'Sudamericana', 'Novela',           '9789500729714', 'B0008', 'Tres generaciones de la familia Trueba.', 2, 2),
  ('Crónica de una muerte anunciada',    'Gabriel García Márquez',      'Debolsillo',   'Novela',           '9789586396924', 'B0009', 'El pueblo entero sabía que iban a matar a Santiago Nasar.', 3, 3),
  ('Fahrenheit 451',                     'Ray Bradbury',                'Debolsillo',   'Ciencia ficción',  '9789875661502', 'B0010', 'Un bombero quema libros en un futuro sin lectura.', 2, 2),
  ('1984',                               'George Orwell',               'Debolsillo',   'Ciencia ficción',  '9789875660750', 'B0011', 'Winston Smith bajo la vigilancia del Gran Hermano.', 4, 4),
  ('El túnel',                           'Ernesto Sabato',              'Seix Barral',  'Novela',           '9789507315564', 'B0012', 'El pintor Juan Pablo Castel y su obsesión.', 2, 2),
  ('Cuentos de la selva',                'Horacio Quiroga',             'Colihue',      'Cuento',           '9789505811639', 'B0013', 'Historias de animales y selva protagonizadas por la fauna misionera.', 6, 5),
  ('Alicia en el país de las maravillas','Lewis Carroll',               'Colihue',      'Fantasía',         '9789506630624', 'B0014', 'Alicia cae por la madriguera del conejo blanco.', 2, 2),
  ('Poeta en Nueva York',                'Federico García Lorca',       'Losada',       'Poesía',           '9789506359297', 'B0015', 'Los poemas urbanos del poeta granadino.', 1, 1);

-- Portadas de prueba (imágenes en public/uploads/portadas): la cuadrada (B0001)
-- verifica que no se recorta la portada (se muestra completa), y el retrato
-- (B0002) mantiene su proporción original.
UPDATE biblioteca_libros SET portada = 'uploads/portadas/B0001.png' WHERE codigo_interno = 'B0001';
UPDATE biblioteca_libros SET portada = 'uploads/portadas/B0002.png' WHERE codigo_interno = 'B0002';

-- ----------------------------------------------------------------------------
--  PRÉSTAMOS (activo, vencido y devuelto) y AVISOS de morosidad
-- ----------------------------------------------------------------------------

INSERT INTO biblioteca_prestamos (tipo_persona, alumno_id, profesor_id, libro_id, fecha_prestamo, fecha_limite, fecha_devolucion, estado) VALUES
  ('alumno',  1, NULL, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0013'), DATE_SUB(CURDATE(), INTERVAL 3 DAY),  DATE_ADD(CURDATE(), INTERVAL 12 DAY), NULL, 'activo'),
  ('alumno',  2, NULL, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0004'), DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY),  NULL, 'vencido'),
  ('alumno',  3, NULL, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0007'), DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'devuelto'),
  ('profesor', NULL, 1, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0001'), DATE_SUB(CURDATE(), INTERVAL 1 DAY),  DATE_ADD(CURDATE(), INTERVAL 14 DAY), NULL, 'activo');

INSERT INTO biblioteca_avisos (prestamo_id, bibliotecario_id, fecha_aviso, medio, notas) VALUES
  (2, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), 'telefono',
   'Se telefoneó al tutor de Mateo López recordando la devolución del libro vencido.');

-- ----------------------------------------------------------------------------
--  PEDIDOS / PETICIONES (pendiente, aceptada y rechazada)
-- ----------------------------------------------------------------------------

INSERT INTO biblioteca_peticiones (tipo_persona, alumno_id, profesor_id, libro_id, estado, motivo, motivo_rechazo, prestamo_id, fecha_peticion) VALUES
  ('alumno',  5,  NULL, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0006'), 'pendiente',
   'Me lo recomendó mi maestra de literatura.', NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
  ('profesor', NULL, 2, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0014'), 'pendiente',
   'Voy a trabajar este libro con el grupo de 5º grado.', NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
  ('alumno',  1,  NULL, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0007'), 'aceptada',
   'Quiero releerlo en las vacaciones.', NULL, 3, DATE_SUB(NOW(), INTERVAL 20 DAY)),
  ('alumno',  10, NULL, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0011'), 'rechazada',
   'Solicito el libro 1984 para un trabajo práctico.',
   'Por el momento no hay ejemplares disponibles para préstamo.', NULL, DATE_SUB(NOW(), INTERVAL 15 DAY));

-- ----------------------------------------------------------------------------
--  FAVORITOS, AMONESTACIONES y AVISOS (paneles con contenido desde el inicio)
-- ----------------------------------------------------------------------------

INSERT INTO biblioteca_favoritos (tipo_persona, alumno_id, profesor_id, libro_id, fecha) VALUES
  ('alumno',  1, NULL, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0001'), DATE_SUB(NOW(), INTERVAL 15 DAY)),
  ('alumno',  1, NULL, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0005'), DATE_SUB(NOW(), INTERVAL 9 DAY)),
  ('alumno',  3, NULL, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0003'), DATE_SUB(NOW(), INTERVAL 6 DAY)),
  ('profesor', NULL, 1, (SELECT id FROM biblioteca_libros WHERE codigo_interno='B0009'), DATE_SUB(NOW(), INTERVAL 4 DAY));

INSERT INTO biblioteca_amonestaciones (alumno_id, tipo, descripcion, fecha, bibliotecario_id) VALUES
  (2,  'mal_estado', 'Devolvió el ejemplar de "Martín Fierro" con varias páginas dañadas y anotaciones.', DATE_SUB(CURDATE(), INTERVAL 6 DAY), 1),
  (4,  'tardanza',   'Devolución con 9 días de retraso sin aviso previo.', DATE_SUB(CURDATE(), INTERVAL 9 DAY), 1);