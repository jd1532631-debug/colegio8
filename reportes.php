<?php
/**
 * Colegio8 — Reportes de secretaría (CSV + vista para imprimir/PDF).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('secretaria');

$tipo = $_GET['tipo'] ?? 'postulaciones';
$descargar = ($_GET['descargar'] ?? '') === '1';
$imprimir  = ($_GET['imprimir'] ?? '') === '1';

/* ---------------- Construcción de cada reporte ---------------- */

function datos_reporte(string $tipo): array
{
    // Devuelve ['nombre' => ..., 'columnas' => [Titulo=>Clave], 'filas' => [...]]
    switch ($tipo) {
        case 'postulaciones':
            $fEst = $_GET['estado'] ?? '';
            $sql = 'SELECT a.apellido, a.nombre, a.dni AS dni_alumno, a.anio_postulado,
                           t.nombre AS tu_nombre, t.apellido AS tu_apellido, t.dni AS dni_tutor, t.telefono AS tel_tutor,
                           s.estado, s.fecha_presentacion, s.fecha_resolucion, s.motivo_rechazo
                    FROM solicitudes s
                    JOIN alumnos a ON a.id = s.alumno_id
                    LEFT JOIN tutores t ON t.alumno_id = a.id ';
            $params = [];
            if (in_array($fEst, ['recibida', 'en_revision', 'aprobada', 'rechazada'], true)) {
                $sql .= 'WHERE s.estado = ?';
                $params[] = $fEst;
            }
            $sql .= ' ORDER BY a.apellido, a.nombre';
            $st = db()->prepare($sql);
            $st->execute($params);
            return [
                'nombre' => 'postulaciones',
                'columnas' => [
                    'Apellido' => 'apellido', 'Nombre' => 'nombre', 'DNI alumno' => 'dni_alumno',
                    'Año' => 'anio_postulado', 'Tutor' => 'tu_nombre_t',
                    'DNI tutor' => 'dni_tutor', 'Tel. tutor' => 'tel_tutor',
                    'Estado' => 'estado', 'Presentada' => 'fecha_presentacion',
                    'Resuelta' => 'fecha_resolucion', 'Motivo' => 'motivo_rechazo',
                ],
                'filas' => array_map(fn($f) => $f + ['tu_nombre_t' => ($f['tu_nombre'] ?? '') . ' ' . ($f['tu_apellido'] ?? '')], $st->fetchAll()),
            ];

        case 'inscriptos':
            $cursoId = (int) ($_GET['curso'] ?? 0);
            $sql = "SELECT v.anio, v.turno, v.division,
                           a.nombre, a.apellido, a.dni, a.telefono,
                           t.nombre AS tu_nombre, t.apellido AS tu_apellido, t.telefono AS tu_telefono
                    FROM alumnos a
                    JOIN vacantes v ON v.id = a.vacante_id
                    LEFT JOIN tutores t ON t.alumno_id = a.id ";
            $params = [];
            if ($cursoId) {
                $sql .= 'WHERE v.id = ? ';
                $params[] = $cursoId;
            }
            $sql .= 'ORDER BY v.anio, v.turno DESC, v.division, a.apellido, a.nombre';
            $st = db()->prepare($sql);
            $st->execute($params);
            $filas = $st->fetchAll();
            foreach ($filas as &$f) {
                $f['curso_titulo'] = (int) $f['anio'] . '° ' . $f['division'] . ' (' . ($f['turno'] === 'manana' ? 'mañana' : 'tarde') . ')';
                $f['tutor_titulo'] = ($f['tu_nombre'] ?? '') . ' ' . ($f['tu_apellido'] ?? '');
            }
            unset($f);
            return [
                'nombre' => 'inscriptos_por_curso',
                'columnas' => [
                    'Curso' => 'curso_titulo', 'Apellido' => 'apellido', 'Nombre' => 'nombre',
                    'DNI' => 'dni', 'Tel. alumno' => 'telefono', 'Tutor' => 'tutor_titulo',
                    'Tel. tutor' => 'tu_telefono',
                ],
                'filas' => $filas,
            ];

        case 'cupos':
            $st = db()->query(
                "SELECT v.anio, v.turno, v.division, v.cupo_total, v.activo,
                        (SELECT COUNT(*) FROM alumnos a WHERE a.vacante_id = v.id) AS cupo_ocupado
                 FROM vacantes v ORDER BY v.anio, v.turno DESC, v.division"
            );
            $filas = $st->fetchAll();
            foreach ($filas as &$f) {
                $f['curso_titulo'] = (int) $f['anio'] . '° ' . $f['division'] . ' (' . ($f['turno'] === 'manana' ? 'mañana' : 'tarde') . ')';
                $f['libres'] = max(0, (int) $f['cupo_total'] - (int) $f['cupo_ocupado']);
                $f['estado_t'] = (int) $f['cupo_ocupado'] >= (int) $f['cupo_total'] ? 'Lleno' : 'Con lugar';
            }
            unset($f);
            return [
                'nombre' => 'resumen_cupos',
                'columnas' => ['Curso' => 'curso_titulo', 'Cupo total' => 'cupo_total',
                    'Ocupados' => 'cupo_ocupado', 'Libres' => 'libres', 'Estado' => 'estado_t'],
                'filas' => $filas,
            ];

        case 'espera':
            $st = db()->query(
                "SELECT le.posicion, le.fecha_ingreso, v.anio, v.turno, v.division,
                        a.nombre, a.apellido, a.dni
                 FROM lista_espera le
                 JOIN vacantes v ON v.id = le.vacante_id
                 JOIN alumnos a ON a.id = le.alumno_id
                 ORDER BY v.anio, v.turno DESC, v.division, le.posicion"
            );
            $filas = $st->fetchAll();
            foreach ($filas as &$f) {
                $f['curso_titulo'] = (int) $f['anio'] . '° ' . $f['division'] . ' (' . ($f['turno'] === 'manana' ? 'mañana' : 'tarde') . ')';
            }
            unset($f);
            return [
                'nombre' => 'lista_espera',
                'columnas' => ['Posición' => 'posicion', 'Curso' => 'curso_titulo',
                    'Apellido' => 'apellido', 'Nombre' => 'nombre', 'DNI' => 'dni', 'Desde' => 'fecha_ingreso'],
                'filas' => $filas,
            ];
    }
    return ['nombre' => '', 'columnas' => [], 'filas' => []];
}

$reporte = datos_reporte($tipo);
if (!$reporte['filas'] && $reporte['nombre'] !== '') {
    // Mantener columnas aunque no haya filas (CSV con encabezado).
}

if ($descargar && $reporte['nombre'] !== '') {
    descargar_csv('colegio8_' . $reporte['nombre'] . '_' . date('Ymd') . '.csv', $reporte['columnas'], $reporte['filas']);
}

$tiposInfo = [
    'postulaciones' => ['Postulaciones', 'Todas las solicitudes con su estado y motivo.'],
    'inscriptos'    => ['Inscriptos por curso', 'Listado de alumnos asignados a cada división.'],
    'cupos'         => ['Resumen de cupos', 'Ocupación de todos los cursos.'],
    'espera'        => ['Lista de espera', 'Alumnos esperando un cupo, con su posición.'],
];

render('secretaria/reportes', [
    'titulo'        => 'Reportes · Secretaría',
    'rolPanel'      => 'secretaria',
    'tipo'          => $tipo,
    'reporte'       => $reporte,
    'tiposInfo'     => $tiposInfo,
    'vistaImpresion'=> $imprimir,
]);