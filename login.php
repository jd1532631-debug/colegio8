<?php
/**
 * Colegio8 — Página de ingreso.
 *
 * Módulos:
 *   postulante     → cuenta de familia (tabla usuarios)
 *   lector         → ACCESO ÚNICO de biblioteca: un solo formulario; el
 *                     backend detecta el rol (alumno/tutor o profesor →
 *                     lector; personal de biblioteca → administración).
 *   secretaria     → requiere código institucional (paso 1) + usuario/clave;
 *                     no hay botón público, se entra por URL directa.
 *   bibliotecario  → se redirige al mismo formulario unificado (lector).
 *
 * Las sesiones viven en namespaces separados (app/auth.php), por lo que
 * abrir varias de estas en distintas pestañas no interfiere entre sí.
 */
require __DIR__ . '/../app/core.php';

$modulo   = $_REQUEST['modulo'] ?? '';
$permitido = ['postulante', 'lector', 'secretaria', 'bibliotecario'];
if (!in_array($modulo, $permitido, true)) {
    redirect('index.php');
}

// Auto-ingreso de biblioteca desde la confirmación de registro: si la
// cuenta de postulante ya tiene un alumno asociado, entra directo como
// lector con su persona; si no, entra igual como visitante (catálogo).
if ($modulo === 'lector' && (int) ($_GET['auto'] ?? 0) === 1 && tiene_sesion('postulante')) {
    $pu = sess('postulante');
    $stAlu = db()->prepare('SELECT id, nombre, apellido FROM alumnos WHERE usuario_id = ?');
    $stAlu->execute([(int) $pu['id']]);
    $alu = $stAlu->fetch();
    if ($alu) {
        iniciar_sesion_rol('lector', [
            'id'         => (int) $pu['id'],
            'tipo'       => 'alumno',
            'persona_id' => (int) $alu['id'],
            'usuario'    => $pu['usuario'],
            'nombre'     => $alu['nombre'] . ' ' . $alu['apellido'],
        ]);
        flash('exito', 'Bienvenido a la biblioteca, ' . $alu['nombre'] . '.');
        redirect('biblioteca-lector/inicio.php');
    }
    iniciar_sesion_rol('lector', [
        'id'         => (int) $pu['id'],
        'tipo'       => 'alumno',
        'persona_id' => null,
        'usuario'    => $pu['usuario'],
        'nombre'     => $pu['usuario'],
    ]);
    flash('info', 'Bienvenido/a a la biblioteca. Todavía no tenés una postulación: podés consultar el catálogo; para pedir libros completá tu solicitud de inscripción.');
    redirect('biblioteca-lector/inicio.php');
}

// Acceso único de biblioteca: el backend decide el rol. Un link directo a
// 'bibliotecario' muestra el mismo formulario unificado que el de 'lector'.
if ($modulo === 'bibliotecario') {
    redirect('login.php?modulo=lector');
}

$destinos = [
    'postulante'    => 'postulante/estado.php',
    'lector'        => 'biblioteca-lector/inicio.php',
    'secretaria'    => 'secretaria/inicio.php',
    'bibliotecario' => 'biblioteca-admin/inicio.php',
];
if (tiene_sesion($modulo)) {
    redirect($destinos[$modulo]);
}
// Si ya estás adentro de la biblioteca como administrador, no mostrar
// otra vez el formulario de acceso.
if ($modulo === 'lector' && tiene_sesion('bibliotecario')) {
    redirect($destinos['bibliotecario']);
}

