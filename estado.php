<?php
/**
 * Colegio8 — Estado de la solicitud (vista del postulante).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('postulante');

$uid = (int) sess('postulante')['id'];

// Migrar un eventual borrador guardado con la clave antigua (global).
if (isset($_SESSION['col8_app']) && !isset($_SESSION['col8_app_' . $uid])) {
    $_SESSION['col8_app_' . $uid] = $_SESSION['col8_app'];
    unset($_SESSION['col8_app']);
}

$stA = db()->prepare('SELECT * FROM alumnos WHERE usuario_id = ?');
$stA->execute([$uid]);
$alu = $stA->fetch();

$sol = null;
$tut = null;
$vacante = null;
$espera = null;
if ($alu) {
    $stS = db()->prepare('SELECT * FROM solicitudes WHERE alumno_id = ? ORDER BY id DESC LIMIT 1');
    $stS->execute([(int) $alu['id']]);
    $sol = $stS->fetch();

    $stT = db()->prepare('SELECT * FROM tutores WHERE alumno_id = ?');
    $stT->execute([(int) $alu['id']]);
    $tut = $stT->fetch();

    if ($alu['vacante_id']) {
        $stV = db()->prepare('SELECT v.* FROM vacantes v WHERE v.id = ?');
        $stV->execute([(int) $alu['vacante_id']]);
        $vacante = $stV->fetch();
        if ($vacante) {
            $vacante['cupo_ocupado'] = cupo_ocupado_vacante((int) $vacante['id']);
        }
    } elseif ($alu) {
        $stE = db()->prepare(
            'SELECT le.*, v.anio, v.turno, v.division, v.cupo_total,
                    (SELECT COUNT(*) FROM alumnos a WHERE a.vacante_id = v.id) AS cupo_ocupado
             FROM lista_espera le JOIN vacantes v ON v.id = le.vacante_id
             WHERE le.alumno_id = ?'
        );
        $stE->execute([(int) $alu['id']]);
        $espera = $stE->fetch();
    }
}

// Permitir retomar un borrador guardado (clave por usuario)
$hayBorrador = !empty($_SESSION['col8_app_' . $uid]);

$docs = [];
if ($sol) {
    $stD = db()->prepare('SELECT id, tipo, nombre_original FROM documentos WHERE solicitud_id = ? AND estado <> "reemplazado"');
    $stD->execute([(int) $sol['id']]);
    foreach ($stD->fetchAll() as $d) {
        $docs[$d['tipo']] = $d;
    }
}

$estados = [
    'recibida'    => ['Recibida', 'Tu solicitud entró a la cola de revisión.', 'gris'],
    'en_revision' => ['En revisión', 'El personal de secretaría está analizando la documentación.', 'ambar'],
    'aprobada'    => ['Aprobada', 'Tu solicitud fue aceptada. Estás inscripto/a.', 'verde'],
    'rechazada'   => ['Rechazada', 'Ver el motivo y corregí lo que haga falta.', 'rojo'],
];

render('postulante/estado', [
    'titulo'      => 'Estado de la solicitud',
    'rolPanel'    => 'postulante',
    'alu'         => $alu,
    'sol'         => $sol,
    'tut'         => $tut,
    'vacante'     => $vacante,
    'espera'      => $espera,
    'hayBorrador' => $hayBorrador,
    'docs'        => $docs,
    'estados'     => $estados,
]);