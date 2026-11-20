<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['id_usuario'], $_SESSION['ultima_actividad'])
    || time() - (int) $_SESSION['ultima_actividad'] > 7200
) {
    $_SESSION = [];
    header('Location: ../index.php', true, 303);
    exit;
}

try {
    $db = database();
    $statement = $db->prepare(
        'SELECT u.nombre, u.rol_id, u.estado, u.version_sesion, r.nombre AS rol
         FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ?'
    );
    $statement->execute([(int) $_SESSION['id_usuario']]);
    $currentUser = $statement->fetch();
    if (!$currentUser || $currentUser['estado'] !== 'activo'
        || !isset($_SESSION['version_sesion'])
        || (int) $currentUser['version_sesion'] !== (int) $_SESSION['version_sesion']
    ) {
        $_SESSION = [];
        header('Location: ../index.php', true, 303);
        exit;
    }
    $_SESSION['nombre'] = $currentUser['nombre'];
    $_SESSION['rol_id'] = (int) $currentUser['rol_id'];
    $_SESSION['ultima_actividad'] = time();
} catch (PDOException | RuntimeException $exception) {
    error_log('Smart Business autorización: ' . $exception->getMessage());
    http_response_code(503);
    exit('No fue posible verificar el acceso. Contacta al administrador.');
}

function puede(string $modulo, string $accion): bool
{
    global $db;
    if (!in_array($accion, ['ver', 'crear', 'editar', 'eliminar'], true)) {
        return false;
    }
    if ((int) $_SESSION['rol_id'] === 1) {
        return true;
    }
    static $permissions = null;
    if ($permissions === null) {
        $statement = $db->prepare(
            'SELECT m.nombre, p.ver, p.crear, p.editar, p.eliminar
             FROM permisos p JOIN modulos m ON m.id = p.modulo_id WHERE p.rol_id = ?'
        );
        $statement->execute([(int) $_SESSION['rol_id']]);
        $permissions = [];
        foreach ($statement->fetchAll() as $row) {
            $permissions[$row['nombre']] = $row;
        }
    }
    return !empty($permissions[$modulo][$accion]);
}

function exigirPermiso(string $modulo, string $accion): void
{
    if (!puede($modulo, $accion)) {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
