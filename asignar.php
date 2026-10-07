<?php
/**
 * Colegio8 — Asignación de división a alumnos aprobados.
 * Si el curso elegido está lleno, el alumno entra a la lista de espera
 * con posición numérica en vez de fallar.
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('secretaria');

// Alumnos aprobados sin división (incluye quienes están en lista de espera).
$porAsignar = db()->query(
    'SELECT a.id, a.nombre, a.apellido, a.dni, a.anio_postulado,
            s.fecha_resolucion,
            le.posicion AS en_espera_pos, le.vacante_id AS en_espera_vacante, vw.anio AS esp_anio, vw.division AS esp_div, vw.turno AS esp_turno
     FROM solicitudes s
     JOIN alumnos a ON a.id = s.alumno_id
     LEFT JOIN lista_espera le ON le.alumno_id = a.id
     LEFT JOIN vacantes vw ON vw.id = le.vacante_id
     WHERE s.estado = "aprobada" AND a.vacante_id IS NULL
     ORDER BY s.fecha_resolucion'
)->fetchAll();

$vacantesActivas = db()->query(
    'SELECT v.*, (SELECT COUNT(*) FROM alumnos a WHERE a.vacante_id = v.id) AS cupo_ocupado
     FROM vacantes v WHERE v.activo = 1 ORDER BY v.anio, v.turno DESC, v.division'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        flash('error', 'La solicitud expiró.');
        redirect('secretaria/asignar.php');
    }
    $accion = $_POST['accion'] ?? 'asignar';

    if ($accion === 'reenviar') {
        // Vuelve la postulación a 'recibida': sale de lista de espera y
        // queda pendiente que la secretaria la revise (o el postulante la
        // corrija). Queda registrado en el historial como 'reenviada'.
        $db = db();
        $db->beginTransaction();
        try {
            $alumnoId = (int) ($_POST['alumno_id'] ?? 0);
            if (!$alumnoId) {
                throw new RuntimeException('Alumno no especificado.');
            }
            $st = $db->prepare('SELECT id FROM solicitudes WHERE alumno_id = ?');
            $st->execute([$alumnoId]);
            $solicitudId = $st->fetchColumn();
            if (!$solicitudId) {
                $db->rollBack();
                flash('error', 'No se encontró la solicitud de ese alumno.');
                redirect('secretaria/asignar.php');
            }
            $db->prepare(
                "UPDATE solicitudes SET estado = 'recibida', motivo_rechazo = NULL,
                        fecha_resolucion = NULL, secretaria_id = NULL, en_revision_hasta = NULL
                 WHERE id = ?"
            )->execute([(int) $solicitudId]);
            $db->prepare('DELETE FROM lista_espera WHERE alumno_id = ?')->execute([$alumnoId]);
            $db->prepare(
                "INSERT INTO historial_solicitudes (solicitud_id, estado, motivo)
                 VALUES (?, 'reenviada', 'Reenviada por la secretaría: vuelve a revisión de postulación.')"
            )->execute([(int) $solicitudId]);
            $db->commit();
            flash('info', 'La solicitud fue reenviada a revisión y el alumno salió de la lista de espera.');
        } catch (Throwable $t) {
            $db->rollBack();
            flash('error', 'No se pudo reenviar la solicitud: ' . $t->getMessage());
        }
        redirect('secretaria/asignar.php');
    }

    $alumnoId = (int) ($_POST['alumno_id'] ?? 0);
    $vacanteId = (int) ($_POST['vacante_id'] ?? 0);
    if ($alumnoId && $vacanteId) {
        $res = asignar_alumno_a_vacante($alumnoId, $vacanteId);
        flash($res['ok'] ? ($res['tipo'] === 'espera' ? 'info' : 'exito') : 'error', $res['detalle']);
    } else {
        flash('error', 'Elegí un curso de destino.');
    }
    redirect('secretaria/asignar.php');
}

$resaltar = (int) ($_GET['resaltar'] ?? 0);

render('secretaria/asignar', [
    'titulo'        => 'Asignación de cursos',
    'rolPanel'      => 'secretaria',
    'porAsignar'    => $porAsignar,
    'vacantesActivas' => $vacantesActivas,
    'resaltar'      => $resaltar,
]);