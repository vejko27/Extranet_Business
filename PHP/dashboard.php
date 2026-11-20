<?php
declare(strict_types=1);

require __DIR__ . '\\auth.php';
require __DIR__ . '\\layout.php';

$canViewUsers = puede('Usuarios y roles', 'ver');
$canCreateUsers = $canViewUsers && puede('Usuarios y roles', 'crear');
$isAdmin = (int) $_SESSION['rol_id'] === 1;

portalStart('Inicio');
?>
<div class="dashboard-intro">
    <div class="eyebrow">Tu espacio de trabajo</div>
    <h1>Bienvenido, <?= e($currentUser['nombre']) ?></h1>
    <p>Selecciona una opción para comenzar. Tu perfil es <strong><?= e($currentUser['rol']) ?></strong>.</p>
</div>

<?php if ($canViewUsers || $isAdmin): ?>
<section class="dashboard-section" aria-labelledby="access-title">
    <h2 id="access-title">Accesos rápidos</h2>
    <div class="dashboard-grid">
        <?php if ($canViewUsers): ?>
            <a class="dashboard-card" href="usuarios.php">
                <span class="dashboard-card-icon" aria-hidden="true">U</span>
                <strong>Usuarios y roles</strong>
                <span>Consulta los usuarios y administra sus accesos.</span>
            </a>
        <?php endif; ?>
        <?php if ($canCreateUsers): ?>
            <a class="dashboard-card" href="usuarios.php#crear-usuario">
                <span class="dashboard-card-icon" aria-hidden="true">+</span>
                <strong>Registrar usuario</strong>
                <span>Crea una cuenta y asígnale un rol.</span>
            </a>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
            <a class="dashboard-card" href="permisos.php">
                <span class="dashboard-card-icon" aria-hidden="true">P</span>
                <strong>Roles y permisos</strong>
                <span>Define los permisos de cada rol por módulo.</span>
            </a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<?php portalEnd(); ?>
