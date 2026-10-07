<?php
/**
 * Colegio8 — Servicio seguro de documentos de inscripción.
 *
 * Acceso:
 *  - Documentos de una solicitud: la secretaría, o el postulante dueño.
 *  - Borradores (solicitud_id NULL): únicamente la sesión que posee el
 *    token del borrador (identifica la sesión que subió el archivo).
 * Nunca se accede por URL directa (uploads/ está bloqueado).
 */
require __DIR__ . '/../app/core.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Solicitud inválida.');
}

$st = db()->prepare('SELECT * FROM documentos WHERE id = ?');
$st->execute([$id]);
$doc = $st->fetch();

$permitido = false;
if ($doc) {
    if ($doc['solicitud_id'] !== null) {
        $stO = db()->prepare(
            'SELECT a.usuario_id FROM solicitudes s JOIN alumnos a ON a.id = s.alumno_id WHERE s.id = ?'
        );
        $stO->execute([(int) $doc['solicitud_id']]);
        $usuarioId = $stO->fetchColumn();
        $permitido = tiene_sesion('secretaria')
            || (
                tiene_sesion('postulante')
                && $usuarioId !== false
                && (int) $usuarioId === (int) sess('postulante')['id']
            );
    } else {
        // Borrador sin solicitud: solo la sesión dueña del token.
        $token = $_SESSION['col8_app']['token'] ?? null;
        $permitido = is_string($token) && is_string($doc['borrador_token'])
            && hash_equals($token, $doc['borrador_token']);
    }
}

if (!$permitido) {
    http_response_code(403);
    exit('No tenés permiso para ver este documento.');
}

$ruta = $doc['ruta'];
if (strpos($ruta, 'uploads/') !== 0) {
    http_response_code(400);
    exit('Archivo inválido.');
}

$abs = dirname(__DIR__) . '/public/' . $ruta;
if (!is_file($abs)) {
    http_response_code(404);
    exit('El archivo ya no existe en el servidor.');
}

$ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$tipos = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'pdf'  => 'application/pdf',
];
$mime = $tipos[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($abs));
header('Content-Disposition: inline; filename="' . rawurlencode($doc['nombre_original']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($abs);
exit;