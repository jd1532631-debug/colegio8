<?php
/**
 * Colegio8 — Detalle de un curso (lista de alumnos, mover, quitar).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('secretaria');
$sec = sess('secretaria');

$id = (int) ($_GET['id'] ?? 0);
$stV = db()->prepare('SELECT * FROM vacantes WHERE id = ?');
$stV->execute([$id]);
$curso = $stV->fetch();
if (!$curso) {
    flash('error', 'El curso indicado no existe.');
    redirect('secretaria/vacantes.php');
}
// Ocupación real calculada (única fuente de verdad: alumnos asignados).
$curso['cupo_ocupado'] = cupo_ocupado_vacante((int) $curso['id']);
$ocupadosTodos = cupos_ocupados_todos();

$alumnos = db()->prepare(
    'SELECT a.id, a.nombre, a.apellido, a.dni, a.telefono,
            t.nombre AS tu_nombre, t.apellido AS tu_apellido, t.dni AS tu_dni, t.telefono AS tu_telefono
     FROM alumnos a
     LEFT JOIN tutores t ON t.alumno_id = a.id
     WHERE a.vacante_id = ?
     ORDER BY a.apellido, a.nombre'
);
$alumnos->execute([$id]);
$alumnos = $alumnos->fetchAll();

$espera = db()->prepare(
    'SELECT le.posicion, le.fecha_ingreso, a.id AS alumno_id, a.nombre, a.apellido
     FROM lista_espera le
     JOIN alumnos a ON a.id = le.alumno_id
     WHERE le.vacante_id = ?
     ORDER BY le.posicion'
);
$espera->execute([$id]);
$espera = $espera->fetchAll();

$opciones = db()->query('SELECT * FROM vacantes WHERE activo = 1 ORDER BY anio, turno DESC, division')->fetchAll();

// Destinos posibles para mover, con cupo disponible (para la UI del modal).
$destinos = [];
foreach ($opciones as $o) {
    if ((int) $o['id'] === (int) $id) {
        continue;
    }
    $disponibles = (int) $o['cupo_total'] - ($ocupadosTodos[(int) $o['id']] ?? 0);
    $destinos[] = [
        'id'          => (int) $o['id'],
        'titulo'      => curso_titulo($o),
        'disponibles' => max(0, $disponibles),
        'lleno'       => $disponibles <= 0,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        flash('error', 'La solicitud expiró.');
        redirect('secretaria/curso.php?id=' . $id);
    }
    $accion = $_POST['accion'] ?? '';
    $alumnoId = (int) ($_POST['alumno_id'] ?? 0);

    if ($accion === 'quitar' && $alumnoId) {
        flash_libre_ok(liberar_vacante_alumno($alumnoId));
    } elseif ($accion === 'mover' && $alumnoId) {
        $destino = (int) ($_POST['destino_id'] ?? 0);
        $permitirLleno = (int) ($_POST['permitir_lleno'] ?? 0) === 1;

        if (!$destino || $destino === $id) {
            flash('error', 'Elegí un curso de destino distinto.');
        } else {
            $stD = db()->prepare('SELECT * FROM vacantes WHERE id = ? AND activo = 1');
            $stD->execute([$destino]);
            $destVac = $stD->fetch();
            if (!$destVac) {
                flash('error', 'El curso de destino no existe o está inactivo.');
            } else {
                $llenoDestino = ($ocupadosTodos[$destino] ?? 0) >= (int) $destVac['cupo_total'];
                if ($llenoDestino && !$permitirLleno) {
                    flash('error', 'El curso ' . curso_titulo($destVac) . ' está lleno. Marcá la opción de mover igualmente para enviar al alumno a su lista de espera.');
                } else {
                    $res = mover_alumno_de_curso($alumnoId, $destino);
                    if ($res['ok']) {
                        // Trazabilidad: registra el cambio en el historial de la solicitud.
                        $stS = db()->prepare('SELECT id FROM solicitudes WHERE alumno_id = ? ORDER BY id DESC LIMIT 1');
                        $stS->execute([$alumnoId]);
                        $solId = $stS->fetchColumn();
                        if ($solId) {
                            $motivo = 'Movido de ' . curso_titulo($curso) . ' a ' . curso_titulo($destVac)
                                . (($res['tipo'] ?? '') === 'espera' ? ' (destino lleno: entró a la lista de espera)' : '')
                                . ' por decisión de secretaría.';
                            $stH = db()->prepare("INSERT INTO historial_solicitudes (solicitud_id, estado, motivo, secretaria_id) VALUES (?, 'movido_de_curso', ?, ?)");
                            $stH->execute([(int) $solId, $motivo, (int) $sec['id']]);
                        }
                        flash('exito', $res['detalle']);
                    } else {
                        flash('error', $res['detalle']);
                    }
                }
            }
        }
    }
    redirect('secretaria/curso.php?id=' . $id);
}

// Movimientos recientes que involucran a este curso (trazabilidad visible).
$movimientos = db()->prepare(
    "SELECT h.fecha, h.motivo, a.nombre, a.apellido
     FROM historial_solicitudes h
     JOIN solicitudes s ON s.id = h.solicitud_id
     JOIN alumnos a ON a.id = s.alumno_id
     WHERE h.estado = 'movido_de_curso' AND h.motivo LIKE ?
     ORDER BY h.fecha DESC
     LIMIT 10"
);
$movimientos->execute(['%' . curso_titulo($curso) . '%']);
$movimientos = $movimientos->fetchAll();

function flash_libre_ok(array $res): void
{
    if ($res['ok']) {
        flash('exito', $res['detalle']);
    } else {
        flash('error', $res['detalle']);
    }
}

render('secretaria/curso', [
    'titulo'   => curso_titulo($curso),
    'rolPanel' => 'secretaria',
    'curso'    => $curso,
    'alumnos'  => $alumnos,
    'espera'   => $espera,
    'destinos' => $destinos,
    'movimientos' => $movimientos,
]);