# Colegio8

Sistema web para una institución educativa con **dos módulos independientes**:

1. **Inscripciones** - postulantes y tutor responsable presentan la solicitud online con documentos; la secretaría la revisa (aprobar/rechazar con motivo), administra cupos/cursos, asigna divisiones y mantiene lista de espera. Reportes CSV e impresión.
2. **Biblioteca** - catálogo de libros, préstamos, pedidos, favoritos, recomendaciones, usuarios (alumnos, profesores, bibliotecarios), amonestaciones y alertas de morosidad. El **lector** ve el resultado de su cuenta: catálogo de consulta, pedir, favoritos, historial.

Tecnología: **PHP 8+ / MySQL o MariaDB**, sesiones separadas por rol, contraseñas con `bcrypt`, consultas preparadas y vistas en PHP plano. Diseño minimalista **verde oscuro + blanco** (con blanco levemente verdoso en los paneles de trabajo), responsivo y accesible, con **modo oscuro/claro** persistente y animaciones sobrias que respetan `prefers-reduced-motion`.

### Acceso rápido

| Rol | URL de acceso |
|---|---|
| Portada pública | http://localhost/colegio8/public/ |
| Postulante / tutor | http://localhost/colegio8/public/login.php?modulo=postulante |
| Biblioteca (lector / bibliotecario) | http://localhost/colegio8/public/login.php?modulo=lector |
| **Secretaría** | **http://localhost/colegio8/public/login.php?modulo=secretaria** |

### Acceso único a biblioteca

Todas las pantallas públicas tienen **un solo botón "Biblioteca"** que lleva a un **único formulario** de usuario y contraseña (`login.php?modulo=lector`). El backend detecta el rol después de validar: una cuenta de alumno/tutor o profesor entra al **panel de lector**, y una cuenta del personal de biblioteca entra al **panel de administración**. Nadie elige de antemano "soy lector" o "soy bibliotecario".

### Secretaría por URL directa (discreción)

En las pantallas públicas **no hay ningún botón ni enlace visible** hacia el login de secretaría. La pantalla sigue existiendo y se alcanza escribiendo directamente `login.php?modulo=secretaria` (o `http://localhost/colegio8/public/login.php?modulo=secretaria`), y conserva el **código institucional + usuario/contraseña** intactos.

### Portada pública

La portada (`/public/`) muestra el cartel de bienvenida, un texto institucional breve y **exactamente tres accesos grandes**: **Postularme**, **Ir a biblioteca** y **Crear una cuenta**. No muestra estadísticas ni contadores operativos (esos viven en los paneles de secretaría y biblioteca).

**Pie de página público:** todas las pantallas públicas (portada, login, registro, preguntas frecuentes) muestran en el pie el **teléfono**, **correo electrónico**, enlace a **Preguntas frecuentes** y las **redes sociales** (Facebook, Instagram, YouTube) que estén configuradas en la tabla `configuracion` (`telefono_contacto`, `email_contacto`, `facebook_url`, `instagram_url`, `youtube_url`). El copyright se muestra siempre.

### Marca: sitio oficial y reemplazo del logo

- **Logo clickeable:** en todas las pantallas (portada, formularios y encabezados
  de los paneles de secretaría, biblioteca y lector), el escudo/logo abre el
  **sitio oficial** del colegio en una pestaña nueva, sin cerrar la sesión activa.
  La URL se lee de la tabla `configuracion` (clave `sitio_oficial`) y se define
  en **un único lugar**. El valor por defecto del sistema es:
  `https://www.escuelasargentinas.com/colegio-secundario-provincial-n08-la-rioja-460080900`
  (se puede cambiar en esa misma clave).
- **Logo por defecto:** el escudo institucional (imagen circular verde del
  "Colegio Provincial Nº 8", con fondo transparente) es el valor por defecto del
  sistema y se muestra en todos los tamaños **sin deformarse** (se escala con
  `object-fit: contain`, nunca se estira ni se recorta en un marco cuadrado).
- **Cambiar el logo sin tocar código:** el personal puede reemplazar la imagen
  copiando su archivo como `public/assets/img/logo.png` (cualquier formato de
  imagen). Si existe, se usa en todas las pantallas automáticamente; si no, se
  muestra un escudo genérico `assets/img/escudo.svg`. El favicon del navegador
  también usa esta misma imagen.

### Animaciones y comodidad de uso

