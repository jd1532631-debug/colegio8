<?php
/**
 * Colegio8 — Revisión completa de una solicitud (secretaría).
 * Vista única con datos del alumno, del tutor y las dos fotos de
 * documentación; validar (aprobar) o rechazar (requiere motivo).
 *
 * La solicitud queda marcada como "en revisión" SOLO mientras esta
 * pantalla está abierta: un latido (heartbeat) renueva el bloqueo cada
 * ~30 s y el mismo vence a los 90 s de inactividad; al vencer vuelve a
 * "recibida" (para ser revisada). Si otra secretaria la está revisando,
 * la pantalla es de solo lectura.
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('secretaria');
$sec = sess('secretaria');

// Liberar bloqueos vencidos antes de consultar (estados coherentes).
liberar_revisiones_vencidas();

$id = (int) ($_GET['id'] ?? 0);

/** SQL de lectura completa de una solicitud. */
$sqlSolicitud = function (): string {
    return 'SELECT s.*, a.id AS alumno_id, a.nombre AS al_nombre, a.apellido AS al_apellido,
            a.dni AS al_dni, a.telefono AS al_telefono, a.fecha_nacimiento AS al_nac, a.anio_postulado,
            t.nombre AS tu_nombre, t.apellido AS tu_apellido, t.dni AS tu_dni,
            t.fecha_nacimiento AS tu_nac, t.telefono AS tu_telefono, t.direccion AS tu_direccion
     FROM solicitudes s
     JOIN alumnos a  ON a.id = s.alumno_id
     JOIN tutores t  ON t.alumno_id = a.id
     WHERE s.id = ?';
};

$st = db()->prepare($sqlSolicitud());
$st->execute([$id]);
$r = $st->fetch();
if (!$r) {
    flash('error', 'La solicitud indicada no existe.');
    redirect('secretaria/bandeja.php');
}

// ----- Adquirir / renovar el bloqueo "en revisión" con vencimiento ----
// Adquiere si: recibida (libre), o 'en_revision' de esta secretaria
// (renueva), o 'en_revision' vencida (reclama). NO adquiere si otra
// secretaria la tiene bloqueada con vigencia en curso.
$stU = db()->prepare(
    "UPDATE solicitudes
        SET estado = 'en_revision', secretaria_id = ?, en_revision_hasta = DATE_ADD(NOW(), INTERVAL " . REVISION_BLOQUEO_SEG . " SECOND)
      WHERE id = ? AND (
        estado = 'recibida' OR
        (estado = 'en_revision' AND (secretaria_id = ? OR en_revision_hasta IS NULL OR en_revision_hasta < NOW()))
      )"
);
$stU->execute([(int) $sec['id'], $id, (int) $sec['id']]);

if ($stU->rowCount() > 0 && $r['estado'] === 'recibida') {
    $stH = db()->prepare("INSERT INTO historial_solicitudes (solicitud_id, estado, secretaria_id) VALUES (?, 'en_revision', ?)");
    $stH->execute([$id, (int) $sec['id']]);
    $r['estado'] = 'en_revision';
}

// Re-leer para tener el estado definitivo (pudo tomarla otra secretaria).
$st = db()->prepare($sqlSolicitud());
$st->execute([$id]);
$r = $st->fetch();

$bloqueada  = false;
$bloqueador = '';
if (($r['estado'] ?? '') === 'en_revision' && (int) ($r['secretaria_id'] ?? 0) !== (int) $sec['id']) {
    $bloqueada = true;
    $stB = db()->prepare('SELECT nombre_completo FROM secretarias WHERE id = ?');
    $stB->execute([(int) $r['secretaria_id']]);
    $bloqueador = (string) $stB->fetchColumn();
}

// Documentación vigente.
$stD = db()->prepare("SELECT id, tipo, nombre_original FROM documentos WHERE solicitud_id = ? AND estado <> 'reemplazado'");
$stD->execute([$id]);
$docs = [];
foreach ($stD->fetchAll() as $d) {
    $docs[$d['tipo']] = $d;
}

$motivos = motivos_rechazo_sugeridos();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        flash('error', 'La solicitud expiró. Volvé a intentar.');
        redirect('secretaria/revision.php?id=' . $id);
    }
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'aprobar') {
        // Solo se puede resolver una solicitud que siga pendiente de revisión.
        // La condición sobre el estado + rowCount() evita que dos secretarios
        // la resuelvan en paralelo y que la última decisión pise a la anterior.
        $stU = db()->prepare("UPDATE solicitudes SET estado = 'aprobada', motivo_rechazo = NULL, fecha_resolucion = NOW(), secretaria_id = ?, en_revision_hasta = NULL WHERE id = ? AND estado IN ('recibida','en_revision')");
        $stU->execute([(int) $sec['id'], $id]);
        if ($stU->rowCount() === 0) {
            flash('error', 'No se pudo aprobar: la solicitud ya fue resuelta por otra secretaria.');
            redirect('secretaria/revision.php?id=' . $id);
        }
        $stD2 = db()->prepare("UPDATE documentos SET estado = 'validado' WHERE solicitud_id = ? AND estado <> 'reemplazado'");
        $stD2->execute([$id]);
        $stH = db()->prepare("INSERT INTO historial_solicitudes (solicitud_id, estado, secretaria_id) VALUES (?, 'aprobada', ?)");
        $stH->execute([$id, (int) $sec['id']]);
        flash('exito', 'Solicitud aprobada. Podés asignarle un curso desde Asignación de cursos.');
        redirect('secretaria/bandeja.php');
    } elseif ($accion === 'rechazar') {
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        if (mb_strlen($motivo) < 5) {
            flash('error', 'Para rechazar es obligatorio escribir un motivo.');
            redirect('secretaria/revision.php?id=' . $id);
        }
        $stU = db()->prepare("UPDATE solicitudes SET estado = 'rechazada', motivo_rechazo = ?, fecha_resolucion = NOW(), secretaria_id = ?, en_revision_hasta = NULL WHERE id = ? AND estado IN ('recibida','en_revision')");
        $stU->execute([$motivo, (int) $sec['id'], $id]);
        if ($stU->rowCount() === 0) {
            flash('error', 'No se pudo rechazar: la solicitud ya fue resuelta por otra secretaria.');
            redirect('secretaria/revision.php?id=' . $id);
        }
        $stH = db()->prepare("INSERT INTO historial_solicitudes (solicitud_id, estado, motivo, secretaria_id) VALUES (?, 'rechazada', ?, ?)");
        $stH->execute([$id, $motivo, (int) $sec['id']]);
        flash('info', 'Solicitud rechazada. El postulante podrá corregir y reenviar.');
        redirect('secretaria/bandeja.php');
    }
}

render('secretaria/revision', [
    'titulo'      => 'Revisión de solicitud',
    'rolPanel'    => 'secretaria',
    'r'           => $r,
    'docs'        => $docs,
    'motivos'     => $motivos,
    'bloqueada'   => $bloqueada,
    'bloqueador'  => $bloqueador,
    'segBloqueo'  => REVISION_BLOQUEO_SEG,
]);