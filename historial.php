<?php
/**
 * Colegio8 — Historial de solicitudes resueltas (aprobadas o rechazadas).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('secretaria');

$fEst = $_GET['estado'] ?? '';
$fBus = trim((string) ($_GET['buscar'] ?? ''));

$where = ["s.estado IN ('aprobada','rechazada')"];
$params = [];
if (in_array($fEst, ['aprobada', 'rechazada'], true)) {
    $where[] = 's.estado = ?';
    $params[] = $fEst;
}
if ($fBus !== '') {
    $where[] = '(a.nombre LIKE ? OR a.apellido LIKE ? OR a.dni LIKE ? OR t.dni LIKE ?)';
    $bus = '%' . $fBus . '%';
    array_push($params, $bus, $bus, $bus, $bus);
}
$whereSql = implode(' AND ', $where);

$porPagina = 30;
$pag = max(1, (int) ($_GET['pagina'] ?? 1));
$offset = ($pag - 1) * $porPagina;

$stT = db()->prepare('SELECT COUNT(*) FROM solicitudes s JOIN alumnos a ON a.id = s.alumno_id LEFT JOIN tutores t ON t.alumno_id = a.id WHERE ' . $whereSql);
$stT->execute($params);
$total = (int) $stT->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pag > $totalPaginas) {
    $pag = $totalPaginas;
    $offset = ($pag - 1) * $porPagina;
}

$st = db()->prepare(
    'SELECT s.id, s.estado, s.fecha_presentacion, s.fecha_resolucion, s.motivo_rechazo,
            a.id AS alumno_id, a.nombre, a.apellido, a.anio_postulado, a.vacante_id,
            v.anio AS cu_anio, v.turno AS cu_turno, v.division AS cu_div
     FROM solicitudes s
     JOIN alumnos a ON a.id = s.alumno_id
     LEFT JOIN tutores t ON t.alumno_id = a.id
     LEFT JOIN vacantes v ON v.id = a.vacante_id
     WHERE ' . $whereSql . '
     ORDER BY s.fecha_resolucion DESC
     LIMIT ' . $porPagina . ' OFFSET ' . $offset
);
$st->execute($params);
$items = $st->fetchAll();

render('secretaria/historial', [
    'titulo'      => 'Historial',
    'rolPanel'    => 'secretaria',
    'items'       => $items,
    'fEst'        => $fEst,
    'fBus'        => $fBus,
    'total'       => $total,
    'pag'         => $pag,
    'totalPaginas'=> $totalPaginas,
]);