<?php
/**
 * Colegio8 — Layout base.
 *
 * Dos modos: panel (sidebar + topbar, según el rol) y centrado (login,
 * registro, primer uso). Las animaciones respetan prefers-reduced-motion.
 */

declare(strict_types=1);

$esPanel   = in_array($rolPanel, ['postulante', 'lector', 'secretaria', 'bibliotecario'], true);
$usuario   = '';
if ($esPanel) {
    $ses = sess($rolPanel);
    $usuario = e($ses['nombre'] ?? $ses['usuario'] ?? '');
}

$itemActivo = $_GET['vista'] ?? basename($_SERVER['SCRIPT_NAME']);

$sitioOficial = sitio_oficial_url();
$logoUrl      = url_logo();
$telC  = trim((string) (valor_config('telefono_contacto') ?: ''));
$emlC  = trim((string) (valor_config('email_contacto') ?: ''));
$redes = [
    'facebook_url'  => ['Facebook', 'M13.5 9H15V6h-1.5C11.6 6 10 7.6 10 10.5V12H8v3h2v6h3v-6h2.5l.5-3h-3V10.5c0-.8.65-1.5 1.5-1.5z'],
    'instagram_url' => ['Instagram', 'M7.8 3h8.4A4.8 4.8 0 0 1 21 7.8v8.4a4.8 4.8 0 0 1-4.8 4.8H7.8A4.8 4.8 0 0 1 3 16.2V7.8A4.8 4.8 0 0 1 7.8 3zm0 2A2.8 2.8 0 0 0 5 7.8v8.4A2.8 2.8 0 0 0 7.8 19h8.4a2.8 2.8 0 0 0 2.8-2.8V7.8A2.8 2.8 0 0 0 16.2 5H7.8zm4.2 2.75a4.25 4.25 0 1 1 0 8.5 4.25 4.25 0 0 1 0-8.5zm0 2a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5zm4.5-2.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2z'],
    'youtube_url'   => ['YouTube', 'M21.6 7.2a2.8 2.8 0 0 0-2-2C17.9 4.8 12 4.8 12 4.8s-5.9 0-7.6.4a2.8 2.8 0 0 0-2 2C2 9 2 12 2 12s0 3 .4 4.8a2.8 2.8 0 0 0 2 2c1.7.4 7.6.4 7.6.4s5.9 0 7.6-.4a2.8 2.8 0 0 0 2-2c.4-1.8.4-4.8.4-4.8s0-3-.4-4.8zM10 15V9l5 3-5 3z'],
];
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Colegio8 — Sistema de inscripciones y biblioteca escolar">
<title><?= e($titulo) ?> · Colegio8</title>
<link rel="icon" href="<?= url('assets/img/logo.png') ?>" type="image/png">
<script>
  /* Modo oscuro/claro: se aplica ANTES del primer render para evitar parpadeos.
     Prioridad: preferencia guardada en localStorage > preferencia del sistema. */
  (function () {
    var t;
    try {
      t = localStorage.getItem('col8_tema');
      if (t !== 'claro' && t !== 'oscuro') {
        t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
          ? 'oscuro' : 'claro';
      }
    } catch (e) {
      t = 'claro';
    }
    document.documentElement.setAttribute('data-tema', t);
  })();
</script>
<?php
/** Versión de caché de assets: cambia con el mtime del archivo (evita JS/CSS viejos). */
$assetVer = static function (string $ruta): string {
    $f = filemtime(__DIR__ . '/../../public/' . $ruta);
    return $f ? (string) $f : '1';
};
?><link rel="stylesheet" href="<?= url('assets/css/estilos.css?v=' . $assetVer('assets/css/estilos.css')) ?>">
<?php if (!empty($vistaImpresion)): ?>
<link rel="stylesheet" href="<?= url('assets/css/impresion.css?v=' . $assetVer('assets/css/impresion.css')) ?>" media="print">
<?php endif; ?>
</head>
<body class="panel-<?= e($rolPanel) ?>">

<a class="salto-contenido" href="#contenido">Saltar al contenido</a>

