<?php
/**
 * Colegio8 — Bandeja de entrada (solicitudes pendientes de revisión).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('secretaria');

// Liberar bloqueos vencidos (revisiones abandonadas vuelven a 'recibida').
liberar_revisiones_vencidas();

// Flujo: ¿hay aprobadas sin división asignada para invitar a asignarlas?
$sinVacante = (int) db()->query(
    "SELECT COUNT(*) FROM solicitudes s
       JOIN alumnos a ON a.id = s.alumno_id
      WHERE s.estado = 'aprobada' AND a.vacante_id IS NULL"
)->fetchColumn();

$porPagina = 25;
$pag = max(1, (int) ($_GET['pagina'] ?? 1));
$offset = ($pag - 1) * $porPagina;

$total = (int) db()->query('SELECT COUNT(*) FROM solicitudes WHERE estado IN ("recibida","en_revision")')->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pag > $totalPaginas) {
    $pag = $totalPaginas;
    $offset = ($pag - 1) * $porPagina;
}

// Ordenadas por antigüedad (las más viejas primero).
$bandeja = db()->prepare(
    'SELECT s.id, s.estado, s.fecha_presentacion, s.motivo_rechazo,
            a.id AS alumno_id, a.nombre, a.apellido, a.anio_postulado, a.dni,
            sec.nombre_completo AS revisor
     FROM solicitudes s
     JOIN alumnos a ON a.id = s.alumno_id
     LEFT JOIN secretarias sec ON sec.id = s.secretaria_id
     WHERE s.estado IN ("recibida","en_revision")
     ORDER BY s.fecha_presentacion ASC
     LIMIT ' . $porPagina . ' OFFSET ' . $offset
);
$bandeja->execute();
$bandeja = $bandeja->fetchAll();

$badges = [
    'recibida'    => ['badge-gris',  'Recibida'],
    'en_revision' => ['badge-ambar', 'En revisión'],
];

render('secretaria/bandeja', [
    'titulo'      => 'Bandeja de entrada',
    'rolPanel'    => 'secretaria',
    'bandeja'     => $bandeja,
    'badges'      => $badges,
    'total'       => $total,
    'pag'         => $pag,
    'totalPaginas'=> $totalPaginas,
    'sinVacante'  => $sinVacante,
]);