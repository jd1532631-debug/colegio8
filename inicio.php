<?php
/**
 * Colegio8 — Panel inicial de secretaría.
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('secretaria');
$sec = sess('secretaria');

// Liberar bloqueos vencidos (revisiones abandonadas vuelven a 'recibida').
liberar_revisiones_vencidas();

$pendientes  = (int) db()->query('SELECT COUNT(*) FROM solicitudes WHERE estado IN ("recibida","en_revision")')->fetchColumn();
$aprobadasSinAsignar = (int) db()->query('SELECT COUNT(*) FROM solicitudes WHERE estado = "aprobada" AND alumno_id NOT IN (SELECT id FROM alumnos WHERE vacante_id IS NOT NULL)')->fetchColumn();
$inscriptos  = (int) db()->query('SELECT COUNT(*) FROM alumnos WHERE vacante_id IS NOT NULL')->fetchColumn();
$enEspera    = (int) db()->query('SELECT COUNT(*) FROM lista_espera')->fetchColumn();
$cursosLlenos = (int) db()->query(
    'SELECT COUNT(*) FROM vacantes v
     WHERE v.activo = 1 AND v.cupo_total > 0
       AND (SELECT COUNT(*) FROM alumnos a WHERE a.vacante_id = v.id) >= v.cupo_total'
)->fetchColumn();
$totalVacantes = (int) db()->query('SELECT COUNT(*) FROM vacantes WHERE activo = 1')->fetchColumn();

$recientes = db()->query(
    'SELECT s.id, s.estado, s.fecha_presentacion, s.motivo_rechazo,
            a.nombre, a.apellido, a.anio_postulado, a.vacante_id
     FROM solicitudes s
     JOIN alumnos a ON a.id = s.alumno_id
     ORDER BY s.fecha_presentacion DESC
     LIMIT 6'
)->fetchAll();

render('secretaria/inicio', [
    'titulo'   => 'Inicio · Secretaría',
    'rolPanel' => 'secretaria',
    'sec'      => $sec,
    'pendientes'   => $pendientes,
    'aprobadasSinAsignar' => $aprobadasSinAsignar,
    'inscriptos'   => $inscriptos,
    'enEspera'     => $enEspera,
    'cursosLlenos' => $cursosLlenos,
    'totalVacantes'=> $totalVacantes,
    'recientes'    => $recientes,
]);