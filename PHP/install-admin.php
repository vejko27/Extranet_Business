<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/database.php';
require __DIR__ . '/clientes-schema.php';

$password = fgets(STDIN);
if ($password === false) {
    fwrite(STDERR, "No se recibió la contraseña.\n");
    exit(1);
}
$password = rtrim($password, "\r\n");
if (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
    fwrite(STDERR, "La contraseña debe tener entre 8 y 72 bytes y no contener caracteres nulos.\n");
    exit(1);
}

try {
    $db = database();
    $db->exec(
        "CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $db->exec(
        "CREATE TABLE IF NOT EXISTS usuarios (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            usuario VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(254) NOT NULL DEFAULT '',
            password VARCHAR(255) NOT NULL,
            rol_id INT UNSIGNED NOT NULL,
            estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
            version_sesion INT UNSIGNED NOT NULL DEFAULT 1,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (rol_id) REFERENCES roles(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $db->exec(
        "CREATE TABLE IF NOT EXISTS modulos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $db->exec(
        "CREATE TABLE IF NOT EXISTS permisos (
            rol_id INT UNSIGNED NOT NULL,
            modulo_id INT UNSIGNED NOT NULL,
            ver BOOLEAN NOT NULL DEFAULT 0,
            crear BOOLEAN NOT NULL DEFAULT 0,
            editar BOOLEAN NOT NULL DEFAULT 0,
            eliminar BOOLEAN NOT NULL DEFAULT 0,
            PRIMARY KEY (rol_id, modulo_id),
            FOREIGN KEY (rol_id) REFERENCES roles(id),
            FOREIGN KEY (modulo_id) REFERENCES modulos(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    instalarEsquemaClientes($db);
    $db->beginTransaction();
    $db->exec("INSERT INTO roles (id, nombre) VALUES (1, 'Administrador'), (2, 'Usuario')
        ON DUPLICATE KEY UPDATE id = id");
    $roles = $db->query('SELECT id, nombre FROM roles WHERE id IN (1, 2) ORDER BY id')->fetchAll();
    if (count($roles) !== 2 || $roles[0]['nombre'] !== 'Administrador' || $roles[1]['nombre'] !== 'Usuario') {
        throw new RuntimeException('Los roles existentes no coinciden con los roles iniciales.');
    }
    $db->exec("INSERT INTO modulos (nombre) VALUES ('Usuarios y roles')
        ON DUPLICATE KEY UPDATE id = id");
    $statement = $db->prepare(
        'INSERT INTO usuarios (nombre, usuario, password, rol_id) VALUES (:nombre, :usuario, :password, 1)'
    );
    $statement->execute([
        'nombre' => 'vcanova',
        'usuario' => 'vcanova',
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    $db->commit();
    fwrite(STDOUT, "Administrador vcanova creado correctamente.\n");
} catch (PDOException | RuntimeException $exception) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Smart Business instalación: ' . $exception->getMessage());
    fwrite(STDERR, "No se pudo crear el administrador. Revisa la configuración, los permisos de MySQL y el registro de PHP. No se modificará un usuario existente.\n");
    exit(1);
}
