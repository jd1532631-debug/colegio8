<?php
/**
 * Colegio8 — Página de inicio pública (portada).
 */
require __DIR__ . '/../app/core.php';

// Si ya hay una sesión, ir directo al panel correspondiente.
if (tiene_sesion('postulante')) {
    redirect('postulante/estado.php');
}
if (tiene_sesion('lector')) {
    redirect('biblioteca-lector/inicio.php');
}
if (tiene_sesion('secretaria')) {
    redirect('secretaria/inicio.php');
}
if (tiene_sesion('bibliotecario')) {
    redirect('biblioteca-admin/inicio.php');
}

render('publico/inicio', [
    'titulo'   => 'Inicio',
    'rolPanel' => 'publico',
]);