- **Números que cuentan:** los totales de los paneles (`stats-num`, por ejemplo solicitudes pendientes, préstamos activos, títulos en catálogo) se animan contando desde 0 hasta su valor real al cargar la pantalla.
- **Botones con vida propia:** los botones destacados (los tres de la portada, los botones de acción de los paneles y los de los formularios) tienen
  - un **brillo resplandecente que los cruza sutilmente** de vez en cuando y una **leve pulsación de sombra** en reposo;
  - un **hover expresivo**: se elevan un poco (sombra que crece), cambian de tono dentro de la paleta verde y se les enciende un **resplandor crítico**;
  - un **click con respuesta inmediata**: compresión leve + destello rápido, que no se "apila" aunque se hagan clics rápidos seguidos.
  Todo usa solo `transform`/`box-shadow`/`filter` (nunca empujan ni mueven otros elementos de la pantalla) y los mismos tiempos y curva de animación del resto del sistema.
- **Brillo sutil:** un resplandor breve al cargar la portada y un brillo discreto que recorre los botones principales y los accesos grandes al pasar el mouse.
- **Entrada de menús:** barra superior, panel lateral y cabecera aparecen con un suave desvanecido + desplazamiento.
- Todo se desactiva si el sistema operativo pide **movimiento reducido**.
- Los menús tienen áreas de clic/toque generosas, buen espaciado y estados de hover/foco visibles.

---

## Estructura de carpetas

```
Colegio8/
├─ app/                 Núcleo PHP (no debe servirse al público)
│  ├─ config.php        (tu archivo de configuración; NO se sube)
│  ├─ db.php, auth.php, helpers.php, uploads.php
│  ├─ inscripciones.php, biblioteca.php
│  └─ vistas/           vista por cada página + layout.php
├─ public/              Raíz web (apuntá el servidor acá)
│  ├─ index.php, login.php, registro.php, logout.php
│  ├─ postulante/       módulo postulante
│  ├─ secretaria/       módulo secretaría (inscripciones)
│  ├─ biblioteca-admin/ módulo bibliotecario
│  ├─ biblioteca-lector/ módulo lector
│  ├─ assets/           CSS, JS, imagen del escudo
│  └─ uploads/          documentos y portadas (bloqueado por .htaccess)
├─ database/
│  ├─ esquema.sql           esquema de producción (sin datos)
│  ├─ datos_prueba_dev.sql  datos SOLO para desarrollo (ver abajo)
│  └─ sembrar_datos_dev.php importador de datos_prueba_dev.sql
└─ .gitignore
```

## Modo oscuro / claro

Todas las pantallas (públicas, postulante, secretaría, biblioteca y lector)
tienen un **único botón** de alternancia (sol/luna) en la barra superior o en
la cabecera pública. La preferencia:

- Se guarda en `localStorage` y persiste entre sesiones y recargas.
- La primera vez (si el usuario no eligió nada) respeta el tema del
  sistema operativo (`prefers-color-scheme`).
- Se aplica de forma consistente a todos los paneles con una paleta oscura
  real (fondos casi negros, texto claro, acentos verdes más claros), con
  transiciones suaves y desactivadas si el sistema pide `prefers-reduced-motion`.
- Está operado por teclado y con foco visible.

## Instalación (XAMPP)

1. Copiá el proyecto a `C:\xampp\htdocs\Colegio8`.
2. Arrancá **Apache** y **MySQL** desde el panel de XAMPP.
3. Creá la base de datos importando el esquema:
   ```bash
   mysql -u root < database\esquema.sql
   ```
   (si tenés contraseña de root, agregá `-p`)
4. Copiá `app/config.example.php` como `app/config.php` y ajustá
   `DB_HOST`, `DB_USER` y `DB_PASS` si usás otra cuenta. Dejá `BASE_URL`
   vacío para que la app se detecte sola; cargá la URL como `localhost/Colegio8/public`.
5. (Opcional, desarrollo) Importá los datos de prueba (ver sección siguiente).
6. Entrá a `http://localhost/Colegio8/public/`.

Sugerencia para Apache: apuntar el virtual host directamente a `public/`
para que `app/` y `database/` queden fuera de la raíz servida de un todo.

### Primer uso (alta de primeras cuentas)

Con la base **vacía** se puede entrar por URL directa a las pantallas
**"Alta inicial"**:

- `primer-uso/secretaria.php` — crea la primera cuenta de secretaría.
- `primer-uso/bibliotecario.php` — crea la primera cuenta de bibliotecario.

