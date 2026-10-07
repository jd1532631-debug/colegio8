<?php
/**
 * Colegio8 — Importador de datos de prueba (SOLO DESARROLLO).
 *
 * Ejecuta database/datos_prueba_dev.sql, que es la ÚNICA fuente de los datos
 * de prueba (cuentas, cursos, solicitudes, libros, préstamos, etc.). Las
 * contraseñas viven hasheadas con bcrypt dentro del propio .sql; las de
 * texto plano están documentadas en un comentario de ese archivo y en el
 * README.
 *
 * Uso (CLI):
 *     php database/sembrar_datos_dev.php
 *
 * Requiere que la base YA tenga el esquema importado, y solo actúa si no
 * existen cuentas cargadas, para no duplicar datos.
 */

declare(strict_types=1);

$archivoSql = __DIR__ . '/datos_prueba_dev.sql';
if (!is_file($archivoSql)) {
    fwrite(STDERR, "No se encontró database/datos_prueba_dev.sql\n");
    exit(1);
}

// --- Conexión -------------------------------------------------------------
function dev_db(): PDO
{
    $archivo = __DIR__ . '/../app/config.php';
    if (is_file($archivo)) {
        require $archivo;
    }
    $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
    $base = defined('DB_NAME') ? DB_NAME : 'colegio8';
    $user = defined('DB_USER') ? DB_USER : 'root';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    $dsn  = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $base);
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

try {
    $pdo = dev_db();
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a la base: " . $e->getMessage() . "\n");
    exit(1);
}

// --- Guarda: no cargar si ya hay cuentas ----------------------------------
$hayUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
if ($hayUsuarios > 0) {
    fwrite(STDERR, "Ya existen cuentas cargadas. No se cargan datos de prueba para no duplicar.\n");
    exit(1);
}

echo "Importando database/datos_prueba_dev.sql…\n";

try {
    $sql = file_get_contents($archivoSql);
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer database/datos_prueba_dev.sql');
    }
    $pdo->exec($sql);
} catch (Throwable $e) {
    fwrite(STDERR, "Error importando datos de prueba: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Datos de prueba cargados correctamente.\n\n";
echo "Accesos (SOLO desarrollo; contraseñas documentadas en datos_prueba_dev.sql y README):\n";
echo "  - Secretaría:    secretaria / Secretaria2026  (código institucional: COLEGIO8-2026)\n";
echo "  - Bibliotecario: bibliotecario / Biblioteca2026\n";
echo "  - Postulante:    garcia / Sofia2026   (solicitud aprobada, 1º B)\n";
echo "  - Lector (alumno): garcia / Sofia2026   ·  Lector (profesor): carlos_gomez / ProfeCarlos2026\n";
echo "Recursos cargados: 13 familias/solicitudes (4 aprobadas, 2 rechazadas, 2 en revisión,\n";
echo "5 recibidas + lista de espera en 1º A), 15 libros, préstamos activos/vencidos/devueltos,\n";
echo "pedidos pendientes/aceptados/rechazados, favoritos, amonestaciones y avisos.\n";