<?php
/**
 * Colegio8 — Registro de cuenta de postulante (familia/tutor).
 *
 * Solo se crea la cuenta. La solicitud de inscripción (datos del alumno
 * y del tutor + documentación) se completa en postulante/aplicacion.php.
 */
require __DIR__ . '/../app/core.php';

if (tiene_sesion('postulante')) {
    redirect('postulante/estado.php');
}

$erroresPorCampo = [];
$valores = [
    'usuario' => '',
    'email'   => '',
    'password' => '',
    'password2' => '',
];

/** Registra un error para un campo determinado. */
function error_campo(string $campo, string $msg): void
{
    global $erroresPorCampo, $valores;
    $erroresPorCampo[$campo][] = $msg;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        error_campo('usuario', 'La solicitud expiró o es inválida.');
    } else {
        $valores['usuario']   = trim((string) ($_POST['usuario'] ?? ''));
        $valores['email']     = trim((string) ($_POST['email'] ?? ''));
        $valores['password']  = (string) ($_POST['password'] ?? '');
        $valores['password2'] = (string) ($_POST['password2'] ?? '');

        if (mb_strlen($valores['usuario']) < 3) {
            error_campo('usuario', 'Debe tener al menos 3 caracteres.');
        }
        if (preg_match('/[\pC\pZ]/u', $valores['usuario'])) {
            error_campo('usuario', 'No puede contener espacios ni caracteres de control.');
        }
        if (preg_match('/^[a-zA-Z0-9_.\-ñÑáéíóúÁÉÍÓÚ]+$/', $valores['usuario']) !== 1) {
            error_campo('usuario', 'Solo letras, números, puntos, guiones y guiones bajos.');
        }

        if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
            error_campo('email', 'Ingresá un correo electrónico válido.');
        }

        if (strlen($valores['password']) < 8) {
            error_campo('password', 'Debe tener al menos 8 caracteres.');
        }
        if ($valores['password'] !== $valores['password2']) {
            error_campo('password2', 'Las contraseñas no coinciden.');
        }

        $hayErrores = (bool) $erroresPorCampo;

        if (!$hayErrores) {
            $st = db()->prepare('SELECT id FROM usuarios WHERE usuario = ? OR email = ?');
            $st->execute([$valores['usuario'], $valores['email']]);
            if ($st->fetch()) {
                error_campo('usuario', 'Ya existe una cuenta con ese usuario o correo.');
                error_campo('email', 'Ya existe una cuenta con ese usuario o correo.');
            }
            $hayErrores = (bool) $erroresPorCampo;
        }

        if (!$hayErrores) {
            $st = db()->prepare('INSERT INTO usuarios (usuario, email, password_hash) VALUES (?, ?, ?)');
            $st->execute([$valores['usuario'], $valores['email'], password_hash($valores['password'], PASSWORD_DEFAULT)]);
            $idUsuario = (int) db()->lastInsertId();

            iniciar_sesion_rol('postulante', [
                'id'      => $idUsuario,
                'usuario' => $valores['usuario'],
                'email'   => $valores['email'],
                'nombre'  => $valores['usuario'],
            ]);

            // PRG: vamos a una página intermedia que muestra el QR/respaldo
            // de acceso y un modal con "Ir a Biblioteca" / "Postularme".
            $_SESSION['registro_exito'] = [
                'usuario' => $valores['usuario'],
                'email'   => $valores['email'],
            ];
            redirect('registro-exito.php');
        }
    }
}

render('publico/registro', [
    'titulo'    => 'Crear cuenta',
    'rolPanel'  => 'publico',
    'errores'   => $erroresPorCampo,
    'hayErrores' => (bool) $erroresPorCampo,
    'valores'   => $valores,
]);