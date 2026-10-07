<?php
/**
 * Colegio8 — Wizard de solicitud de inscripción (postulante/tutor).
 *
 * Flujo:
 *   Paso 1. Datos del alumno + foto/escaneo del DNI del alumno.
 *   Paso 2. Datos del tutor/responsable + foto/escaneo del DNI del tutor.
 *   Paso 3. Revisión final y envío.
 *
 * Si la solicitud fue rechazada, el mismo wizard permite corregir datos
 * y reemplazar la documentación para reenviarla (vuelve a 'recibida').
 * Mientras está 'recibida', 'en_revision' o 'aprobada' no se puede editar.
 *
 * La documentación se guarda en el servidor (carpeta uploads) y se
 * referencia por ruta en la tabla `documentos`.
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('postulante');

$uid = (int) sess('postulante')['id'];
$anios = [1, 2, 3, 4, 5];

// El borrador vive por usuario (la sesión PHP es compartida si cambian de
// cuenta en el mismo navegador): evita que los datos de una cuenta aparezcan
// en otra. Se migra un eventual borrador viejo sin clave por usuario.
$claveDraft = 'col8_app_' . $uid;
if (isset($_SESSION['col8_app']) && !isset($_SESSION[$claveDraft])) {
    $_SESSION[$claveDraft] = $_SESSION['col8_app'];
    unset($_SESSION['col8_app']);
}

/* ---------- Datos actuales del usuario (si ya postuló) ---------- */
$alu = null;
$sol = null;
$tut = null;
$stA = db()->prepare('SELECT * FROM alumnos WHERE usuario_id = ?');
$stA->execute([$uid]);
$alu = $stA->fetch();
if ($alu) {
    $stS = db()->prepare('SELECT * FROM solicitudes WHERE alumno_id = ? ORDER BY id DESC LIMIT 1');
    $stS->execute([(int) $alu['id']]);
    $sol = $stS->fetch();
    $stT = db()->prepare('SELECT * FROM tutores WHERE alumno_id = ?');
    $stT->execute([(int) $alu['id']]);
    $tut = $stT->fetch();
}

$modo = 'nueva';
if ($alu && $sol) {
    if (in_array($sol['estado'], ['recibida', 'en_revision', 'aprobada'], true)) {
        flash('info', 'Tu solicitud está en etapa «' . $sol['estado'] . '»; no se puede editar en este momento.');
        redirect('postulante/estado.php');
    }
    // Rechazada → modo corrección
    $modo = 'correccion';
}

/* ---------- Borrador en sesión (por usuario) ---------- */
if (empty($_SESSION[$claveDraft])) {
    $_SESSION[$claveDraft] = [
        'alumno' => $alu ? [
            'nombre' => $alu['nombre'], 'apellido' => $alu['apellido'], 'dni' => $alu['dni'],
            'telefono' => $alu['telefono'], 'fecha_nacimiento' => $alu['fecha_nacimiento'],
            'anio_postulado' => $alu['anio_postulado'],
        ] : [],
        'tutor' => $tut ? [
            'nombre' => $tut['nombre'], 'apellido' => $tut['apellido'], 'dni' => $tut['dni'],
            'fecha_nacimiento' => $tut['fecha_nacimiento'], 'telefono' => $tut['telefono'],
            'direccion' => $tut['direccion'],
        ] : [],
        'token' => bin2hex(random_bytes(8)),
        'docs'  => array_fill_keys(tipos_dni_documentos(), null),
    ];
    if ($sol) {
        // Pre-cargar los documentos vigentes de la solicitud existente.
        $stD = db()->prepare('SELECT tipo, id FROM documentos WHERE solicitud_id = ? AND estado <> "reemplazado"');
        $stD->execute([(int) $sol['id']]);
        foreach ($stD->fetchAll() as $d) {
            if (array_key_exists($d['tipo'], $_SESSION[$claveDraft]['docs'])) {
                $_SESSION[$claveDraft]['docs'][$d['tipo']] = (int) $d['id'];
            }
        }
    }
}
$draft = &$_SESSION[$claveDraft];
$token = $draft['token'];

