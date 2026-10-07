<?php
/**
 * Colegio8 — Alta inicial (única) de la primera cuenta de secretaría.
 *
 * Solo está disponible mientras NO exista ninguna cuenta de secretaría
 * y la configuración lo permita (alta_inicial_secretaria = 1). Después
 * de crear la primera cuenta, esta pantalla deja de estar accesible.
 */
require __DIR__ . '/../app/core.php';

$habilitado = (int) (valor_config('alta_inicial_secretaria', '1')) === 1;
$existentes = (int) db()->query('SELECT COUNT(*) FROM secretarias')->fetchColumn();
if ($existentes > 0 || !$habilitado) {
    // Ya existe una cuenta: redirigir al login normal.
    redirect('login.php?modulo=secretaria');
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró o es inválida.';
    } else {
        $nombre = trim((string) ($_POST['nombre_completo'] ?? ''));
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password2'] ?? '');

        if (mb_strlen($nombre) < 5) $errores[] = 'Ingresá el nombre completo.';
        if (mb_strlen($usuario) < 3) $errores[] = 'El usuario debe tener al menos 3 caracteres.';
        if (strlen($pass) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($pass !== $pass2) $errores[] = 'Las contraseñas no coinciden.';

        if (!$errores) {
            $st = db()->prepare('INSERT INTO secretarias (usuario, nombre_completo, password_hash) VALUES (?, ?, ?)');
            $st->execute([$usuario, $nombre, password_hash($pass, PASSWORD_DEFAULT)]);
            flash('exito', 'Cuenta de secretaría creada. Esta pantalla quedó deshabilitada.');
            redirect('login.php?modulo=secretaria');
        }
    }
}

render('publico/primer_uso', [
    'titulo'   => 'Alta inicial · Secretaría',
    'rolPanel' => 'publico',
    'errores'  => $errores,
    'rol'      => 'secretaria',
    'rolNombre' => 'Secretaría de inscripciones',
    'directo'  => url('login.php?modulo=secretaria'),
]);