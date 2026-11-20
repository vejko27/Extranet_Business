<?php
declare(strict_types=1);

function database(): PDO
{
    $configPath = getenv('SMART_BUSINESS_DB_CONFIG');
    if (!is_string($configPath) || $configPath === '') {
        $configPath = PHP_OS_FAMILY === 'Windows'
            ? 'C:/xampp/private/smart-business-db.php'
            : '/etc/smart-business/smart-business-db.php';
    }
    if (!is_file($configPath)) {
        throw new RuntimeException('Falta el archivo privado de configuración de la base de datos.');
    }

    $config = require $configPath;
    if (!is_array($config)
        || !isset($config['host'], $config['database'], $config['username'], $config['password'])
        || !is_string($config['host'])
        || !is_string($config['database'])
        || !is_string($config['username'])
        || !is_string($config['password'])
        || $config['host'] === ''
        || $config['database'] === ''
        || $config['username'] === ''
    ) {
        throw new RuntimeException('La configuración de la base de datos es inválida.');
    }

    return new PDO(
        'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';charset=utf8mb4',
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
