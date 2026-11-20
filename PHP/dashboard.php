<?php
declare(strict_types=1);

require __DIR__ . '\\auth.php';
require __DIR__ . '\\layout.php';

$canViewUsers = puede('Usuarios y roles', 'ver');
$canCreateUsers = $canViewUsers && puede('Usuarios y roles', 'crear');
$canViewClients = puede('Clientes', 'ver');
$canViewBasc = puede('BASC Comercial', 'ver');
$isAdmin = (int) $_SESSION['rol_id'] === 1;

portalStart('Inicio');
?>
<div class="dashboard-intro">
    <img class="dashboard-logo" src="../Logo/Business_2023_Grande.jpg" alt="Smart Business Corporation">
    <div>
        <h1>Bienvenido, <?= e($currentUser['nombre']) ?></h1>
        <p>Selecciona una opción para comenzar.</p>
    </div>
</div>

<?php if ($canViewUsers || $canViewClients || $canViewBasc || $isAdmin): ?>
<section class="dashboard-section" aria-label="Accesos rápidos">
    <div class="dashboard-grid">
        <?php if ($canViewClients): ?>
            <a class="dashboard-card" href="clientes.php">
                <strong>Clientes</strong>
                <span>Consulta el listado de clientes del área comercial.</span>
            </a>
        <?php endif; ?>
        <?php if ($canViewBasc): ?>
            <a class="dashboard-card" href="basc-comercial.php">
                <strong>BASC - Comercial</strong>
                <span>Consulta los clientes homologados.</span>
            </a>
        <?php endif; ?>
        <?php if ($canViewUsers): ?>
            <a class="dashboard-card" href="usuarios.php">
                <strong>Usuarios y roles</strong>
                <span>Consulta los usuarios y administra sus accesos.</span>
            </a>
        <?php endif; ?>
        <?php if ($canCreateUsers): ?>
            <a class="dashboard-card" href="usuarios.php#crear-usuario">
                <strong>Registrar usuario</strong>
                <span>Crea una cuenta y asígnale un rol.</span>
            </a>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
            <a class="dashboard-card" href="permisos.php">
                <strong>Roles y permisos</strong>
                <span>Define los permisos de cada rol por módulo.</span>
            </a>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<div class="card"><p>Has iniciado sesión correctamente. Aún no tienes módulos asignados.</p></div>
<?php endif; ?>
<?php portalEnd(); ?>