<?php if (!$esPanel): ?>

  <!-- ======================= LAYOUT CENTRADO (público) ======================= -->
  <div class="caja-publico">
    <header class="cabecera-publico">
      <div class="marca">
        <?php $logoExternoPub = $sitioOficial && $itemActivo === 'index.php'; ?>
        <?php if ($logoExternoPub): ?>
          <a class="escudo-link" href="<?= e($sitioOficial) ?>" target="_blank" rel="noopener" aria-label="Sitio oficial de Colegio8">
            <img class="escudo" src="<?= e($logoUrl) ?>" alt="" width="40" height="40">
          </a>
        <?php else: ?>
          <a class="escudo-link" href="<?= url('index.php') ?>" aria-label="Colegio8, inicio">
            <img class="escudo" src="<?= e($logoUrl) ?>" alt="" width="40" height="40">
          </a>
        <?php endif; ?>
        <a class="marca-link" href="<?= url('index.php') ?>" aria-label="Colegio8, inicio">
          <span class="marca-nombre">Colegio8</span>
        </a>
      </div>
      <div class="controles-derecha">
        <button type="button" class="btn-icono btn-tema" id="btnTema" aria-pressed="false" aria-label="Cambiar a modo oscuro">
          <svg class="ico-sol" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="ico-luna" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <nav class="nav-publico" aria-label="Accesos">
          <?php if ($itemActivo !== 'index.php'): ?>
          <a class="enlace-biblioteca" href="<?= url('login.php?modulo=lector') ?>">Biblioteca</a>
          <?php endif; ?>
        </nav>
      </div>
    </header>
    <main id="contenido" class="caja-publico-cuerpo">
      <?php require $contenido; ?>
    </main>
    <footer class="pie-publico">
      <div style="display:flex;flex-wrap:wrap;gap:.8rem 1.6rem;justify-content:center;align-items:center">
        <?php if ($telC): ?><span><strong>Teléfono:</strong> <?= e($telC) ?></span><?php endif; ?>
        <?php if ($emlC): ?><span><strong>Correo:</strong> <a href="mailto:<?= e($emlC) ?>"><?= e($emlC) ?></a></span><?php endif; ?>
        <a href="<?= url('frecuentes.php') ?>">Preguntas frecuentes</a>
        <?php foreach ($redes as $clave => [$etq, $path]): ?>
          <?php $u = trim((string) (valor_config($clave) ?: '')); ?>
          <?php if ($u): ?>
            <a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="<?= e($etq) ?> del Colegio8">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="<?= e($path) ?>"/></svg>
              <?= e($etq) ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <p style="margin:.7rem 0 0">© <?= date('Y') ?> Colegio8 — Sistema de inscripciones y biblioteca escolar.</p>
    </footer>
  </div>

