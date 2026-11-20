<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/layout.php';
exigirPermiso('Usuarios y roles', 'ver');

$isAdmin = (int) $_SESSION['rol_id'] === 1;
$canCreate = puede('Usuarios y roles', 'crear');
$canEdit = puede('Usuarios y roles', 'editar');
$error = '';
$notice = $_SESSION['usuarios_notice'] ?? '';
unset($_SESSION['usuarios_notice']);

function postString(string $name): string
{
    return is_string($_POST[$name] ?? null) ? $_POST[$name] : '';
}

try {
    $roles = $db->query('SELECT id, nombre FROM roles ORDER BY nombre')->fetchAll();
    $allowedRoles = $isAdmin ? $roles : array_values(array_filter(
        $roles,
        static fn (array $role): bool => (int) $role['id'] === (int) $_SESSION['rol_id']
    ));
    $allowedRoleIds = array_map(static fn (array $role): int => (int) $role['id'], $allowedRoles);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('La sesión del formulario ha caducado. Vuelve a cargar la página.');
        }
        $action = postString('accion');
        if (($action === 'crear' && !$canCreate)
            || (in_array($action, ['editar', 'estado'], true) && !$canEdit)
            || !in_array($action, ['crear', 'editar', 'estado'], true)
        ) {
            http_response_code(403);
            exit('Acceso denegado.');
        }

        $id = ctype_digit(postString('id')) ? (int) postString('id') : 0;
        $target = null;
        if ($action !== 'crear') {
            $db->beginTransaction();
            $statement = $db->prepare('SELECT id, rol_id, estado FROM usuarios WHERE id = ? FOR UPDATE');
            $statement->execute([$id]);
            $target = $statement->fetch();
            if (!$target || (!$isAdmin && (int) $target['rol_id'] !== (int) $_SESSION['rol_id'])) {
                $db->rollBack();
                http_response_code(403);
                exit('No puedes modificar ese usuario.');
            }
        }

        if ($action === 'estado') {
            if ($id === (int) $_SESSION['id_usuario']) {
                $error = 'No puedes desactivar tu propia cuenta.';
            } else {
                $newStatus = $target['estado'] === 'activo' ? 'inactivo' : 'activo';
                $statement = $db->prepare('UPDATE usuarios SET estado = ? WHERE id = ?');
                $statement->execute([$newStatus, $id]);
            }
        } else {
            $name = trim(postString('nombre'));
            $username = trim(postString('usuario'));
            $email = trim(postString('email'));
            $password = postString('password');
            $roleId = ctype_digit(postString('rol_id')) ? (int) postString('rol_id') : 0;
            if ($name === '' || strlen($name) > 100 || $username === '' || strlen($username) > 100
                || strlen($email) > 254 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
                || !in_array($roleId, $allowedRoleIds, true)
            ) {
                $error = 'Revisa el nombre, usuario, correo y rol seleccionado.';
            } elseif (($action === 'crear' || $password !== '')
                && (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0"))
            ) {
                $error = 'La contraseña debe tener entre 8 y 72 bytes y no contener caracteres nulos.';
            } elseif ($action === 'editar' && $id === (int) $_SESSION['id_usuario']
                && $roleId !== (int) $_SESSION['rol_id']
            ) {
                $error = 'No puedes cambiar tu propio rol.';
            } elseif ($action === 'crear') {
                $statement = $db->prepare(
                    'INSERT INTO usuarios (nombre, usuario, email, password, rol_id) VALUES (?, ?, ?, ?, ?)'
                );
                $statement->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT), $roleId]);
            } else {
                $statement = $db->prepare('UPDATE usuarios SET nombre = ?, usuario = ?, email = ?, rol_id = ? WHERE id = ?');
                $statement->execute([$name, $username, $email, $roleId, $id]);
                if ($password !== '') {
                    $statement = $db->prepare('UPDATE usuarios SET password = ?, version_sesion = version_sesion + 1 WHERE id = ?');
                    $statement->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
                }
            }
        }

        if ($db->inTransaction()) {
            if ($error === '') {
                $db->commit();
            } else {
                $db->rollBack();
            }
        }
        if ($error === '') {
            $_SESSION['usuarios_notice'] = 'Usuario guardado correctamente.';
            header('Location: usuarios.php', true, 303);
            exit;
        }
    }

    $editing = null;
    $editId = $_GET['editar'] ?? '';
    if ($canEdit && is_string($editId) && ctype_digit($editId)) {
        $statement = $db->prepare('SELECT id, nombre, usuario, email, rol_id FROM usuarios WHERE id = ?');
        $statement->execute([(int) $editId]);
        $editing = $statement->fetch();
        if (!$editing || (!$isAdmin && (int) $editing['rol_id'] !== (int) $_SESSION['rol_id'])) {
            http_response_code(403);
            exit('No puedes modificar ese usuario.');
        }
    }
    $users = $db->query(
        'SELECT u.id, u.nombre, u.usuario, u.email, u.rol_id, u.estado, r.nombre AS rol
         FROM usuarios u JOIN roles r ON r.id = u.rol_id ORDER BY u.nombre'
    )->fetchAll();
} catch (PDOException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Smart Business usuarios: ' . $exception->getMessage());
    if ($exception->getCode() === '23000') {
        http_response_code(409);
        exit('No se pudo guardar: el usuario ya existe o el rol no está disponible.');
    }
    http_response_code(503);
    exit('No fue posible administrar los usuarios. Contacta al administrador.');
}

