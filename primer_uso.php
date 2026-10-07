<?php
/** Vista: formulario de alta inicial (primera cuenta de secretaría/bibliotecario). */
$errores = $errores ?? [];
$rol     = $rol ?? '';
$rolNombre = $rolNombre ?? '';
$directo = $directo ?? '';
?>
<section class="tarjeta-login">
  <h1 class="titulo-bienvenida">Alta inicial de <?= e($rolNombre) ?></h1>
  <p style="color:var(--gris-500)">
    Este formulario crea la <strong>primera</strong> cuenta administrativa y queda
    deshabilitado automáticamente. Se recomienda usarlo una sola vez y luego
    protegerlo (ver README).
  </p>

  <?php if ($errores): ?>
    <div class="aviso-error" role="alert">
      <strong>Revisá estos datos:</strong>
      <ul style="margin:.4rem 0 0 1.1rem;padding:0">
        <?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="formulario" novalidate>
    <?= csrf_field() ?>
    <div class="campo">
      <label for="nombre_completo">Nombre completo <span class="requerido">*</span></label>
      <input type="text" id="nombre_completo" name="nombre_completo" required autofocus
             value="<?= e($_POST['nombre_completo'] ?? '') ?>">
    </div>
    <div class="campo">
      <label for="usuario">Usuario <span class="requerido">*</span></label>
      <input type="text" id="usuario" name="usuario" required minlength="3"
             value="<?= e($_POST['usuario'] ?? '') ?>">
    </div>
    <div class="fila-campos">
      <div class="campo">
        <label for="password">Contraseña <span class="requerido">*</span></label>
        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
      </div>
      <div class="campo">
        <label for="password2">Repetir contraseña <span class="requerido">*</span></label>
        <input type="password" id="password2" name="password2" required minlength="8" autocomplete="new-password">
      </div>
    </div>
    <div style="display:flex;justify-content:flex-end;margin-top:-.4rem">
      <button type="button" class="btn btn-fantasma btn-pequeno" data-ver-claves="password,password2">Mostrar contraseñas</button>
    </div>
    <button class="btn btn-primario" type="submit">Crear cuenta</button>
  </form>

  <p style="margin-top:1.2rem;border-top:var(--borde);padding-top:1rem">
    ¿Ya se creó? <a href="<?= e($directo) ?>">Ir al ingreso</a>
  </p>
</section>