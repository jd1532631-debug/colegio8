<?php
/**
 * Colegio8 — Latido (heartbeat) del bloqueo "en revisión".
 *
 * Lo llama la pantalla de revisión cada ~30 s mientras está abierta para
 * renovar el vencimiento del bloqueo. Si el secretario cierra la ventana
 * o se va sin resolver, el bloqueo vence solo y la solicitud vuelve a
 * "recibida" (para ser revisada) vía liberar_revisiones_vencidas().
 */
require __DIR__ . '/../../app/core.php';
requerir_sesion('secretaria');
$sec = sess('secretaria');

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0 || !verificar_csrf()) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit('{"ok":false}');
}

db()->prepare(
    'UPDATE solicitudes SET en_revision_hasta = DATE_ADD(NOW(), INTERVAL ' . REVISION_BLOQUEO_SEG . ' SECOND)
     WHERE id = ? AND estado = "en_revision" AND secretaria_id = ?'
)->execute([$id, (int) $sec['id']]);

header('Content-Type: application/json');
echo '{"ok":true}';