<?php else: ?>

  <!-- ======================= LAYOUT PANEL (por rol) ======================= -->
  <?php
    $menus = [
        'postulante' => [
            ['titulo' => 'Mi solicitud', 'items' => [
                ['estado',      'Estado',          'estado.php'],
                ['solicitud',   'Presentar / corregir', 'aplicacion.php'],
            ]],
        ],
        'lector' => [
            ['titulo' => 'Biblioteca', 'items' => [
                ['biblioteca',   'Inicio',          'inicio.php'],
                ['catalogo',     'Catálogo',        'catalogo.php'],
                ['pedidos',      'Mis pedidos',     'pedidos.php'],
                ['historial',    'Mi historial',    'historial.php'],
                ['favoritos',    'Favoritos',       'favoritos.php'],
            ]],
        ],
        'secretaria' => [
            ['titulo' => 'Inscripciones', 'items' => [
                ['inicio',       'Inicio',          'inicio.php'],
                ['bandeja',      'Bandeja de entrada', 'bandeja.php'],
                ['asignar',      'Asignación de cursos', 'asignar.php'],
                ['vacantes',     'Cupos y cursos',  'vacantes.php'],
                ['historial',    'Historial',       'historial.php'],
                ['reportes',     'Reportes',        'reportes.php'],
            ]],
        ],
        'bibliotecario' => [
            ['titulo' => 'Biblioteca', 'items' => [
                ['inicio',       'Inicio',          'inicio.php'],
                ['libros',       'Libros',          'libros.php'],
                ['prestamos',    'Préstamos',       'prestamos.php'],
                ['pedidos',      'Pedidos',         'pedidos.php'],
                ['usuarios',     'Usuarios',        'usuarios.php'],
                ['amonestaciones','Amonestaciones', 'amonestaciones.php'],
                ['morosidad',    'Tardanza',       'morosidad.php'],
                ['reportes',     'Reportes',        'reportes.php'],
            ]],
        ],
    ];
    $menu = $menus[$rolPanel] ?? [];
    $dirBase = [
        'postulante'    => 'postulante',
        'lector'        => 'biblioteca-lector',
        'secretaria'    => 'secretaria',
        'bibliotecario' => 'biblioteca-admin',
    ][$rolPanel];
    // Pantalla "inicio" de cada panel: el postulante no tiene inicio.php
    // (sus pantallas son estado.php y aplicacion.php).
    $inicioArchivo = [
        'postulante'    => 'estado.php',
        'lector'        => 'inicio.php',
        'secretaria'    => 'inicio.php',
        'bibliotecario' => 'inicio.php',
    ][$rolPanel];
    $activo = $_GET['vista'] ?? basename($_SERVER['SCRIPT_NAME']);
  ?>
  <div class="panel">
    <aside class="barra-lateral" id="barraLateral" aria-label="Menú principal">
      <div class="marca marca-panel">
        <?php $logoExternoPanel = $sitioOficial && $activo === $inicioArchivo; ?>
        <?php if ($logoExternoPanel): ?>
          <a class="escudo-link" href="<?= e($sitioOficial) ?>" target="_blank" rel="noopener" aria-label="Sitio oficial de Colegio8">
            <img class="escudo" src="<?= e($logoUrl) ?>" alt="" width="36" height="36">
          </a>
        <?php else: ?>
          <a class="escudo-link" href="<?= url($dirBase . '/' . $inicioArchivo) ?>" aria-label="Ir al inicio">
            <img class="escudo" src="<?= e($logoUrl) ?>" alt="" width="36" height="36">
          </a>
        <?php endif; ?>
        <a class="marca-link" href="<?= url($dirBase . '/' . $inicioArchivo) ?>" aria-label="Ir al inicio">
          <span class="marca-nombre">Colegio8</span>
        </a>
      </div>
      <button class="btn-cerrar-barra" id="cerrarBarra" aria-label="Cerrar menú">✕</button>
      <nav class="nav-panel">
        <?php foreach ($menu as $grupo): ?>
          <p class="nav-grupo"><?= e($grupo['titulo']) ?></p>
          <ul class="nav-lista">
            <?php foreach ($grupo['items'] as [$id, $etq, $archivo]): ?>
              <?php $esta = ($archivo === $activo); ?>
              <li><a class="nav-item<?= $esta ? ' esta' : '' ?>" href="<?= url($dirBase . '/' . $archivo) ?>"<?= $esta ? ' aria-current="page"' : '' ?>>
                <span class="nav-punto"></span><?= e($etq) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endforeach; ?>
      </nav>
      <div class="barra-footer">
        <a class="nav-item" href="<?= url('logout.php?modulo=' . $rolPanel) ?>">Cerrar sesión</a>
      </div>
    </aside>

    <div class="panel-principal">
      <header class="barra-superior">
        <button class="btn-icono btn-menu-hamb" id="abrirBarra" aria-label="Abrir menú" aria-expanded="false" aria-controls="barraLateral">
          <span></span><span></span><span></span>
        </button>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <button type="button" class="btn-icono btn-tema" id="btnTema" aria-pressed="false" aria-label="Cambiar a modo oscuro">
          <svg class="ico-sol" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="ico-luna" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <div class="perfil">
          <span class="perfil-nombre"><?= $usuario ?></span>
          <span class="perfil-rol"><?= e(ucfirst($rolPanel)) ?></span>
        </div>
      </header>
      <main id="contenido" class="contenido">
        <?php require $contenido; ?>
      </main>
      <?php if ($activo === 'inicio.php'): ?>
        <footer class="pie-panel">
          <div style="display:flex;flex-wrap:wrap;gap:.8rem 1.6rem;justify-content:space-between;align-items:center">
            <div style="display:flex;gap:.8rem 1.6rem;flex-wrap:wrap;align-items:center">
              <?php if ($telC): ?><span><strong>Teléfono:</strong> <?= e($telC) ?></span><?php endif; ?>
              <?php if ($emlC): ?><span><strong>Correo:</strong> <a href="mailto:<?= e($emlC) ?>"><?= e($emlC) ?></a></span><?php endif; ?>
              <a href="<?= url('frecuentes.php') ?>">Preguntas frecuentes</a>
            </div>
            <div class="pie-panel-redes">
              <?php foreach ($redes as $clave => [$etq, $path]): ?>
                <?php $u = trim((string) (valor_config($clave) ?: '')); ?>
                <?php if ($u): ?>
                  <a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="<?= e($etq) ?> del Colegio8">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="<?= e($path) ?>"/></svg>
                    <?= e($etq) ?>
                  </a>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
          <p style="margin:.7rem 0 0">© <?= date('Y') ?> Colegio8 — Inscripciones y biblioteca escolar.</p>
        </footer>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>

<!-- Toasts (mensajes flash) -->
<div class="toast-region" id="toastRegion" aria-live="polite" aria-atomic="true"></div>

<!-- Modal genérico -->
<div class="modal-capa" id="modalCapa" hidden>
  <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
    <div class="modal-cabecera">
      <h2 id="modalTitulo"></h2>
      <button class="btn-icono" data-cerrar-modal aria-label="Cerrar">✕</button>
    </div>
    <div class="modal-cuerpo" id="modalCuerpo"></div>
  </div>
</div>

<script>
  window.COL8 = {
    base: <?= json_encode(url('')) ?>,
    csrf: <?= json_encode(csrf_token()) ?>,
    flashes: <?= json_encode(tomar_flash()) ?>
  };
</script>
<script src="<?= url('assets/js/app.js?v=' . $assetVer('assets/js/app.js')) ?>"></script>
</body>
</html>