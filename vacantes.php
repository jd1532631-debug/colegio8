<?php
/**
 * Colegio8 — Gestión de cupos y cursos (vacantes).
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('secretaria');

$anios = [1, 2, 3, 4, 5];
$vistaForm = $_GET['vista'] ?? '';
$editando = null;
if ($vistaForm === 'editar') {
    $stE = db()->prepare('SELECT * FROM vacantes WHERE id = ?');
    $stE->execute([(int) ($_GET['id'] ?? 0)]);
    $editando = $stE->fetch();
    if (!$editando) {
        flash('error', 'El curso indicado no existe.');
        redirect('secretaria/vacantes.php');
    }
}

$errores = [];
$valores = [
    'anio' => $_POST['anio'] ?? ($editando['anio'] ?? 1),
    'turno' => $_POST['turno'] ?? ($editando['turno'] ?? 'manana'),
    'division' => $_POST['division'] ?? ($editando['division'] ?? 'A'),
    'cupo_total' => $_POST['cupo_total'] ?? ($editando['cupo_total'] ?? 35),
    'activo' => (int) ($_POST['activo'] ?? ($editando['activo'] ?? 1)),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró.';
    } else {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'eliminar') {
            $id = (int) ($_POST['id'] ?? 0);
            $stV = db()->prepare('SELECT COUNT(*) FROM alumnos WHERE vacante_id = ?');
            $stV->execute([$id]);
            if (!$stV->fetchColumn()) {
                db()->prepare('DELETE FROM vacantes WHERE id = ?')->execute([$id]);
                flash('exito', 'Curso eliminado.');
            } else {
                flash('error', 'No se puede eliminar un curso con alumnos asignados.');
            }
            redirect('secretaria/vacantes.php');
        }

        if (in_array((int) $valores['anio'], $anios, true) === false) $errores[] = 'Año inválido.';
        if (!in_array($valores['turno'], ['manana', 'tarde'], true)) $errores[] = 'Turno inválido.';
        $valores['division'] = strtoupper(trim($valores['division']));
        if (preg_match('/^[A-Z]$/', $valores['division']) !== 1) $errores[] = 'La división debe ser una sola letra (A, B…).';
        $cupo = (int) $valores['cupo_total'];
        if ($cupo < 1 || $cupo > 300) $errores[] = 'El cupo debe estar entre 1 y 300.';
        $valores['activo'] = (int) ($_POST['activo'] ?? 1) === 1 ? 1 : 0;

        if (!$errores) {
            $dup = db()->prepare('SELECT id FROM vacantes WHERE anio = ? AND turno = ? AND division = ? AND id <> ?');
            $dup->execute([(int) $valores['anio'], $valores['turno'], $valores['division'], (int) ($editando['id'] ?? 0)]);
            if ($dup->fetch()) {
                $errores[] = 'Ya existe ese curso (año + turno + división).';
            }
        }

        if (!$errores && $accion === 'crear') {
            $st = db()->prepare('INSERT INTO vacantes (anio, turno, division, cupo_total, activo) VALUES (?, ?, ?, ?, ?)');
            $st->execute([(int) $valores['anio'], $valores['turno'], $valores['division'], $cupo, $valores['activo']]);
            flash('exito', 'Curso creado.');
            redirect('secretaria/vacantes.php');
        }
        if (!$errores && $accion === 'actualizar') {
            $id = (int) $editando['id'];
            // No permitir bajar el cupo por debajo de la ocupación real.
            $ocupado = cupo_ocupado_vacante($id);
            if ($cupo < $ocupado) {
                $errores[] = 'El cupo no puede ser menor que la cantidad de alumnos ya asignados (' . $ocupado . ').';
            } else {
                $st = db()->prepare('UPDATE vacantes SET anio = ?, turno = ?, division = ?, cupo_total = ?, activo = ? WHERE id = ?');
                $st->execute([(int) $valores['anio'], $valores['turno'], $valores['division'], $cupo, $valores['activo'], $id]);
                flash('exito', 'Curso actualizado.');
                redirect('secretaria/vacantes.php');
            }
        }
    }
}

$cursos = db()->query(
    'SELECT v.*, (SELECT COUNT(*) FROM alumnos a WHERE a.vacante_id = v.id) AS cupo_ocupado
     FROM vacantes v ORDER BY v.anio, v.division, CASE v.turno WHEN "manana" THEN 1 ELSE 2 END'
)->fetchAll();

// ----- Filtros (GET) calculados sobre la ocupación/estado actual -----
$filtroTurno    = $_GET['turno'] ?? '';
$filtroOcupacion = $_GET['ocupacion'] ?? '';
$filtroEstado   = $_GET['estado'] ?? '';

foreach ($cursos as &$c) {
    $c['ocupacion'] = 'disponible';
    if ((int) $c['cupo_total'] > 0) {
        $ratio = (int) $c['cupo_ocupado'] * 4 / (int) $c['cupo_total'];
        if ((int) $c['cupo_ocupado'] >= (int) $c['cupo_total']) {
            $c['ocupacion'] = 'lleno';
        } elseif ($ratio >= 3) { // >= 75 %
            $c['ocupacion'] = 'casi';
        } elseif ($ratio >= 2) { // >= 50 %
            $c['ocupacion'] = 'con_lugar';
        }
    }
}
unset($c);

$dictOcupacion = [
    'lleno'      => 'Lleno',
    'casi'       => 'Casi lleno',
    'con_lugar'  => 'Con lugar',
    'disponible' => 'Disponible',
];

$cursosMostrar = array_filter($cursos, function ($c) use ($filtroTurno, $filtroOcupacion, $filtroEstado) {
    if ($filtroTurno !== '' && (string) $c['turno'] !== $filtroTurno) return false;
    if ($filtroOcupacion !== '' && $c['ocupacion'] !== $filtroOcupacion) return false;
    if ($filtroEstado !== '') {
        $activo = (int) $c['activo'] === 1 ? 'activo' : 'inactivo';
        if ($activo !== $filtroEstado) return false;
    }
    return true;
});

render('secretaria/vacantes', [
    'titulo'   => 'Cupos y cursos',
    'rolPanel' => 'secretaria',
    'cursos'   => $cursosMostrar,
    'cursosTotal' => count($cursos),
    'anios'    => $anios,
    'valores'  => $valores,
    'editar'   => $editando,
    'errores'  => $errores,
    'vistaForm'  => $vistaForm,
    'dictOcupacion' => $dictOcupacion,
    'filtroTurno'    => $filtroTurno,
    'filtroOcupacion'=> $filtroOcupacion,
    'filtroEstado'   => $filtroEstado,
]);