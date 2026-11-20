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
            estado VARCHAR(16) NOT NULL DEFAULT ''
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $db->exec("INSERT INTO modulos (nombre) VALUES ('Clientes') ON DUPLICATE KEY UPDATE id = id");
}