/* ---------- Utilidades de documentos del borrador ---------- */
function doc_del_token_marker(string $token, string $tipo): ?array
{
    $st = db()->prepare('SELECT id, ruta FROM documentos WHERE borrador_token = ? AND tipo = ? ORDER BY id DESC LIMIT 1');
    $st->execute([$token, $tipo]);
    return $st->fetch() ?: null;
}

function doc_actual(?int $id): ?array
{
    if (!$id) return null;
    $st = db()->prepare('SELECT id, nombre_original, tipo FROM documentos WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/** Sube un documento del borrador; reemplaza el anterior del mismo tipo. */
function subir_doc_borrador(string $token, string $tipo, array &$erroresCtx): ?int
{
    if (!empty($_FILES[$tipo]['name'])) {
        $errores = [];
        $ruta = subir_archivo($tipo, 'inscripciones', $errores);
        if (!$ruta) {
            $erroresCtx[] = $errores[0] ?? 'No se pudo guardar el archivo.';
            return null;
        }
        // Reemplazo: eliminar borrador anterior físico.
        $anterior = doc_del_token_marker($token, $tipo);
        if ($anterior && strpos((string) $anterior['ruta'], 'uploads/') === 0) {
            borrar_archivo($anterior['ruta']);
            db()->prepare('DELETE FROM documentos WHERE id = ?')->execute([(int) $anterior['id']]);
        }
        $st = db()->prepare('INSERT INTO documentos (solicitud_id, borrador_token, tipo, nombre_original, ruta, estado)
                             VALUES (NULL, ?, ?, ?, ?, "recibido")');
        $st->execute([$token, $tipo, $_FILES[$tipo]['name'], $ruta]);
        return (int) db()->lastInsertId();
    }
    return null;
}

$errores = [];
$paso = (int) ($_GET['paso'] ?? 3);
// Ruta por defecto: continuar en el último paso guardado si existe.
if (!isset($_GET['paso'])) {
    $paso = !empty($draft['alumno']) && !empty($draft['tutor']) ? 3 : (empty($draft['alumno']) ? 1 : 2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $errores[] = 'La solicitud expiró. Volvé a intentar.';
    } else {
        $accion = $_POST['accion'] ?? 'siguiente';

        if ($accion === 'anterior') {
            redirect('postulante/aplicacion.php?paso=' . max(1, $paso - 1));
        }

        if ($paso === 1) {
            $d = [
                'nombre'   => trim((string) ($_POST['alumno_nombre'] ?? '')),
                'apellido' => trim((string) ($_POST['alumno_apellido'] ?? '')),
                'dni'      => trim((string) ($_POST['alumno_dni'] ?? '')),
                'telefono' => trim((string) ($_POST['alumno_telefono'] ?? '')),
                'fecha_nacimiento' => trim((string) ($_POST['alumno_fecha_nacimiento'] ?? '')),
                'anio_postulado'   => (int) ($_POST['alumno_anio'] ?? 0),
            ];
            if (mb_strlen($d['nombre']) < 2) $errores[] = 'Ingresá el nombre del alumno.';
            if (mb_strlen($d['apellido']) < 2) $errores[] = 'Ingresá el apellido del alumno.';
            if (!in_array($d['anio_postulado'], $anios, true)) $errores[] = 'Elegí el año al que postula.';
            if (!$d['telefono']) $errores[] = 'Ingresá un teléfono de contacto.';
            if ($d['fecha_nacimiento']) {
                $ts = strtotime($d['fecha_nacimiento']);
                if (!$ts || $ts > time()) $errores[] = 'Fecha de nacimiento del alumno inválida.';
            } else {
                $errores[] = 'Ingresá la fecha de nacimiento del alumno.';
            }
            if ($d['dni'] !== '') {
                if (preg_match('/^\d{6,9}$/', $d['dni']) !== 1) {
                    $errores[] = 'El DNI del alumno debe tener entre 6 y 9 dígitos (si se ingresa).';
                } else {
                    $st = db()->prepare('SELECT id FROM alumnos WHERE dni = ? AND usuario_id <> ?');
                    $st->execute([$d['dni'], $uid]);
                    if ($st->fetch()) $errores[] = 'Ese DNI de alumno ya está registrado en otra solicitud.';
                }
            }

            if ($modo === 'nueva') {
                foreach (['dni_alumno_frente' => 'frente', 'dni_alumno_dorso' => 'dorso'] as $campoDoc => $caraDoc) {
                    // No exigir archivo si ya hay una foto cargada en el borrador
                    // (p. ej. al volver a este paso después de continuar).
                    if (empty($_FILES[$campoDoc]['name']) && empty($draft['docs'][$campoDoc])) {
                        $errores[] = 'Adjuntá la foto del DNI del alumno — ' . $caraDoc . ' (imagen o PDF).';
                    }
                }
            }

            if (!$errores) {
                $draft['alumno'] = $d;
                if ($modo === 'correccion') {
                    // Reemplazo por separado de cada foto vinculada a la solicitud existente.
                    foreach (['dni_alumno_frente' => 'frente', 'dni_alumno_dorso' => 'dorso'] as $campoDoc => $caraDoc) {
                        if (!empty($_FILES[$campoDoc]['name'])) {
                            $erroresSub = [];
                            $ruta = subir_archivo($campoDoc, 'inscripciones', $erroresSub);
                            if ($ruta) {
                                db()->prepare('UPDATE documentos SET estado = "reemplazado" WHERE solicitud_id = ? AND tipo = ? AND estado <> "reemplazado"')
                                    ->execute([(int) $sol['id'], $campoDoc]);
                                $st = db()->prepare('INSERT INTO documentos (solicitud_id, borrador_token, tipo, nombre_original, ruta, estado)
                                                     VALUES (?, NULL, ?, ?, ?, "recibido")');
                                $st->execute([(int) $sol['id'], $campoDoc, $_FILES[$campoDoc]['name'], $ruta]);
                                $draft['docs'][$campoDoc] = (int) db()->lastInsertId();
                            } else {
                                $errores[] = 'DNI del alumno (' . $caraDoc . '): ' . ($erroresSub[0] ?? 'No se pudo guardar el archivo.');
                            }
                        }
                    }
                } else {
                    foreach (['dni_alumno_frente', 'dni_alumno_dorso'] as $campoDoc) {
                        $nuevoId = subir_doc_borrador($token, $campoDoc, $errores);
                        if ($nuevoId !== null) {
                            $draft['docs'][$campoDoc] = $nuevoId;
                        }
                    }
                }
                if (!$errores) {
                    redirect('postulante/aplicacion.php?paso=2');
                }
            }
        } elseif ($paso === 2) {
            $d = [
                'nombre'   => trim((string) ($_POST['tutor_nombre'] ?? '')),
                'apellido' => trim((string) ($_POST['tutor_apellido'] ?? '')),
                'dni'      => trim((string) ($_POST['tutor_dni'] ?? '')),
                'fecha_nacimiento' => trim((string) ($_POST['tutor_fecha_nacimiento'] ?? '')),
                'telefono' => trim((string) ($_POST['tutor_telefono'] ?? '')),
                'direccion' => trim((string) ($_POST['tutor_direccion'] ?? '')),
            ];
            if (mb_strlen($d['nombre']) < 2) $errores[] = 'Ingresá el nombre del tutor/responsable.';
            if (mb_strlen($d['apellido']) < 2) $errores[] = 'Ingresá el apellido del tutor/responsable.';
            if (preg_match('/^\d{5,10}$/', $d['dni']) !== 1) $errores[] = 'El DNI del tutor es obligatorio (5–10 dígitos).';
            if ($d['fecha_nacimiento']) {
                $ts = strtotime($d['fecha_nacimiento']);
                if (!$ts || $ts > time()) $errores[] = 'Fecha de nacimiento del tutor inválida.';
            } else {
                $errores[] = 'Ingresá la fecha de nacimiento del tutor.';
            }
            if (!$d['telefono']) $errores[] = 'Ingresá un teléfono de contacto del tutor.';
            if (!$d['direccion']) $errores[] = 'Ingresá la dirección del tutor.';

            if ($modo === 'nueva') {
                foreach (['dni_tutor_frente' => 'frente', 'dni_tutor_dorso' => 'dorso'] as $campoDoc => $caraDoc) {
                    if (empty($_FILES[$campoDoc]['name']) && empty($draft['docs'][$campoDoc])) {
                        $errores[] = 'Adjuntá la foto del DNI del tutor — ' . $caraDoc . ' (imagen o PDF).';
                    }
                }
            }

            if (!$errores) {
                $draft['tutor'] = $d;
                if ($modo === 'correccion') {
                    foreach (['dni_tutor_frente' => 'frente', 'dni_tutor_dorso' => 'dorso'] as $campoDoc => $caraDoc) {
                        if (!empty($_FILES[$campoDoc]['name'])) {
                            $erroresSub = [];
                            $ruta = subir_archivo($campoDoc, 'inscripciones', $erroresSub);
                            if ($ruta) {
                                db()->prepare('UPDATE documentos SET estado = "reemplazado" WHERE solicitud_id = ? AND tipo = ? AND estado <> "reemplazado"')
                                    ->execute([(int) $sol['id'], $campoDoc]);
                                $st = db()->prepare('INSERT INTO documentos (solicitud_id, borrador_token, tipo, nombre_original, ruta, estado)
                                                     VALUES (?, NULL, ?, ?, ?, "recibido")');
                                $st->execute([(int) $sol['id'], $campoDoc, $_FILES[$campoDoc]['name'], $ruta]);
                                $draft['docs'][$campoDoc] = (int) db()->lastInsertId();
                            } else {
                                $errores[] = 'DNI del tutor (' . $caraDoc . '): ' . ($erroresSub[0] ?? 'No se pudo guardar el archivo.');
                            }
                        }
                    }
                } else {
                    foreach (['dni_tutor_frente', 'dni_tutor_dorso'] as $campoDoc) {
                        $nuevoId = subir_doc_borrador($token, $campoDoc, $errores);
                        if ($nuevoId !== null) {
                            $draft['docs'][$campoDoc] = $nuevoId;
                        }
                    }
                }
                if (!$errores) {
                    redirect('postulante/aplicacion.php?paso=3');
                }
            }
        } elseif ($paso === 3) {
            // ---------- ENVÍO FINAL ----------
            $faltanDocs = [];
            foreach (tipos_dni_documentos() as $campoDoc) {
                if (($draft['docs'][$campoDoc] ?? null) === null) {
                    $faltanDocs[] = doc_etiqueta_tipo($campoDoc);
                }
            }
            if (empty($draft['alumno']) || empty($draft['tutor']) || $faltanDocs) {
                $errores[] = 'Faltan datos o documentación'
                    . ($faltanDocs ? ': ' . implode(', ', $faltanDocs) : '.')
                    . ' Revisá los pasos anteriores.';
            } else {
                db()->beginTransaction();
                try {
                    $secIdSecre = null;
                    if ($modo === 'nueva') {
                        $st = db()->prepare(
                            'INSERT INTO alumnos (usuario_id, nombre, apellido, dni, telefono, fecha_nacimiento, anio_postulado)
                             VALUES (?, ?, ?, ?, ?, ?, ?)'
                        );
                        $st->execute([
                            $uid, $draft['alumno']['nombre'], $draft['alumno']['apellido'],
                            $draft['alumno']['dni'] ?: null, $draft['alumno']['telefono'],
                            $draft['alumno']['fecha_nacimiento'] ?: null, $draft['alumno']['anio_postulado'],
                        ]);
                        $aluId = (int) db()->lastInsertId();

                        $st = db()->prepare(
                            'INSERT INTO tutores (alumno_id, nombre, apellido, dni, fecha_nacimiento, telefono, direccion)
                             VALUES (?, ?, ?, ?, ?, ?, ?)'
                        );
                        $st->execute([
                            $aluId, $draft['tutor']['nombre'], $draft['tutor']['apellido'], $draft['tutor']['dni'],
                            $draft['tutor']['fecha_nacimiento'] ?: null, $draft['tutor']['telefono'], $draft['tutor']['direccion'],
                        ]);

                        $st = db()->prepare('INSERT INTO solicitudes (alumno_id, estado) VALUES (?, "recibida")');
                        $st->execute([$aluId]);
                        $solId = (int) db()->lastInsertId();

                        $st = db()->prepare(
                            'UPDATE documentos SET solicitud_id = ?, borrador_token = NULL
                             WHERE borrador_token = ? AND solicitud_id IS NULL'
                        );
                        $st->execute([$solId, $token]);
                    } else {
                        $aluId = (int) $alu['id'];
                        $st = db()->prepare(
                            'UPDATE alumnos SET nombre = ?, apellido = ?, dni = ?, telefono = ?, fecha_nacimiento = ?, anio_postulado = ?, vacante_id = NULL
                             WHERE id = ?'
                        );
                        $st->execute([
                            $draft['alumno']['nombre'], $draft['alumno']['apellido'], $draft['alumno']['dni'] ?: null,
                            $draft['alumno']['telefono'], $draft['alumno']['fecha_nacimiento'] ?: null,
                            $draft['alumno']['anio_postulado'], $aluId,
                        ]);
                        $st = db()->prepare(
                            'UPDATE tutores SET nombre = ?, apellido = ?, dni = ?, fecha_nacimiento = ?, telefono = ?, direccion = ? WHERE alumno_id = ?'
                        );
                        $st->execute([
                            $draft['tutor']['nombre'], $draft['tutor']['apellido'], $draft['tutor']['dni'],
                            $draft['tutor']['fecha_nacimiento'] ?: null, $draft['tutor']['telefono'],
                            $draft['tutor']['direccion'], $aluId,
                        ]);
                        $solId = (int) $sol['id'];
                        $st = db()->prepare(
                            'UPDATE solicitudes SET estado = "recibida", motivo_rechazo = NULL, fecha_presentacion = NOW(), fecha_resolucion = NULL, secretaria_id = NULL WHERE id = ?'
                        );
                        $st->execute([$solId]);
                    }

                    $st = db()->prepare('INSERT INTO historial_solicitudes (solicitud_id, estado) VALUES (?, "recibida")');
                    $st->execute([$solId]);

                    db()->commit();
                    unset($_SESSION[$claveDraft]);
                    flash('exito', 'Solicitud ' . ($modo === 'nueva' ? 'creada' : 'reenviada') . ' correctamente. Quedó en cola de revisión.');
                    redirect('postulante/estado.php');
                } catch (Throwable $t) {
                    db()->rollBack();
                    $errores[] = 'Ocurrió un error al guardar la solicitud: ' . $t->getMessage();
                }
            }
        }
    }
}

/* Datos para la vista */
$docsActuales = [];
foreach (tipos_dni_documentos() as $tipoDoc) {
    $docsActuales[$tipoDoc] = doc_actual($draft['docs'][$tipoDoc] ?? null);
}

render('postulante/aplicacion', [
    'titulo'   => $modo === 'correccion' ? 'Corregir solicitud' : 'Nueva inscripción',
    'rolPanel' => 'postulante',
    'paso'     => $paso,
    'draft'    => $draft,
    'anios'    => $anios,
    'errores'  => $errores,
    'docs'     => $docsActuales,
    'modo'     => $modo,
]);