La pantalla solo funciona mientras no exista ninguna cuenta del rol. Una vez
creada la primera, se bloquea por sí sola. Si importaste los datos de prueba,
ya hay cuentas y el alta inicial queda inactiva.

### Código institucional (secretaría)

El login de secretaría pide primero el **código institucional** (por defecto
`COLEGIO8-2026`) y luego usuario/contraseña. El código se guarda en la tabla
`configuracion` (clave `codigo_secretaria`); en producción conviene cambiarlo.

---

## Datos de prueba — SOLO DESARROLLO ⚠️

`database/datos_prueba_dev.sql` deja la base **lista para probar de punta a
punta los cuatro roles y los dos módulos** sin cargar nada a mano:

```bash
mysql -u root < database\esquema.sql
mysql -u root < database\datos_prueba_dev.sql
```

Importá el **esquema primero y los datos después** (nunca al revés, nunca en
reemplazo del esquema). Es seguro correrlo **una sola vez**; si querés
repetirlo, recreá la base desde cero:

```bash
mysql -u root -e "DROP DATABASE IF EXISTS colegio8;"
```

También podés cargarlo con el importador que usa la misma fuente única
(requiere PHP CLI): `php database\sembrar_datos_dev.php`. El script no
vuelve a cargar si ya existen cuentas.

> **ADVERTENCIA:** este archivo deja contraseñas conocidas y datos falsos.
> Es SOLO para entornos de desarrollo/pruebas y **nunca** debe importarse en
> la base real de una institución. Las contraseñas se guardan hasheadas con
> bcrypt (igual que en producción); el texto plano queda documentado en los
> comentarios del archivo y en esta tabla únicamente para poder entrar.

### Cuentas de prueba

| Rol | Usuario | Contraseña | Contenido listo para ver |
|---|---|---|---|
| Secretaría | `secretaria` | `Secretaria2026` | bandeja con solicitudes en 4 estados, desocupes de cursos (1ºA y 4ºA llenos, vacantes parciales y vacías), historial, reportes |
| Bibliotecario | `bibliotecario` | `Biblioteca2026` | 15 libros, pedidos pendientes/aceptados/rechazados, préstamos activos/vencidos/devueltos, morosidad con aviso, amonestaciones, reportes |
| Postulante (tutor) | `garcia` | `Sofia2026` | solicitud **aprobada** y asignada a 1º B (madre: Claudia Rossi), división visible, sin tener que completar el formulario |
| Lector (alumno) | `garcia` | `Sofia2026` | misma cuenta entra a la biblioteca: préstamo activo, pedido aceptado, favoritos y recomendaciones |
| Lector (profesor) | `carlos_gomez` | `ProfeCarlos2026` | préstamo activo, favorito, historial |
| Profesor (biblio) | `ana_perez` | `ProfeAna2026` | un pedido pendiente enviado a la biblioteca |

Otras familias de ejemplo (todas con tutor y solicitud cargada):

| Usuario | Contraseña | Alumno | Estado de su solicitud |
|---|---|---|---|
| `lopez` | `Mateo2026` | Mateo López (tutor: Jorge López) | recibida (y préstamo vencido + amonestación en biblioteca) |
| `perez` | `Camila2026` | Camila Pérez | en revisión |
| `fernandez` | `Maria2026` | María Fernández | rechazada (con motivo) |
| `gutierrez` | `Lucas2026` | Lucas Gutiérrez | aprobada → 2º A |
| `rodriguez` | `Emma2026` | Emma Rodríguez | aprobada → 4º B |
| `diaz` | `Valentino2026` | Valentino Díaz | aprobada → 6º A |
| `acosta` | `Juana2026` | Juana Acosta | recibida |
| `romero` | `Thiago2026` | Thiago Romero | en revisión |
| `sosa` | `Mia2026` | Mía Sosa | rechazada (con motivo) |
| `huertas` | `Bruno2026` | Bruno Huertas | recibida + lista de espera (1º) — 1º A |
| `cabrera` | `Isabella2026` | Isabella Cabrera | recibida + lista de espera (2º) — 1º A |
| `silva` | `Franco2026` | Franco Silva | recibida + lista de espera (3º) — 1º A |

Qué incluye además el archivo de datos:

- **Inscripciones:** 12 cursos con cupos realistas (1º A y 4º A llenos; 1º C,
  3º A y 6º A vacíos; el resto con ocupación parcial), 13 solicitudes en los
  cuatro estados, historial de pasos y lista de espera en el curso lleno.
