<?php
declare(strict_types=1);

require __DIR__ . '\\auth.php';
require __DIR__ . '\\layout.php';

if ((int) $_SESSION['rol_id'] !== 1) {
    http_response_code(403);
    exit('Solo un administrador puede gestionar los permisos.');
}

$actions = ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'];
$notice = $_SESSION['permisos_notice'] ?? '';
unset($_SESSION['permisos_notice']);
$error = '';

try {
    $roles = $db->query('SELECT id, nombre FROM roles ORDER BY id')->fetchAll();
    $modules = $db->query('SELECT id, nombre FROM modulos ORDER BY nombre')->fetchAll();
    $roleIds = array_map(static fn (array $role): int => (int) $role['id'], $roles);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('La sesión del formulario ha caducado. Vuelve a cargar la página.');
        }
        $action = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
        if ($action === 'crear_rol') {
            $name = is_string($_POST['nombre'] ?? null) ? trim($_POST['nombre']) : '';
            if ($name === '' || strlen($name) > 100) {
                $error = 'Escribe un nombre válido para el rol.';
            } else {
                $statement = $db->prepare('INSERT INTO roles (nombre) VALUES (?)');
                $statement->execute([$name]);
                $_SESSION['permisos_notice'] = 'Rol creado correctamente. Configura sus permisos.';
                header('Location: permisos.php?rol=' . $db->lastInsertId(), true, 303);
                exit;
            }
        } elseif ($action === 'guardar') {
            $roleInput = is_string($_POST['rol_id'] ?? null) ? $_POST['rol_id'] : '';
            $roleId = ctype_digit($roleInput) ? (int) $roleInput : 0;
            if ($roleId === 1 || !in_array($roleId, $roleIds, true)) {
                $error = 'Selecciona un rol válido distinto de Administrador.';
            } else {
                $posted = is_array($_POST['permisos'] ?? null) ? $_POST['permisos'] : [];
                $statement = $db->prepare(
                    'INSERT INTO permisos (rol_id, modulo_id, ver, crear, editar, eliminar) VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE ver = VALUES(ver), crear = VALUES(crear),
                         editar = VALUES(editar), eliminar = VALUES(eliminar)'
                );
                $db->beginTransaction();
                foreach ($modules as $module) {
                    $modulePermissions = $posted[$module['id']] ?? [];
                    $modulePermissions = is_array($modulePermissions) ? $modulePermissions : [];
                    $values = [$roleId, (int) $module['id']];
                    foreach (array_keys($actions) as $key) {
                        $values[] = ($modulePermissions[$key] ?? null) === '1' ? 1 : 0;
                    }
                    $statement->execute($values);
                }
                $db->commit();
                $_SESSION['permisos_notice'] = 'Permisos actualizados correctamente.';
                header('Location: permisos.php?rol=' . $roleId, true, 303);
                exit;
            }
        } else {
            http_response_code(400);
            exit('Acción no válida.');
        }
    }

    $selectedInput = is_string($_GET['rol'] ?? null) ? $_GET['rol'] : '';
    $selectedRole = ctype_digit($selectedInput) && in_array((int) $selectedInput, $roleIds, true)
        ? (int) $selectedInput : 1;
    $statement = $db->prepare('SELECT modulo_id, ver, crear, editar, eliminar FROM permisos WHERE rol_id = ?');
    $statement->execute([$selectedRole]);
    $currentPermissions = [];
    foreach ($statement->fetchAll() as $row) {
        $currentPermissions[(int) $row['modulo_id']] = $row;
    }
} catch (PDOException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Smart Business permisos: ' . $exception->getMessage());
    if ($exception->getCode() === '23000') {
        http_response_code(409);
        exit('No se pudo guardar: el rol ya existe o dejó de estar disponible.');
    }
    http_response_code(503);
    exit('No fue posible administrar los roles y permisos. Contacta al administrador.');
}

portalStart('Roles y permisos');
?>
<div class="permissions-header">
    <img src="../Logo/Business_2023_Grande.jpg" alt="Smart Business Corporation">
    <div>
        <h1>Gestión de permisos</h1>
        <p>Define qué puede hacer cada rol en cada módulo.</p>
    </div>
</div>
<?php if ($notice !== ''): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>

<section class="card permissions-card">
    <form method="get" class="permissions-role-form">
        <label>Rol
            <select name="rol">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id'] ?>" <?= (int) $role['id'] === $selectedRole ? 'selected' : '' ?>><?= e($role['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Seleccionar</button>
    </form>
</section>

<section class="card permissions-card">
    <?php if ($selectedRole === 1): ?>
        <p>El administrador tiene todos los permisos; no se pueden modificar.</p>
    <?php else: ?>
        <p>Para entrar a un módulo, activa «Ver». El permiso «Eliminar» queda reservado para funciones futuras.</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="rol_id" value="<?= $selectedRole ?>">
    <?php endif; ?>
    <div class="table-wrap">
        <table class="permissions-table">
            <thead><tr><th scope="col">Módulo</th><?php foreach ($actions as $label): ?><th scope="col"><?= e($label) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($modules as $module): ?>
                <tr>
                    <th scope="row"><?= e($module['nombre']) ?></th>
                    <?php foreach ($actions as $key => $label): ?>
                        <td><label><input type="checkbox" aria-label="<?= e($label . ' ' . $module['nombre']) ?>"
                            name="permisos[<?= (int) $module['id'] ?>][<?= e($key) ?>]" value="1"
                            <?= $selectedRole === 1 || !empty($currentPermissions[(int) $module['id']][$key]) ? 'checked' : '' ?>
                            <?= $selectedRole === 1 ? 'disabled' : '' ?>><span class="permissions-action-label" aria-hidden="true"><?= e($label) ?></span></label></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($selectedRole !== 1): ?>
        <p class="permissions-submit"><button type="submit">Guardar permisos</button></p>
        </form>
    <?php endif; ?>
</section>

<section class="card permissions-card">
    <h2>Crear rol</h2>
    <form method="post" class="permissions-role-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="accion" value="crear_rol">
        <label>Nombre del rol<input name="nombre" maxlength="100" required></label>
        <button type="submit">Crear rol</button>
    </form>
</section>
<?php portalEnd(); ?>
