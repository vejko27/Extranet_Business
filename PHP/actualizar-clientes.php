<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '\\database.php';
require __DIR__ . '\\clientes-schema.php';

try {
    instalarEsquemaClientes(database());
    fwrite(STDOUT, "Módulo Clientes instalado correctamente.\n");
} catch (PDOException | RuntimeException $exception) {
    error_log('Smart Business instalación de Clientes: ' . $exception->getMessage());
    fwrite(STDERR, "No fue posible instalar Clientes. Revisa la configuración y los permisos de la base de datos.\n");
    exit(1);
}
