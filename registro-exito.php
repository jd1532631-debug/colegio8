<?php
/**
 * Colegio8 — Confirmación de cuenta creada (post-registro).
 *
 * Solo se puede ver con una sesión de postulante recién creada (PRG desde
 * registro.php). Muestra el respaldo de acceso y un modal con las
 * próximas acciones (Ir a Biblioteca / Postularme).
 */
require __DIR__ . '/../app/core.php';

if (!tiene_sesion('postulante')) {
    flash('error', 'Necesitás crear una cuenta o ingresar para continuar.');
    redirect('registro.php');
}

$cuenta = $_SESSION['registro_exito'] ?? sess('postulante');
unset($_SESSION['registro_exito']);

render('publico/registro_exito', [
    'titulo'   => 'Cuenta creada',
    'rolPanel' => 'publico',
    'cuenta'   => $cuenta,
    'auto'     => (int) ($_GET['auto'] ?? 0) === 1,
]);