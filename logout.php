<?php
/**
 * Colegio8 — Cierre de sesión por módulo (solo cierra el rol indicado).
 */
require __DIR__ . '/../app/core.php';

$modulo   = $_GET['modulo'] ?? '';
$permitido = ['postulante', 'lector', 'secretaria', 'bibliotecario'];
if (in_array($modulo, $permitido, true)) {
    // Limpiar el borrador de solicitud del postulante de ESTA cuenta: la
    // sesión PHP es compartida entre cuentas, por eso va claveado por id.
    if (in_array($modulo, ['postulante', 'lector'], true)) {
        $ses = sess($modulo);
        if (!empty($ses['id'])) {
            unset($_SESSION['col8_app_' . (int) $ses['id']]);
        }
    }
    // Revocar la sesión persistente ("Recordar mi cuenta").
    recordar_olvidar();
    cerrar_sesion_rol($modulo);
    flash('info', 'Sesión cerrada correctamente.');
}
redirect('index.php');