portalStart('Usuarios y roles');
?>
<div class="users-header">
    <img src="../Logo/Business_2023_Grande.jpg" alt="Smart Business Corporation">
    <div>
        <h1>Gestión de usuarios</h1>
        <p><?= $canCreate ? 'Crea usuarios y asigna sus roles.' : 'Consulta los usuarios y sus roles.' ?></p>
    </div>
</div>
<?php if ($notice !== ''): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>

<?php if ($editing || $canCreate): ?>
<section class="card users-card" id="crear-usuario">
    <h2><?= $editing ? 'Editar usuario' : 'Crear usuario' ?></h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="accion" value="<?= $editing ? 'editar' : 'crear' ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
        <div class="users-fields">
            <label><span class="sr-only">Nombre</span><input name="nombre" placeholder="Nombre y apellido" maxlength="100" required value="<?= e($editing['nombre'] ?? '') ?>"></label>
            <label><span class="sr-only">Usuario</span><input name="usuario" placeholder="Usuario" maxlength="100" required value="<?= e($editing['usuario'] ?? '') ?>"></label>
            <label><span class="sr-only">Correo (opcional)</span><input type="email" name="email" placeholder="Correo (opcional)" maxlength="254" value="<?= e($editing['email'] ?? '') ?>"></label>
            <label><span class="sr-only">Contraseña<?= $editing ? ' (dejar vacía para conservar)' : '' ?></span>
                <input type="password" name="password" placeholder="<?= $editing ? 'Nueva contraseña (opcional)' : 'Contraseña' ?>" autocomplete="new-password" aria-describedby="password-hint" <?= $editing ? '' : 'required' ?>>
            </label>
            <label><span class="sr-only">Rol</span>
                <select name="rol_id" required>
                    <?php foreach ($allowedRoles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= (int) ($editing['rol_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= e($role['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit"><?= $editing ? 'Guardar cambios' : 'Crear' ?></button>
        </div>
        <p class="muted" id="password-hint">La contraseña debe tener entre 8 y 72 bytes. <?= $editing ? 'Déjala vacía para conservar la actual. ' : '' ?>Solo un administrador puede asignar otros roles.</p>
        <?php if ($editing): ?><a href="usuarios.php">Cancelar edición</a><?php endif; ?>
    </form>
</section>
<?php endif; ?>

<section class="card users-card">
    <h2>Usuarios registrados</h2>
    <div class="table-wrap">
        <table class="users-table">
            <thead><tr><th scope="col">ID</th><th scope="col">Nombre</th><th scope="col">Usuario</th><th scope="col">Correo</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td data-label="ID"><?= (int) $user['id'] ?></td>
                    <td data-label="Nombre"><?= e($user['nombre']) ?></td>
                    <td data-label="Usuario"><?= e($user['usuario']) ?></td>
                    <td data-label="Correo"><?= e($user['email']) ?></td>
                    <td data-label="Rol"><span class="users-badge"><?= e($user['rol']) ?></span></td>
                    <td data-label="Estado"><span class="users-badge <?= $user['estado'] === 'activo' ? 'users-badge-active' : 'users-badge-inactive' ?>"><?= e(ucfirst($user['estado'])) ?></span></td>
                    <td data-label="Acciones"><div class="actions">
                        <?php if ($canEdit && ($isAdmin || (int) $user['rol_id'] === (int) $_SESSION['rol_id'])): ?>
                            <a class="button" href="usuarios.php?editar=<?= (int) $user['id'] ?>">Editar</a>
                            <?php if ((int) $user['id'] !== (int) $_SESSION['id_usuario']): ?>
                            <form method="post" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="accion" value="estado">
                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                <button type="submit" class="<?= $user['estado'] === 'activo' ? 'users-button-danger' : 'users-button-success' ?>"><?= $user['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php portalEnd(); ?>
