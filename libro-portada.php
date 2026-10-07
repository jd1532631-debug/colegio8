<?php
/**
 * Colegio8 — Portada de libro (servida con permisos).
 * Accesible para cualquier sesión autenticada (lector o bibliotecario).
 */
require __DIR__ . '/../app/core.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Solicitud inválida.');
}

$autenticado = tiene_sesion('lector') || tiene_sesion('bibliotecario')
    || tiene_sesion('postulante') || tiene_sesion('secretaria');
if (!$autenticado) {
    http_response_code(403);
    exit('Debés iniciar sesión.');
}

$st = db()->prepare('SELECT portada FROM biblioteca_libros WHERE id = ?');
$st->execute([$id]);
$portada = $st->fetchColumn();

if (!$portada) {
    http_response_code(404);
    exit('Sin portada.');
}

$abs = dirname(__DIR__) . '/public/' . $portada;
if (!is_file($abs)) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$tipos = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
header('Content-Type: ' . ($tipos[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($abs));
header('Cache-Control: public, max-age=3600');
readfile($abs);
exit;