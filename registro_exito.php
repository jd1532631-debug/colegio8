<?php
/** Vista: confirmación de registro y próximo pasos. */
$cuenta = $cuenta ?? [];
$auto   = (bool) ($auto ?? false);
$usuario = $cuenta['usuario'] ?? sess('postulante')['usuario'] ?? '';
$email   = $cuenta['email'] ?? '';

$qrContenido = url('login.php?modulo=postulante');
?>
<section class="tarjeta-login">
  <h1 class="titulo-bienvenida">¡Cuenta creada!</h1>
  <p style="color:var(--gris-500)">
    El usuario <strong><?= e($usuario) ?></strong> ya puede ingresar al sistema.
    Guardá estos datos: los vas a necesitar para entrar.
  </p>

  <div style="display:grid;gap:.5rem;margin:1rem 0;padding:1rem;border:var(--borde);border-radius:var(--radio)">
    <div><strong style="color:var(--gris-500)">Usuario:</strong> <?= e($usuario) ?></div>
    <?php if ($email): ?><div><strong style="color:var(--gris-500)">Correo:</strong> <?= e($email) ?></div><?php endif; ?>
    <div><strong style="color:var(--gris-500)">Acceso:</strong> <a href="<?= e($qrContenido) ?>"><?= e($qrContenido) ?></a></div>
  </div>

  <div class="aviso-info">
    <strong>Recomendado:</strong> guardá o comenzás ya la postulación para no perder los datos.
  </div>

  <div style="display:flex;gap:.6rem;margin-top:1.2rem;flex-wrap:wrap;justify-content:center">
    <a class="btn btn-primario" href="<?= url('postulante/aplicacion.php') ?>">Postularme al colegio</a>
    <a class="btn btn-fantasma" href="<?= url('login.php?modulo=lector&auto=1') ?>">Ir a biblioteca</a>
  </div>

  <p style="margin-top:1.2rem;border-top:var(--borde);padding-top:1rem;text-align:center">
    <a href="<?= url('login.php') ?>">Ir al ingreso</a>
  </p>
</section>

<div class="modal-auto-contenedor">
  <div id="modalAuto"
       data-titulo="¿Qué querés hacer ahora?"
       data-ancho="460"
       data-boton-cerrar="Por ahora, no"
       data-contenido-html="
        <p style=&quot;text-align:center;color:var(--gris-500);margin:0 0 .4rem&quot;>Tu cuenta <strong><?= e($usuario) ?></strong> quedó creada.</p>
        <p style=&quot;text-align:center;margin:0 0 1rem&quot;>Elegí con qué acción querés continuar.</p>
        <div style=&quot;display:flex;gap:.6rem;flex-wrap:wrap;justify-content:center&quot;>
          <a class=&quot;btn btn-primario&quot; href=&quot;<?= url('postulante/aplicacion.php') ?>&quot;>Postularme</a>
          <a class=&quot;btn btn-fantasma&quot; href=&quot;<?= url('login.php?modulo=lector&auto=1') ?>&quot;>Ir a biblioteca</a>
        </div>">
  </div>
</div>