$etiquetas = [
    'postulante'    => 'Postulantes · Seguimiento de inscripción',
    'lector'        => 'Biblioteca',
    'secretaria'    => 'Secretaría · Inscripciones',
    'bibliotecario' => 'Biblioteca',
];
$descripciones = [
    'postulante'    => 'Ingresá con la cuenta que creaste al postular a un alumno.',
    'lector'        => 'Una sola entrada para toda la biblioteca: si tu cuenta es de alumno, tutor o profesor entrás como lector; si es del personal de biblioteca, entrás a la administración. El sistema lo detecta automáticamente.',
    'secretaria'    => 'Primero ingresá el código institucional y después tus credenciales.',
    'bibliotecario' => 'Cuenta de administración del módulo de biblioteca.',
];

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $error = 'La solicitud expiró o es inválida. Volvé a intentar.';
    } elseif ($modulo === 'secretaria' && !codigo_secretaria_ok()) {
        // -------- Paso 1 de 2: código institucional ---------
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $esperado = (string) valor_config('codigo_secretaria', '');
        if ($codigo !== '' && $esperado !== '' && hash_equals($esperado, $codigo)) {
            marcar_codigo_ok();
            redirect('login.php?modulo=secretaria&paso=2');
        }
        $error = 'El código institucional es incorrecto.';
    } else {
        // -------- Credenciales ------------------------------
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $clave   = (string) ($_POST['password'] ?? '');
        $recordar = isset($_POST['recordar']);
        $autenticado = false;

        if ($modulo === 'postulante') {
            $st = db()->prepare('SELECT id, usuario, email, password_hash FROM usuarios WHERE usuario = ? OR email = ?');
            $st->execute([$usuario, $usuario]);
            $u = $st->fetch();
            if ($u && password_verify($clave, $u['password_hash'])) {
                if ($recordar) {
                    recordar_registrar('postulante', (int) $u['id']);
                }
                iniciar_sesion_rol('postulante', [
                    'id'      => (int) $u['id'],
                    'usuario' => $u['usuario'],
                    'email'   => $u['email'],
                    'nombre'  => $u['usuario'],
                ]);
                flash('exito', 'Bienvenido de nuevo, ' . $u['usuario'] . '.');
                redirect($destinos['postulante']);
            }
        } elseif ($modulo === 'lector') {
            // Intento 1: cuenta de familia (usuarios → alumno lector)
            $st = db()->prepare('SELECT id, usuario, email, password_hash FROM usuarios WHERE usuario = ? OR email = ?');
            $st->execute([$usuario, $usuario]);
            $u = $st->fetch();
            if ($u && password_verify($clave, $u['password_hash'])) {
                $stAlu = db()->prepare('SELECT id, nombre, apellido FROM alumnos WHERE usuario_id = ?');
                $stAlu->execute([(int) $u['id']]);
                $alu = $stAlu->fetch();
                if (!$alu) {
                    // Cuenta de familia sin postulación todavía: entra como
                    // visitante y puede consultar el catálogo.
                    if ($recordar) {
                        recordar_registrar('lector', (int) $u['id']);
                    }
                    iniciar_sesion_rol('lector', [
                        'id'         => (int) $u['id'],
                        'tipo'       => 'alumno',
                        'persona_id' => null,
                        'usuario'    => $u['usuario'],
                        'nombre'     => $u['usuario'],
                    ]);
                    flash('info', 'Bienvenido/a a la biblioteca. Todavía no tenés una postulación: podés consultar el catálogo; para pedir libros completá tu solicitud de inscripción.');
                    redirect($destinos['lector']);
                } else {
                    if ($recordar) {
                        recordar_registrar('lector', (int) $u['id']);
                    }
                    iniciar_sesion_rol('lector', [
                        'id'         => (int) $u['id'],
                        'tipo'       => 'alumno',
                        'persona_id' => (int) $alu['id'],
                        'usuario'    => $u['usuario'],
                        'nombre'     => $alu['nombre'] . ' ' . $alu['apellido'],
                    ]);
                    flash('exito', 'Bienvenido a la biblioteca, ' . $alu['nombre'] . '.');
                    redirect($destinos['lector']);
                }
            } else {
                // Intento 2: profesor de biblioteca (lector)
                $stP = db()->prepare('SELECT id, nombre, apellido, usuario, password_hash FROM biblioteca_profesores WHERE usuario = ? AND activo = 1');
                $stP->execute([$usuario]);
                $p = $stP->fetch();
                if ($p && password_verify($clave, $p['password_hash'])) {
                    if ($recordar) {
                        recordar_registrar('lector', (int) $p['id']);
                    }
                    iniciar_sesion_rol('lector', [
                        'id'         => (int) $p['id'],
                        'tipo'       => 'profesor',
                        'persona_id' => (int) $p['id'],
                        'usuario'    => $p['usuario'],
                        'nombre'     => $p['nombre'] . ' ' . $p['apellido'],
                    ]);
                    flash('exito', 'Bienvenido a la biblioteca, ' . $p['nombre'] . '.');
                    redirect($destinos['lector']);
                } else {
                    // Intento 3: personal de biblioteca (administración)
                    $stB = db()->prepare('SELECT id, usuario, nombre_completo, password_hash FROM bibliotecarios WHERE usuario = ?');
                    $stB->execute([$usuario]);
                    $b = $stB->fetch();
                    if ($b && password_verify($clave, $b['password_hash'])) {
                        iniciar_sesion_rol('bibliotecario', [
                            'id'      => (int) $b['id'],
                            'usuario' => $b['usuario'],
                            'nombre'  => $b['nombre_completo'],
                        ]);
                        flash('exito', 'Bienvenida/o a la biblioteca, ' . $b['nombre_completo'] . '.');
                        redirect($destinos['bibliotecario']);
                    }
                }
                if (!$error) {
                    $error = 'Usuario o contraseña incorrectos.';
                }
            }
        } elseif ($modulo === 'secretaria') {
            if (!codigo_secretaria_ok()) {
                redirect('login.php?modulo=secretaria&paso=1');
            }
            $st = db()->prepare('SELECT id, usuario, nombre_completo, password_hash FROM secretarias WHERE usuario = ?');
            $st->execute([$usuario]);
            $s = $st->fetch();
            if ($s && password_verify($clave, $s['password_hash'])) {
                desmarcar_codigo_ok();
                iniciar_sesion_rol('secretaria', [
                    'id'     => (int) $s['id'],
                    'usuario' => $s['usuario'],
                    'nombre' => $s['nombre_completo'],
                ]);
                flash('exito', 'Bienvenida/o, ' . $s['nombre_completo'] . '.');
                redirect($destinos['secretaria']);
            }
            $error = 'Usuario o contraseña incorrectos.';
        } elseif ($modulo === 'bibliotecario') {
            $st = db()->prepare('SELECT id, usuario, nombre_completo, password_hash FROM bibliotecarios WHERE usuario = ?');
            $st->execute([$usuario]);
            $b = $st->fetch();
            if ($b && password_verify($clave, $b['password_hash'])) {
                iniciar_sesion_rol('bibliotecario', [
                    'id'      => (int) $b['id'],
                    'usuario' => $b['usuario'],
                    'nombre'  => $b['nombre_completo'],
                ]);
                flash('exito', 'Bienvenida/o a la biblioteca, ' . $b['nombre_completo'] . '.');
                redirect($destinos['bibliotecario']);
            }
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}

$faltaCodigo = ($modulo === 'secretaria' && !codigo_secretaria_ok());

render('publico/login', [
    'titulo'       => 'Ingreso',
    'rolPanel'     => 'publico',
    'modulo'       => $modulo,
    'etiqueta'     => $etiquetas[$modulo],
    'descripcion'  => $descripciones[$modulo],
    'faltaCodigo'  => $faltaCodigo,
    'error'        => $error,
]);