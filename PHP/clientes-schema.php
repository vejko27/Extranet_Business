<?php
declare(strict_types=1);

function instalarEsquemaClientes(PDO $db): void
{
    $db->exec(
        "CREATE TABLE IF NOT EXISTS clientes (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            razon_social VARCHAR(200) NOT NULL,
            ruc VARCHAR(30) NOT NULL DEFAULT '',
            tipo VARCHAR(16) NOT NULL DEFAULT '',
            certificado VARCHAR(100) NOT NULL DEFAULT '',
            acuerdo_seguridad VARCHAR(16) NOT NULL DEFAULT '',
            ddjj VARCHAR(16) NOT NULL DEFAULT '',
            estado VARCHAR(16) NOT NULL DEFAULT '',
            archivado TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $statement = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
    );
    $statement->execute(['clientes', 'archivado']);
    if ((int) $statement->fetchColumn() === 0) {
        $db->exec('ALTER TABLE clientes ADD COLUMN archivado TINYINT(1) NOT NULL DEFAULT 0');
    }
    $db->exec("INSERT INTO modulos (nombre) VALUES ('Clientes') ON DUPLICATE KEY UPDATE id = id");
}
