<?php
declare(strict_types=1);

require __DIR__ . '\\session.php';
require __DIR__ . '\\database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('La sesión del formulario ha caducado. Vuelve a cargar la página.');
}

$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;
if (!is_string($username) || !is_string($password)
    || trim($username) === '' || strlen($username) > 100
    || $password === '' || strlen($password) > 72 || str_contains($password, "\0")
) {
    $_SESSION['login_error'] = 'Introduce un usuario y una contraseña válidos.';
    header('Location: ../index.php', true, 303);
    exit;
}

try {
    if (($_SESSION['login_bloqueo'] ?? 0) > time()) {
        $_SESSION['login_error'] = 'Demasiados intentos. Espera cinco minutos antes de volver a intentarlo.';
        header('Location: ../index.php', true, 303);
        exit;
    }
    $statement = database()->prepare(
        'SELECT id, nombre, usuario, password, rol_id, estado, version_sesion FROM usuarios WHERE usuario = :username LIMIT 1'
    );
    $statement->execute(['username' => trim($username)]);
    $user = $statement->fetch();
    $validPassword = password_verify(
        $password,
        $user['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'
    );

    if (!$user || !$validPassword) {
        $_SESSION['login_fallos'] = ($_SESSION['login_fallos'] ?? 0) + 1;
        if ($_SESSION['login_fallos'] >= 5) {
            $_SESSION['login_bloqueo'] = time() + 300;
            $_SESSION['login_fallos'] = 0;
        }
        $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
        header('Location: ../index.php', true, 303);
        exit;
    }

    if ($user['estado'] !== 'activo') {
        $_SESSION['login_error'] = 'Tu usuario está inactivo. Contacta al administrador.';
        header('Location: ../index.php', true, 303);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION = [
        'id_usuario' => (int) $user['id'],
        'nombre' => $user['nombre'],
        'rol_id' => (int) $user['rol_id'],
        'version_sesion' => (int) $user['version_sesion'],
        'ultima_actividad' => time(),
    ];
    header('Location: dashboard.php', true, 303);
} catch (PDOException | RuntimeException $exception) {
    error_log('Smart Business login: ' . $exception->getMessage());
    $_SESSION['login_error'] = 'No fue posible conectar con el servicio de acceso. Contacta al administrador.';
    header('Location: ../index.php', true, 303);
}
exit;