- **Biblioteca:** 15 libros de distintos géneros y cantidades de ejemplares,
  4 préstamos (2 activos, 1 vencido con su aviso de morosidad, 1 devuelto),
  4 pedidos (2 pendientes, 1 aceptado ligado a su préstamo, 1 rechazado con
  motivo), 4 favoritos de dos géneros (para alimentar recomendaciones) y
  2 amonestaciones.

---

## Roles y accesos

| Rol | Entrada | Qué puede hacer |
|---|---|---|
| Postulante / tutor | `login.php?modulo=postulante` | presentar solicitud en 3 pasos (datos, documentos, confirmación), corregir tras rechazo y reenviar, ver estado y división/espera |
| Lector (alumno o profesor) | `login.php?modulo=lector` (formulario único de biblioteca) | el sistema lo detecta solo: catálogo de consulta, pedir, favoritos y recomendaciones, historial y pedidos |
| Bibliotecario | `login.php?modulo=lector` (misma pantalla única) | el sistema lo detecta y lo lleva a la administración: libros, préstamos, pedidos, usuarios, amonestaciones, morosidad con avisos, reportes CSV |
| Secretaría | `login.php?modulo=secretaria` por **URL directa** (no hay botón público; código + credenciales) | bandeja, revisión con visualización de documentos, aprobar/rechazar, cupos y cursos, asignación con lista de espera automática, historial, reportes CSV e impresión |

Las sesiones están separadas por rol (un solo `$_SESSION` con espacios por rol):
podés estar logueado como postulante y como bibliotecario en distintas pestañas
sin pisarse.

---

## Inscripciones: documentos y movimientos de curso

- **4 fotos del DNI por separado.** El formulario del postulante pide
  obligatoriamente **DNI del alumno (frente y dorso)** y **DNI del tutor
  (frente y dorso)**, cada uno con su etiqueta y miniatura de vista previa
  (pasos 1 y 2 del wizard). El tipo se guarda en `documentos.tipo` como
  `dni_alumno_frente`, `dni_alumno_dorso`, `dni_tutor_frente`,
  `dni_tutor_dorso` (o `otro`).
- **Corrección con reemplazo por foto.** Cuando una solicitud fue rechazada, el
  postulante puede **reemplazar solo una de las fotos** sin volver a cargar las
  demás: el archivo anterior pasa a estado `reemplazado` y el nuevo se asocia a
  la solicitud. No se puede enviar si falta alguna de las 4.
- **Documentos protegidos por permisos.** `documento.php` solo sirve un
  documento si el solicitante es la secretaría **o el postulante dueño de la
  solicitud**; los documentos de un borrador requieren que la sesión sea la que
  creó ese borrador. Cualquier otro acceso responde `403`.
- **Vista previa amplia en secretaría.** En la revisión (y en el estado del
  postulante) las fotos se muestran **etiquetadas y en pantalla grande** dentro
  de un modal ampliado: si el documento tiene varias fotos se navega entre ellas
  con **‹ Anterior / n / N / Siguiente ›**. Las imágenes se centran y escalan
  sin deformarse (`object-fit: contain`).
- **Mover alumnos entre cursos.** Desde el detalle de un curso, secretaría puede
  **mover a un alumno** a otro curso viendo cupos disponibles. Un curso lleno
  **no acepta el movimiento** salvo que se marque la excepción explícita, en
  cuyo caso el alumno entra a la **lista de espera** del destino. Cada
  movimiento queda registrado en `historial_solicitudes` (`movido_de_curso`,
  con origen y destino) y el curso muestra los **últimos movimientos**.

---

## Decisiones de seguridad

- Contraseñas solo con `password_hash()` (bcrypt). Nunca se guardan en texto plano.
- Consultas con **PDO preparado** en todo el sistema.
- **CSRF** en todos los formularios POST (token por sesión).
- Subidas: **solo JPG/PNG/PDF**, tamaño máximo configurable, nombre único,
  guardadas fuera de la raíz servida (siempre que se sirva el virtual host) y
  servidas por `documento.php` / `libro-portada.php` que validan permisos.
- La carpeta `public/uploads` tiene `.htaccess` que deniega el acceso directo.
- `config.php` (con credenciales) está excluido en `.gitignore`.

## Notas

- `public/.htaccess` solo desactiva el listado de directorios; el proyecto
  funciona con Apache y también con `php -S`.
- Los datos de prueba van con fechas relativas al día de importación, así la
  pantalla de morosidad siempre muestra el préstamo vencido y el catálogo se
  ve actualizado sin importar cuándo se importe.