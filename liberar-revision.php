<?php
/**
 * Colegio8 — Liberación del bloqueo "en revisión".
 *
 * Lo llama la pantalla de revisión con fetch keepalive al cerrar/ocultar
 * la ventana o al salir sin resolver: la solicitud vuelve a "recibida"
 * y cualquier secretaria puede volver a tomarla. Solo libera si el
 * bloqueo es de la secretaria que llama.
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

$st = db()->prepare(
    "UPDATE solicitudes SET estado = 'recibida', secretaria_id = NULL, en_revision_hasta = NULL
     WHERE id = ? AND estado = 'en_revision' AND secretaria_id = ?"
);
$st->execute([$id, (int) $sec['id']]);
if ($st->rowCount() > 0) {
    db()->prepare(
        "INSERT INTO historial_solicitudes (solicitud_id, estado, motivo)
         VALUES (?, 'recibida', 'Liberada: el secretario cerró la revisión sin resolver.')"
    )->execute([$id]);
}

header('Content-Type: application/json');
echo '{"ok":true}';